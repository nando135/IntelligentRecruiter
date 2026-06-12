from fastapi import FastAPI, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from dotenv import load_dotenv
from pydantic import BaseModel, Field
from typing import Optional, List, Any, Dict
import tempfile
import os
import re
import unicodedata
from datetime import datetime

from langchain_community.document_loaders import PyPDFLoader, Docx2txtLoader
from langchain_ollama import ChatOllama

from db import get_conn
from graph.runner import run_chat, clear_thread

load_dotenv()

app = FastAPI(title="Intelligent Recruiter AI CV Scanner")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3.2:1b")
OLLAMA_URL   = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")

ALL_SECTIONS = [
    "Education", "Academic Background", "Academic Qualifications",
    "Experience", "Work Experience", "Employment History",
    "Professional Experience", "Career History", "Internship",
    "Skills", "Technical Skills", "Core Competencies",
    "Projects", "Personal Projects", "Academic Projects", "Portfolio",
    "Certifications", "Certificates", "Licenses", "Professional Development",
    "Achievements", "Awards", "Honors", "Recognition", "Accomplishments",
    "Languages", "Language Skills", "Language Proficiency",
    "Volunteer", "Volunteering", "Community Service", "Social Work",
    "Publications", "Research", "Papers",
    "Competitions", "Hackathons", "Contests",
    "Hobbies", "Interests", "Hobbies & Interests",
    "References", "Referees",
    "Summary", "Professional Summary", "Profile", "About Me",
    "Objective", "Career Objective", "Personal Statement",
    "Personal Information", "Personal Details", "Personal Info",
    "Extracurricular", "Extracurricular Activities", "Co-curricular",
]


# ─── Chat ────────────────────────────────────────────────────────────────────

class ChatRequest(BaseModel):
    thread_id: str
    message: str

# ─── Classification / Ranking Request Models ────────────────────────────────

class RankingCandidate(BaseModel):
    id: int
    full_name: Optional[str] = None
    category: Optional[str] = None
    current_job_title: Optional[str] = None
    professional_summary: Optional[str] = None
    total_experience_years: Optional[float] = 0
    skills: List[str] = Field(default_factory=list)


class RankCandidatesRequest(BaseModel):
    category: str
    candidates: List[RankingCandidate] = Field(default_factory=list)

class JobRankingCandidate(BaseModel):
    id: int
    full_name: Optional[str] = None
    category: Optional[str] = None
    current_job_title: Optional[str] = None
    professional_summary: Optional[str] = None
    total_experience_years: Optional[float] = 0
    skills: List[str] = Field(default_factory=list)
    experiences: List[Dict[str, Any]] = Field(default_factory=list)
    projects: List[Dict[str, Any]] = Field(default_factory=list)
    educations: List[Dict[str, Any]] = Field(default_factory=list)
    certifications: List[Dict[str, Any]] = Field(default_factory=list)
    raw_text: Optional[str] = None


class JobRankCandidatesRequest(BaseModel):
    job_description: str
    candidates: List[JobRankingCandidate] = Field(default_factory=list)

def _allocate_thread_id() -> str:
    conn = get_conn()
    cur = conn.cursor()
    cur.execute("SELECT MAX(id) FROM chat_threads")
    row = cur.fetchone()
    cur.close()
    conn.close()
    max_id = row[0] if row and row[0] else 0
    return f"thread_{max_id + 1}"


@app.get("/chat/new-thread")
def new_thread():
    return {"thread_id": _allocate_thread_id()}


@app.post("/chat")
def chat(req: ChatRequest):
    try:
        reply = run_chat(req.thread_id, req.message)
        return {"status": "success", "thread_id": req.thread_id, "reply": reply}
    except Exception as e:
        return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})


@app.delete("/chat/{thread_id}")
def delete_thread(thread_id: str):
    clear_thread(thread_id)
    return {"status": "success", "thread_id": thread_id}


# ─── Document Loading ────────────────────────────────────────────────────────

def load_document(file_path: str, suffix: str):
    if suffix == ".pdf":
        loader = PyPDFLoader(file_path)
    elif suffix == ".docx":
        loader = Docx2txtLoader(file_path)
    else:
        raise ValueError("Only PDF and DOCX files are supported.")
    return loader.load()


def get_raw_text(documents) -> str:
    pages = []

    for doc in documents:
        page_text = doc.page_content or ""
        page_text = page_text.replace("\x00", "")
        page_text = clean_text(page_text)

        if page_text:
            pages.append(page_text)

    return clean_text("\n\n".join(pages))


def limit_text(raw_text: str, max_chars: int = 2000) -> str:
    return raw_text[:max_chars] if len(raw_text) > max_chars else raw_text

def clean_text(text: str) -> str:
    if not text:
        return ""

    text = unicodedata.normalize("NFKC", text)

    replacements = {
        "\uFFFD": "",
        "\u00A0": " ",
        "–": "-",
        "—": "-",
        "−": "-",
        "•": "\n- ",
        "▪": "\n- ",
        "➢": "\n- ",
        "→": "\n- ",
        "●": "\n- ",
        "“": '"',
        "”": '"',
        "‘": "'",
        "’": "'",
    }

    for old, new in replacements.items():
        text = text.replace(old, new)

    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{4,}", "\n\n\n", text)

    return text.strip()

# ─── Section Extractor ───────────────────────────────────────────────────────

def extract_section(text: str, section_names: list, next_sections: list = None) -> str:
    if next_sections is None:
        next_sections = ALL_SECTIONS

    next_sections = [s for s in next_sections if s not in section_names]

    section_pattern = "|".join(re.escape(s) for s in section_names)
    next_pattern = "|".join(re.escape(s) for s in next_sections)

    pattern = (
        r"(?:^|\n)\s*(?:" + section_pattern + r")\s*[:\-–]?\s*\n"
        r"(.*?)"
        r"(?=\n\s*(?:" + next_pattern + r")\s*[:\-–]?\s*\n|$)"
    )

    match = re.search(pattern, text, re.DOTALL | re.IGNORECASE)
    return clean_text(match.group(1)) if match else ""
# ─── Personal Info ───────────────────────────────────────────────────────────

def extract_email(text: str):
    match = re.search(r"[\w\.\+\-]+@[\w\.\-]+\.\w{2,}", text)
    return match.group(0) if match else None


def extract_phone(text: str):
    match = re.search(r"(\+?6?0?1[\d\s\-]{8,12}\d|\+?[\d][\d\s\-\(\)]{7,}\d)", text)
    return match.group(0).strip() if match else None


def extract_link(text: str, domain: str):
    pattern = r"(https?://)?(www\.)?" + re.escape(domain) + r"/[\w\-\./\?=&%#@]+"
    match = re.search(pattern, text, re.IGNORECASE)
    return match.group(0) if match else None


def extract_possible_name(text: str):
    skip_keywords = {
        "curriculum", "vitae", "resume", "cv", "profile", "contact",
        "summary", "objective", "education", "experience", "skills",
        "projects", "certifications", "references", "languages", "achievements",
        "page", "email", "phone", "address", "linkedin", "github",
        "volunteer", "publications", "hobbies", "interests", "competitions",
    }
    for line in text.splitlines()[:30]:
        line = line.strip()
        if not line:
            continue
        words = line.split()
        if 2 <= len(words) <= 5:
            if all(w[0].isupper() and w.replace("-", "").replace("'", "").isalpha() for w in words):
                if not any(w.lower() in skip_keywords for w in words):
                    return line
    return None


def extract_location(text: str):
    patterns = [
        r"(?:Location|Address|Based in|City|Hometown)[:\s]+([A-Za-z\s,]+?)(?:\n|$)",
        r"\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)*,\s*(?:Malaysia|Singapore|Indonesia|Thailand|Philippines|USA|UK|Australia|Canada))\b",
        r"\b(Kuala Lumpur|Petaling Jaya|Shah Alam|Subang|Cyberjaya|Putrajaya|Penang|Johor Bahru|Selangor|Kota Kinabalu|Melaka|Ipoh|Kuching|Kota Bharu|Alor Setar)\b",
    ]
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            return match.group(1).strip()
    return None


def extract_date_of_birth(text: str):
    patterns = [
        r"(?:Date of Birth|DOB|Born)[:\s]+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})",
        r"(?:Date of Birth|DOB|Born)[:\s]+(\d{1,2}\s+\w+\s+\d{4})",
        r"(?:Date of Birth|DOB|Born)[:\s]+(\w+\s+\d{1,2},?\s+\d{4})",
    ]
    for p in patterns:
        m = re.search(p, text, re.IGNORECASE)
        if m:
            return m.group(1).strip()
    return None


def extract_gender(text: str):
    m = re.search(r"(?:Gender|Sex)[:\s]+(Male|Female|Other)", text, re.IGNORECASE)
    return m.group(1).title() if m else None


def extract_nationality(text: str):
    m = re.search(r"(?:Nationality|Citizenship)[:\s]+([A-Za-z\s]+?)(?:\n|$)", text, re.IGNORECASE)
    if m:
        return m.group(1).strip()
    # common nationalities
    for nat in ["Malaysian", "Singaporean", "Indonesian", "Thai", "Filipino", "American", "British", "Australian"]:
        if re.search(r"\b" + nat + r"\b", text, re.IGNORECASE):
            return nat
    return None


def extract_religion(text: str):
    m = re.search(r"(?:Religion)[:\s]+([A-Za-z\s]+?)(?:\n|$)", text, re.IGNORECASE)
    if m:
        return m.group(1).strip()
    for rel in ["Islam", "Muslim", "Christian", "Buddhist", "Hindu", "Catholic"]:
        if re.search(r"\b" + rel + r"\b", text, re.IGNORECASE):
            return rel
    return None


def extract_marital_status(text: str):
    m = re.search(r"(?:Marital Status|Marital)[:\s]+(Single|Married|Divorced|Widowed)", text, re.IGNORECASE)
    return m.group(1).title() if m else None


def extract_driving_license(text: str):
    patterns = [
        r"(?:Driving Licen[cs]e|Driving|License)[:\s]+([A-Z0-9,\s]+?)(?:\n|$)",
        r"\b(?:Valid\s+)?(?:Driving\s+)?Licen[cs]e[:\s]+([A-Z0-9,\s]+?)(?:\n|$)",
        r"\b(Class\s+[A-Z0-9]+(?:\s*,\s*Class\s*[A-Z0-9]+)*)\b",
    ]
    for p in patterns:
        m = re.search(p, text, re.IGNORECASE)
        if m:
            return m.group(1).strip()
    # detect license class letters common in Malaysia
    m = re.search(r"\bLicen[cs]e\b[^:]*\b([BDEGbdeg]\d?(?:[,\s]+[BDEGbdeg]\d?)*)\b", text, re.IGNORECASE)
    if m:
        return m.group(1).strip()
    if re.search(r"\b(driving licen[cs]e|valid licen[cs]e|own transport)\b", text, re.IGNORECASE):
        return "Valid"
    return None


def extract_expected_salary(text: str):
    m = re.search(
        r"(?:Expected Salary|Salary Expectation|Expected Pay|Desired Salary)[:\s]+(RM\s?[\d,]+(?:\s*[-–]\s*RM\s?[\d,]+)?(?:\s*/\s*month)?)",
        text, re.IGNORECASE
    )
    if m:
        return m.group(1).strip()
    m = re.search(
        r"(?:Expected Salary|Salary Expectation)[:\s]+([\d,]+(?:\s*[-–]\s*[\d,]+)?(?:\s*/\s*month)?)",
        text, re.IGNORECASE
    )
    return m.group(1).strip() if m else None


def extract_notice_period(text: str):
    m = re.search(
        r"(?:Notice Period|Available in|Availability)[:\s]+([^\n]+)",
        text, re.IGNORECASE
    )
    return m.group(1).strip() if m else None


def extract_willing_to_relocate(text: str):
    if re.search(r"\b(willing to relocate|open to relocation|available to relocate)\b", text, re.IGNORECASE):
        return True
    if re.search(r"\b(not willing to relocate|unable to relocate)\b", text, re.IGNORECASE):
        return False
    return None

def phrase_exists(phrase: str, text: str) -> bool:
    if not phrase or not text:
        return False

    phrase = phrase.strip()
    pattern = r"(?<![A-Za-z0-9])" + re.escape(phrase) + r"(?![A-Za-z0-9])"

    return re.search(pattern, text, re.IGNORECASE) is not None
# ─── Skills ──────────────────────────────────────────────────────────────────

def extract_known_skills(text: str):
    technical = [
        "Python", "Java", "PHP", "Laravel", "MySQL", "SQL", "PostgreSQL",
        "React", "Next.js", "JavaScript", "TypeScript", "HTML", "CSS",
        "FastAPI", "Flask", "Django", "Git", "Docker", "AWS", "Azure",
        "Selenium", "Playwright", "Appium", "Firebase", "Flutter",
        "Node.js", "MongoDB", "Redis", "Kubernetes", "GraphQL", "REST API",
        "Spring Boot", "C\\+\\+", "C#", "Go", "Rust", "Swift", "Kotlin",
        "TensorFlow", "PyTorch", "Pandas", "NumPy", "Scikit-learn",
        "Vue.js", "Angular", "Bootstrap", "Tailwind", "jQuery",
        "Linux", "Ubuntu", "Bash", "Shell", "CI/CD", "GitHub Actions",
        "Figma", "Adobe XD", "Postman", "JIRA", "Confluence",
        "Wordpress", "Shopify", "Magento", "SAP", "Salesforce",
        "Unity", "Unreal Engine", "Blender", "AutoCAD", "MATLAB",
        "R", "Scala", "Perl", "Assembly", "Dart","API Integration", "RESTful API", "Microservices", "Authentication",
        "Authorization", "WebSocket", "Laravel Reverb", "Queue", "Redis Queue",
        "FastAPI", "Ollama", "LangChain", "LangGraph", "OpenAI API",
        "Prompt Engineering", "RAG", "Vector Database", "OCR",
    ]
    soft = [
        "Communication", "Leadership", "Problem Solving", "Teamwork",
        "Critical Thinking", "Time Management", "Adaptability",
        "Creativity", "Collaboration", "Presentation", "Negotiation",
        "Decision Making", "Multitasking", "Analytical", "Interpersonal",
        "Self-motivated", "Detail-oriented", "Fast Learner",
    ]
    analytics = [
        "Power BI", "Tableau", "Excel", "VBA", "Power Automate",
        "Machine Learning", "Deep Learning", "Data Analysis", "Data Analytics",
        "Data Science", "NLP", "Computer Vision", "Big Data", "Hadoop", "Spark",
        "Google Analytics", "Looker", "Metabase", "Grafana","Classification", "Regression", "Random Forest", "Logistic Regression",
        "Decision Tree", "SVM", "KNN", "Model Evaluation", "Confusion Matrix",
        "Feature Engineering", "Data Cleaning", "Data Visualization",
    ]
    prog_langs = [
        "Python", "Java", "PHP", "JavaScript", "TypeScript",
        "C#", "Go", "Rust", "Swift", "Kotlin", "Ruby", "Scala",
        "R", "MATLAB", "Dart", "Perl", "Bash", "Shell",
    ]
    databases = [
        "MySQL", "PostgreSQL", "MongoDB", "Redis", "SQLite",
        "Oracle", "MSSQL", "DynamoDB", "Firestore", "Cassandra",
        "MariaDB", "CouchDB", "Neo4j", "InfluxDB",
    ]
    frameworks = [
        "Laravel", "Django", "Flask", "FastAPI", "Spring Boot",
        "React", "Vue.js", "Angular", "Next.js", "Express.js",
        "Flutter", "Bootstrap", "Tailwind", "Symfony", "CodeIgniter",
        "Ruby on Rails", "ASP.NET", "NestJS", "Nuxt.js",
    ]
    tools = [
        "Git", "Docker", "Kubernetes", "AWS", "Azure", "Firebase",
        "Figma", "Postman", "JIRA", "Linux", "GitHub Actions", "CI/CD",
        "Heroku", "Vercel", "Netlify", "Nginx", "Apache",
        "Webpack", "Vite", "npm", "yarn", "pip",
        "VS Code", "IntelliJ", "PyCharm", "Eclipse", "Xcode",
        "Slack", "Trello", "Notion", "Asana","Composer", "Artisan", "XAMPP", "MySQL Workbench", "Postman",
        "VS Code", "Android Studio", "Firebase Console",
    ]

    def find(lst):
        return sorted(set(
            s.replace("\\+\\+", "++") for s in lst
            if re.search(r"\b" + s + r"\b", text, re.IGNORECASE)
        ))

    return {
        "technical_skills":      find(technical),
        "soft_skills":           find(soft),
        "analytics_tools":       find(analytics),
        "programming_languages": find(prog_langs),
        "databases":             find(databases),
        "frameworks":            find(frameworks),
        "tools":                 find(tools),
    }


# ─── Education ───────────────────────────────────────────────────────────────

def extract_education(text: str) -> list:
    degree_keywords = [
        "bachelor", "master", "phd", "diploma", "degree",
        "b.sc", "m.sc", "b.eng", "m.eng", "bcs", "mcs",
        "b.tech", "m.tech", "stpm", "spm", "foundation",
        "university", "college", "institute", "polytechnic",
        "uitm", "utm", "upm", "uniten", "tarc", "apu", "mmu",
        "um", "usm", "ukm", "utem", "unimas", "upsi", "uum",
    ]

    cgpa_pattern = re.compile(
        r"(?:cgpa|gpa|pointer|result)[:\s]*([0-9]\.[0-9]{1,2})(?:\s*/\s*([0-9]\.[0-9]{1,2}))?",
        re.IGNORECASE
    )
    year_pattern  = re.compile(r"\b(19|20)\d{2}\b")
    degree_names  = re.compile(
        r"(bachelor[^\n]*|master[^\n]*|phd[^\n]*|doctor[^\n]*|diploma[^\n]*|"
        r"foundation[^\n]*|b\.sc[^\n]*|m\.sc[^\n]*|b\.eng[^\n]*|m\.eng[^\n]*)",
        re.IGNORECASE
    )
    honours_pattern = re.compile(
        r"(first class|second class upper|second class lower|third class|"
        r"distinction|merit|pass|dean['\s]?s list|with honours)",
        re.IGNORECASE
    )

    section_text = extract_section(text, [
        "Education", "Academic Background", "Academic Qualifications", "Educational Background"
    ])

    if not section_text:
        section_text = text

    results = []
    blocks  = re.split(r"\n{2,}", section_text)

    for block in blocks:
        block = block.strip()
        if not block:
            continue
        lower = block.lower()
        if not any(kw in lower for kw in degree_keywords):
            continue

        years     = year_pattern.findall(block)
        cgpa_m    = cgpa_pattern.search(block)
        degree_m  = degree_names.search(block)
        honours_m = honours_pattern.search(block)

        lines = [l.strip() for l in block.splitlines() if l.strip()]

        institution = None
        degree_str  = None
        field       = None

        if degree_m:
            degree_str = degree_m.group(0).strip()

        for line in lines:
            ll = line.lower()
            if any(kw in ll for kw in [
                "university", "college", "institute", "polytechnic",
                "uitm", "utm", "upm", "tarc", "apu", "mmu", "uniten",
                "um", "usm", "ukm", "utem", "unimas",
            ]):
                institution = line
                break

        if not institution and lines:
            institution = lines[0]

        # Try to extract field of study
        field_m = re.search(
            r"(?:in|of)\s+(Computer Science|Information Technology|Software Engineering|"
            r"Electrical Engineering|Mechanical Engineering|Business|Accounting|"
            r"Finance|Marketing|Data Science|Artificial Intelligence|[A-Z][a-z]+(?:\s[A-Z][a-z]+){0,3})",
            block, re.IGNORECASE
        )
        if field_m:
            field = field_m.group(1).strip()

        # Coursework
        coursework = []
        cw_m = re.search(r"(?:Relevant Coursework|Courses)[:\s]+([^\n]+(?:\n[^\n]+){0,3})", block, re.IGNORECASE)
        if cw_m:
            coursework = [c.strip() for c in re.split(r"[,•\|]", cw_m.group(1)) if c.strip()]

        results.append({
            "institution":         institution,
            "degree":              degree_str,
            "field_of_study":      field,
            "start_year":          int(years[0]) if len(years) >= 2 else None,
            "end_year":            int(years[1]) if len(years) >= 2 else (int(years[0]) if years else None),
            "cgpa":                float(cgpa_m.group(1)) if cgpa_m else None,
            "cgpa_scale":          float(cgpa_m.group(2)) if cgpa_m and cgpa_m.group(2) else 4.0,
            "honours":             honours_m.group(1).title() if honours_m else None,
            "relevant_coursework": coursework,
        })

    return results[:6]

MONTH_MAP = {
    "jan": 1, "january": 1,
    "feb": 2, "february": 2,
    "mar": 3, "march": 3,
    "apr": 4, "april": 4,
    "may": 5,
    "jun": 6, "june": 6,
    "jul": 7, "july": 7,
    "aug": 8, "august": 8,
    "sep": 9, "sept": 9, "september": 9,
    "oct": 10, "october": 10,
    "nov": 11, "november": 11,
    "dec": 12, "december": 12,
}


def parse_resume_date(value: str):
    if not value:
        return None

    value = value.strip().lower().replace(",", "")

    if value in ["present", "current", "now"]:
        return datetime.today()

    # Example: April 2026
    m = re.search(r"([a-z]+)\s+(\d{4})", value)
    if m:
        month_name = m.group(1)
        year = int(m.group(2))
        month = MONTH_MAP.get(month_name[:3]) or MONTH_MAP.get(month_name)

        if month:
            return datetime(year, month, 1)

    # Example: 04/2026 or 04-2026
    m = re.search(r"(\d{1,2})[\/\-](\d{4})", value)
    if m:
        month = int(m.group(1))
        year = int(m.group(2))

        if 1 <= month <= 12:
            return datetime(year, month, 1)

    # Example: 2026
    m = re.search(r"\b(19|20)\d{2}\b", value)
    if m:
        year = int(m.group(0))
        return datetime(year, 1, 1)

    return None


def calculate_duration_months(start_date: str, end_date: str) -> int:
    start = parse_resume_date(start_date)
    end = parse_resume_date(end_date)

    if not start or not end:
        return 0

    months = (end.year - start.year) * 12 + (end.month - start.month)

    # Count same-month role as 1 month.
    if months <= 0:
        return 1

    return months

# ─── Experience ───────────────────────────────────────────────────────────────

def extract_experiences(text: str) -> list:
    section_text = extract_section(text, [
        "Experience", "Work Experience", "Employment History",
        "Professional Experience", "Career History", "Work History",
    ])

    if not section_text:
        return []

    date_pattern = re.compile(
        r"((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s,]*\d{4}"
        r"|\d{1,2}[\/\-]\d{4}|\d{4})\s*[-–—to]+\s*"
        r"((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s,]*\d{4}"
        r"|Present|Current|Now|\d{1,2}[\/\-]\d{4}|\d{4})",
        re.IGNORECASE
    )
    emp_type_pattern = re.compile(
        r"\b(full[\s-]?time|part[\s-]?time|internship|intern|contract|freelance|remote|hybrid)\b",
        re.IGNORECASE
    )

    blocks  = re.split(r"\n{2,}", section_text)
    results = []

    for block in blocks:
        block = block.strip()
        if len(block) < 20:
            continue

        lines   = [l.strip() for l in block.splitlines() if l.strip()]
        date_m  = date_pattern.search(block)
        emp_m   = emp_type_pattern.search(block)

        start_date = date_m.group(1) if date_m else None
        end_date = date_m.group(2) if date_m else None
        is_current = bool(re.search(r"\b(present|current|now)\b", end_date or "", re.IGNORECASE))
        duration_months = calculate_duration_months(start_date, end_date)

        bullets = re.findall(r"(?:^|\n)\s*[•\-\*▪➢→]\s*(.+)", block)
        if not bullets:
            bullets = [l for l in lines[2:] if len(l) > 20]

        company   = None
        job_title = None

        if lines:
            job_title = lines[0]
        if len(lines) > 1:
            company = lines[1]

        if not bullets and not date_m:
            continue

        # Extract tools used from bullets
        tools_used = []
        tools_list = ["Python", "Java", "PHP", "Laravel", "MySQL", "React",
                      "JavaScript", "Docker", "AWS", "Git", "Flask", "Django"]
        for tool in tools_list:
            if re.search(r"\b" + re.escape(tool) + r"\b", block, re.IGNORECASE):
                tools_used.append(tool)

        results.append({
            "company_name":     company,
            "job_title":        job_title,
            "employment_type":  emp_m.group(1).title() if emp_m else None,
            "department":       None,
            "location":         None,
            "start_date":       start_date,
            "end_date":         end_date,
            "is_current":       is_current,
            "duration_months":  duration_months,
            "responsibilities": bullets[:10],
            "achievements":     [],
            "tools_used":       tools_used,
            "industry":         None,
        })

    return results


# ─── Projects ────────────────────────────────────────────────────────────────

def extract_projects(text: str) -> list:
    section_text = extract_section(text, [
        "Projects", "Personal Projects", "Academic Projects",
        "Portfolio", "Side Projects", "Key Projects",
    ])

    if not section_text:
        return []

    blocks  = re.split(r"\n{2,}", section_text)
    results = []

    for block in blocks:
        block = block.strip()
        if len(block) < 15:
            continue

        lines  = [l.strip() for l in block.splitlines() if l.strip()]
        bullets = re.findall(r"(?:^|\n)\s*[•\-\*▪➢→]\s*(.+)", block)

        # Technologies
        tech_m = re.search(
            r"(?:tech(?:nologies|nology|nical stack|stack)?|built with|using|tools?|developed with)[:\s]+([^\n]+)",
            block, re.IGNORECASE
        )
        technologies = []
        if tech_m:
            technologies = [t.strip() for t in re.split(r"[,|/•]", tech_m.group(1)) if t.strip()]

        # Project type
        proj_type = None
        if re.search(r"\b(web|website|web app)\b", block, re.IGNORECASE):
            proj_type = "Web Application"
        elif re.search(r"\b(mobile|android|ios|flutter)\b", block, re.IGNORECASE):
            proj_type = "Mobile Application"
        elif re.search(r"\b(machine learning|ml|ai|deep learning|nlp)\b", block, re.IGNORECASE):
            proj_type = "AI/ML"
        elif re.search(r"\b(api|backend|rest)\b", block, re.IGNORECASE):
            proj_type = "Backend/API"
        elif re.search(r"\b(data|analytics|dashboard)\b", block, re.IGNORECASE):
            proj_type = "Data Analytics"

        # URL/link
        url_m = re.search(r"(https?://[^\s]+)", block)
        url   = url_m.group(1) if url_m else None

        # Year
        year_m = re.search(r"\b(20\d{2})\b", block)

        # Role
        role_m = re.search(r"(?:Role|Position|As)[:\s]+([^\n]+)", block, re.IGNORECASE)

        description = " ".join(bullets) if bullets else (lines[1] if len(lines) > 1 else None)

        results.append({
            "project_name": lines[0] if lines else None,
            "project_type": proj_type,
            "description":  description,
            "technologies": technologies,
            "role":         role_m.group(1).strip() if role_m else None,
            "outcome":      None,
            "url":          url,
            "year":         int(year_m.group(1)) if year_m else None,
        })

    return results


# ─── Certifications ──────────────────────────────────────────────────────────

def extract_certifications(text: str) -> list:
    section_text = extract_section(text, [
        "Certifications", "Certificates", "Licenses",
        "Professional Development", "Courses", "Online Courses",
    ])

    if not section_text:
        return []

    year_pattern = re.compile(r"\b(20\d{2})\b")
    url_pattern  = re.compile(r"(https?://[^\s]+)")
    results      = []
    lines        = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line  = re.sub(r"^[•\-\*▪]\s*", "", line)
        if len(line) < 5:
            continue

        year_m  = year_pattern.search(line)
        url_m   = url_pattern.search(line)

        # Issuer: look for "by X" or "- X" or "| X"
        issuer_m = re.search(r"(?:by|from|–|-|\|)\s+([A-Z][^\n,\|]{2,40})(?:,|\||$)", line)

        results.append({
            "name":            re.sub(r"\s*[-–|]\s*.+$", "", line).strip(),
            "issuer":          issuer_m.group(1).strip() if issuer_m else None,
            "date_issued":     year_m.group(1) if year_m else None,
            "expiry_date":     None,
            "credential_link": url_m.group(1) if url_m else None,
        })

    return results


# ─── Achievements ─────────────────────────────────────────────────────────────

def extract_achievements(text: str) -> list:
    section_text = extract_section(text, [
        "Achievements", "Awards", "Honors", "Honours",
        "Recognition", "Accomplishments", "Academic Achievements",
    ])

    if not section_text:
        return []

    year_pattern = re.compile(r"\b(20\d{2}|19\d{2})\b")
    results      = []
    lines        = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line = re.sub(r"^[•\-\*▪]\s*", "", line)
        if len(line) < 5:
            continue

        year_m = year_pattern.search(line)

        # Organizer
        org_m = re.search(r"(?:by|from|organized by|–|-)\s+([A-Z][^\n,]{2,40})(?:,|$)", line)

        # Position
        pos_m = re.search(r"\b(1st|2nd|3rd|first|second|third|winner|runner[\s-]?up|champion|gold|silver|bronze)\b", line, re.IGNORECASE)

        results.append({
            "title":        line,
            "description":  None,
            "year":         int(year_m.group(1)) if year_m else None,
            "organization": org_m.group(1).strip() if org_m else None,
            "position":     pos_m.group(1).title() if pos_m else None,
        })

    return results


# ─── Languages ───────────────────────────────────────────────────────────────

def extract_languages(text: str) -> list:
    section_text = extract_section(text, [
        "Languages", "Language Skills", "Language Proficiency", "Language"
    ])

    known_langs = [
        "English", "Malay", "Bahasa Malaysia", "Bahasa Melayu",
        "Mandarin", "Chinese", "Tamil", "Arabic", "French",
        "Japanese", "Korean", "Spanish", "German", "Hindi",
    ]

    if not section_text:
        found = []
        for lang in known_langs:
            if re.search(r"\b" + re.escape(lang) + r"\b", text, re.IGNORECASE):
                prof_m = re.search(
                    re.escape(lang) + r"[:\s\-–]*(native|fluent|proficient|intermediate|basic|conversational|advanced|elementary)",
                    text, re.IGNORECASE
                )
                found.append({
                    "language":    lang,
                    "proficiency": prof_m.group(1).title() if prof_m else None,
                })
        return found

    results = []
    lines   = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line   = re.sub(r"^[•\-\*▪]\s*", "", line)
        prof_m = re.search(
            r"(native|fluent|proficient|intermediate|basic|conversational|advanced|elementary|mother tongue)",
            line, re.IGNORECASE
        )
        lang_part = re.sub(
            r"\s*[-–:,|]\s*(native|fluent|proficient|intermediate|basic|conversational|advanced|elementary|mother tongue).*",
            "", line, flags=re.IGNORECASE
        ).strip()

        if lang_part and len(lang_part) > 1:
            results.append({
                "language":    lang_part,
                "proficiency": prof_m.group(1).title() if prof_m else None,
            })

    return results


# ─── Volunteer ───────────────────────────────────────────────────────────────

def extract_volunteer(text: str) -> list:
    section_text = extract_section(text, [
        "Volunteer", "Volunteering", "Community Service",
        "Social Work", "Voluntary Work", "Community Involvement",
    ])

    if not section_text:
        return []

    blocks  = re.split(r"\n{2,}", section_text)
    results = []

    for block in blocks:
        block = block.strip()
        if len(block) < 10:
            continue

        lines      = [l.strip() for l in block.splitlines() if l.strip()]
        year_m     = re.search(r"\b(20\d{2}|19\d{2})\b", block)
        date_m     = re.search(
            r"((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s,]*\d{4}|\d{4})"
            r"\s*[-–to]*\s*"
            r"((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s,]*\d{4}|Present|\d{4})?",
            block, re.IGNORECASE
        )
        bullets    = re.findall(r"(?:^|\n)\s*[•\-\*▪]\s*(.+)", block)
        description = " ".join(bullets) if bullets else (lines[1] if len(lines) > 1 else None)

        results.append({
            "organization": lines[1] if len(lines) > 1 else None,
            "role":         lines[0] if lines else None,
            "start_date":   date_m.group(1) if date_m else None,
            "end_date":     date_m.group(2) if date_m and date_m.group(2) else None,
            "description":  description,
        })

    return results


# ─── Publications ────────────────────────────────────────────────────────────

def extract_publications(text: str) -> list:
    section_text = extract_section(text, [
        "Publications", "Research", "Papers",
        "Research Papers", "Journal Articles", "Conference Papers",
    ])

    if not section_text:
        return []

    results = []
    lines   = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line = re.sub(r"^[•\-\*▪\d\.]\s*", "", line)
        if len(line) < 10:
            continue
        year_m = re.search(r"\b(20\d{2}|19\d{2})\b", line)
        results.append({
            "title":       line,
            "journal":     None,
            "year":        int(year_m.group(1)) if year_m else None,
            "co_authors":  [],
        })

    return results


# ─── Competitions / Hackathons ────────────────────────────────────────────────

def extract_competitions(text: str) -> list:
    section_text = extract_section(text, [
        "Competitions", "Hackathons", "Contests",
        "Competition", "Hackathon", "Coding Competitions",
    ])

    if not section_text:
        return []

    results = []
    lines   = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line = re.sub(r"^[•\-\*▪]\s*", "", line)
        if len(line) < 5:
            continue
        year_m = re.search(r"\b(20\d{2}|19\d{2})\b", line)
        pos_m  = re.search(
            r"\b(1st|2nd|3rd|first|second|third|winner|runner[\s-]?up|champion|finalist|top \d+)\b",
            line, re.IGNORECASE
        )
        results.append({
            "event_name": line,
            "position":   pos_m.group(1).title() if pos_m else None,
            "year":       int(year_m.group(1)) if year_m else None,
            "organizer":  None,
        })

    return results


# ─── Extracurricular ─────────────────────────────────────────────────────────

def extract_extracurricular(text: str) -> list:
    section_text = extract_section(text, [
        "Extracurricular", "Extracurricular Activities",
        "Co-curricular", "Co-curricular Activities",
        "Club", "Clubs & Societies", "Student Activities",
    ])

    if not section_text:
        return []

    results = []
    lines   = [l.strip() for l in section_text.splitlines() if l.strip()]

    for line in lines:
        line = re.sub(r"^[•\-\*▪]\s*", "", line)
        if len(line) < 3:
            continue
        year_m = re.search(r"\b(20\d{2}|19\d{2})\b", line)
        role_m = re.search(r"(?:as|role|position)[:\s]+([^\n,]+)", line, re.IGNORECASE)
        results.append({
            "activity": line,
            "role":     role_m.group(1).strip() if role_m else None,
            "year":     int(year_m.group(1)) if year_m else None,
        })

    return results


# ─── Hobbies ─────────────────────────────────────────────────────────────────

def extract_hobbies(text: str) -> list:
    section_text = extract_section(text, [
        "Hobbies", "Interests", "Hobbies & Interests",
        "Personal Interests", "Activities",
    ])

    if not section_text:
        return []

    items = re.split(r"[,•\|\n]", section_text)
    return [
        re.sub(r"^[\-\*▪\s]+", "", i).strip()
        for i in items
        if re.sub(r"^[\-\*▪\s]+", "", i).strip() and len(re.sub(r"^[\-\*▪\s]+", "", i).strip()) > 2
    ]


# ─── References ──────────────────────────────────────────────────────────────

def extract_references(text: str) -> list:
    section_text = extract_section(text, ["References", "Referees"])

    if not section_text:
        if re.search(r"\b(references available upon request|references on request)\b", text, re.IGNORECASE):
            return [{"note": "Available upon request"}]
        return []

    blocks  = re.split(r"\n{2,}", section_text)
    results = []

    for block in blocks:
        block = block.strip()
        if len(block) < 5:
            continue

        lines   = [l.strip() for l in block.splitlines() if l.strip()]
        email_m = re.search(r"[\w\.\+\-]+@[\w\.\-]+\.\w{2,}", block)
        phone_m = re.search(r"(\+?[\d][\d\s\-\(\)]{7,}\d)", block)

        results.append({
            "name":      lines[0] if lines else None,
            "job_title": lines[1] if len(lines) > 1 else None,
            "company":   lines[2] if len(lines) > 2 else None,
            "email":     email_m.group(0) if email_m else None,
            "phone":     phone_m.group(0).strip() if phone_m else None,
        })

    return results


# ─── Summary & Job Title ─────────────────────────────────────────────────────

def extract_summary(text: str):
    section_text = extract_section(text, [
        "Summary", "Professional Summary", "Profile", "About Me",
        "Objective", "Career Objective", "Personal Statement", "About",
    ])
    if section_text and len(section_text) > 20:
        return section_text[:1000]
    return None


def extract_current_job(text: str):
    patterns = [
        r"(?:Current(?:ly)?|Present)\s*[:\-–]?\s*([^\n]+)",
        r"(?:Position|Role|Title)[:\s]+([^\n]+)",
    ]
    for p in patterns:
        m = re.search(p, text, re.IGNORECASE)
        if m:
            return m.group(1).strip()
    return None


def extract_total_experience(experiences: list):
    if not experiences:
        return None, None
    total_months = sum(e.get("duration_months") or 0 for e in experiences)
    if total_months == 0:
        return None, None
    return total_months, round(total_months / 12, 1)


# ─── LLM Assist (focused single-field only) ──────────────────────────────────

def llm_extract_field(raw_text: str, field: str, instruction: str):
    context = limit_text(raw_text, max_chars=2000)
    try:
        llm = ChatOllama(model=OLLAMA_MODEL, base_url=OLLAMA_URL, temperature=0)
        prompt = (
            f"From the CV text below, extract ONLY the {field}.\n"
            f"{instruction}\n"
            f"Reply with just the value, nothing else. If not found, reply: null\n\n"
            f"CV TEXT:\n{context}"
        )
        response = llm.invoke([
            {"role": "system", "content": "You are a CV data extractor. Reply with only the requested value."},
            {"role": "user",   "content": prompt},
        ])
        result = response.content.strip()
        if result.lower() in ("null", "none", "", "not found", "n/a"):
            return None
        return result
    except Exception:
        return None


# ─── Main Build ──────────────────────────────────────────────────────────────

def build_result(file_name: str, raw_text: str) -> dict:
    email             = extract_email(raw_text)
    phone             = extract_phone(raw_text)
    name              = extract_possible_name(raw_text)
    location          = extract_location(raw_text)
    dob               = extract_date_of_birth(raw_text)
    gender            = extract_gender(raw_text)
    nationality       = extract_nationality(raw_text)
    religion          = extract_religion(raw_text)
    marital           = extract_marital_status(raw_text)
    driving_license   = extract_driving_license(raw_text)
    expected_salary   = extract_expected_salary(raw_text)
    notice_period     = extract_notice_period(raw_text)
    relocate          = extract_willing_to_relocate(raw_text)
    linkedin          = extract_link(raw_text, "linkedin.com")
    github            = extract_link(raw_text, "github.com")
    portfolio         = extract_link(raw_text, "behance.net") or extract_link(raw_text, "dribbble.com")
    website           = extract_link(raw_text, "notion.so") or extract_link(raw_text, "medium.com")
    skills            = extract_known_skills(raw_text)
    education         = extract_education(raw_text)
    exps              = extract_experiences(raw_text)
    projects          = extract_projects(raw_text)
    certs             = extract_certifications(raw_text)
    achiev            = extract_achievements(raw_text)
    langs             = extract_languages(raw_text)
    volunteer         = extract_volunteer(raw_text)
    publications      = extract_publications(raw_text)
    competitions      = extract_competitions(raw_text)
    extracurricular   = extract_extracurricular(raw_text)
    hobbies           = extract_hobbies(raw_text)
    references        = extract_references(raw_text)
    summary           = extract_summary(raw_text)
    job_title         = extract_current_job(raw_text)
    total_months, total_years = extract_total_experience(exps)

    # LLM only for fields regex can't reliably get
    if not name:
        name = llm_extract_field(raw_text, "candidate's full name",
                                  "Usually the largest text at the top of the CV.")
    if not summary:
        summary = llm_extract_field(raw_text, "professional summary or career objective",
                                     "A short paragraph at the top describing the candidate.")
    if not job_title and exps:
        job_title = exps[0].get("job_title")

    latest_company = exps[0].get("company_name") if exps else None
    classification = classify_candidate_from_parts(
    job_title=job_title,
    summary=summary,
    skills=skills,
    experiences=exps,
    education=education,
    projects=projects,
    raw_text=raw_text,
)

    return {
    "status":         "success",
    "parser_status":  "success",
    "parser_warning": None,
    "file_name":      file_name,
    "candidate": {
        "full_name":                    name,
        "email":                        email,
        "phone":                        phone,
        "location":                     location,
        "date_of_birth":                dob,
        "gender":                       gender,
        "nationality":                  nationality,
        "religion":                     religion,
        "marital_status":               marital,
        "driving_license":              driving_license,
        "expected_salary":              expected_salary,
        "notice_period":                notice_period,
        "willing_to_relocate":          relocate,
        "professional_summary":         summary,
        "current_job_title":            job_title,
        "latest_company":               latest_company,
        "total_experience_months":      total_months,
        "total_experience_years":       total_years,
        "internship_experience_months": None,
        "full_time_experience_months":  None,
        "candidate_category":           classification["category"],
    },
    "classification":  classification,
    "experiences":      exps,
    "education":        education,
    "skills":           skills,
    "projects":         projects,
    "certifications":   certs,
    "achievements":     achiev,
    "languages":        langs,
    "volunteer":        volunteer,
    "publications":     publications,
    "competitions":     competitions,
    "extracurricular":  extracurricular,
    "hobbies":          hobbies,
    "references":       references,
    "links": {
        "linkedin":  linkedin,
        "github":    github,
        "portfolio": portfolio,
        "website":   website,
    },
    "raw_text": raw_text,
}


# ─── Empty response helper ────────────────────────────────────────────────────

def empty_response(file_name: str, raw_text: str, warning: str):
    return {
        "status": "success", "parser_status": "failed",
        "parser_warning": warning, "file_name": file_name,
        "candidate": {}, "classification": {}, "experiences": [], "education": [],
        "skills": {}, "projects": [], "certifications": [],
        "achievements": [], "languages": [], "volunteer": [],
        "publications": [], "competitions": [], "extracurricular": [],
        "hobbies": [], "references": [], "links": {}, "raw_text": raw_text,
    }

# ─── Candidate Classification / Category Ranking ─────────────────────────────

VALID_CATEGORIES = ["IT", "Business", "Data", "Marketing", "Finance", "Operations"]

CATEGORY_KEYWORDS = {
    "IT": [
        "software", "developer", "programmer", "engineer", "backend", "frontend",
        "full stack", "web", "mobile", "api", "laravel", "php", "java", "javascript",
        "typescript", "react", "vue", "angular", "node", "fastapi", "django", "flask",
        "spring", "mysql", "postgresql", "mongodb", "firebase", "git", "docker",
        "cloud", "aws", "azure", "devops", "cybersecurity", "network", "system"
    ],
    "Business": [
        "business", "strategy", "sales", "client", "stakeholder", "account", "crm",
        "partnership", "market research", "business development", "consulting",
        "proposal", "commercial", "management", "administration"
    ],
    "Data": [
        "data", "analytics", "analysis", "analyst", "dashboard", "power bi",
        "tableau", "excel", "sql", "python", "pandas", "numpy", "machine learning",
        "statistics", "forecast", "visualization", "reporting", "etl", "big data",
        "data science", "data warehouse"
    ],
    "Marketing": [
        "marketing", "seo", "sem", "campaign", "content", "copywriting", "brand",
        "social media", "instagram", "tiktok", "facebook", "google ads", "email marketing",
        "digital marketing", "creative", "canva", "adobe", "public relations"
    ],
    "Finance": [
        "finance", "accounting", "audit", "tax", "budget", "forecasting", "financial",
        "invoice", "payable", "receivable", "reconciliation", "bookkeeping",
        "investment", "risk", "banking", "sap", "procurement"
    ],
    "Operations": [
        "operations", "supply chain", "logistics", "inventory", "warehouse", "process",
        "workflow", "order management", "customer service", "admin", "coordination",
        "quality", "planning", "scheduler", "fulfillment", "vendor"
    ],
}


def normalize_category(category: str) -> str:
    cleaned = (category or "").strip().lower()

    for valid in VALID_CATEGORIES:
        if cleaned == valid.lower():
            return valid

    return "Operations"


def flatten_skills(skills) -> list:
    flattened = []

    if isinstance(skills, dict):
        for values in skills.values():
            if isinstance(values, list):
                flattened.extend([str(v) for v in values if v])
            elif values:
                flattened.append(str(values))

    elif isinstance(skills, list):
        flattened.extend([str(v) for v in skills if v])

    return sorted(set(flattened))


def count_keyword_hits(text: str, keywords: list) -> list:
    text = (text or "").lower()
    hits = []

    for keyword in keywords:
        pattern = r"\b" + re.escape(keyword.lower()) + r"\b"

        if re.search(pattern, text):
            hits.append(keyword)

    return sorted(set(hits))


def classify_candidate_from_parts(job_title, summary, skills, experiences, education, projects, raw_text) -> dict:
    skill_list = flatten_skills(skills)

    experience_text = " ".join([
        " ".join([
            str(exp.get("job_title") or ""),
            str(exp.get("department") or ""),
            str(exp.get("industry") or ""),
            " ".join(exp.get("responsibilities") or []),
            " ".join(exp.get("achievements") or []),
            " ".join(exp.get("tools_used") or []),
        ])
        for exp in experiences or []
        if isinstance(exp, dict)
    ])

    education_text = " ".join([
        " ".join([
            str(edu.get("degree") or ""),
            str(edu.get("field_of_study") or ""),
            " ".join(edu.get("relevant_coursework") or []),
        ])
        for edu in education or []
        if isinstance(edu, dict)
    ])

    project_text = " ".join([
        " ".join([
            str(project.get("project_name") or ""),
            str(project.get("project_type") or ""),
            str(project.get("description") or ""),
            " ".join(project.get("technologies") or []),
        ])
        for project in projects or []
        if isinstance(project, dict)
    ])

    full_text = " ".join([
        str(job_title or ""),
        str(summary or ""),
        " ".join(skill_list),
        experience_text,
        education_text,
        project_text,
        limit_text(raw_text, 2500),
    ])

    category_scores = {}
    category_hits = {}

    for category, keywords in CATEGORY_KEYWORDS.items():
        hits = count_keyword_hits(full_text, keywords)
        category_hits[category] = hits
        category_scores[category] = len(hits)

    best_category = max(category_scores, key=category_scores.get)
    best_score = category_scores[best_category]
    total_hits = sum(category_scores.values())

    if best_score == 0:
        return {
            "category": "Operations",
            "confidence": 35.00,
            "reason": "No strong category-specific evidence was found, so the candidate was placed under Operations for manual review.",
        }

    confidence = round(min(95, max(45, (best_score / max(total_hits, 1)) * 100)), 2)
    top_hits = category_hits[best_category][:5]

    return {
        "category": best_category,
        "confidence": confidence,
        "reason": "Classified as " + best_category + " based on evidence such as: " + ", ".join(top_hits) + ".",
    }


def score_candidate_for_category(candidate: RankingCandidate, category: str):
    category = normalize_category(category)

    skill_text = " ".join(candidate.skills or [])
    title_text = candidate.current_job_title or ""
    summary_text = candidate.professional_summary or ""
    full_text = " ".join([skill_text, title_text, summary_text])

    hits = count_keyword_hits(full_text, CATEGORY_KEYWORDS[category])
    title_hits = count_keyword_hits(title_text, CATEGORY_KEYWORDS[category])
    summary_hits = count_keyword_hits(summary_text, CATEGORY_KEYWORDS[category])

    experience_years = float(candidate.total_experience_years or 0)

    skill_score = min(55, len(hits) * 8)
    experience_score = min(20, experience_years * 4)
    title_score = 15 if title_hits else 0
    summary_score = min(10, len(summary_hits) * 3)

    score = round(min(100, skill_score + experience_score + title_score + summary_score), 2)

    if hits:
        reason = f"Strong {category} fit based on {', '.join(hits[:5])}. Experience detected: {experience_years:g} years."
    else:
        reason = f"Ranked lower because limited direct {category} evidence was detected. Experience detected: {experience_years:g} years."

    return score, reason

# ─── Job Description Based Math Matching ─────────────────────────────────────

EXTRA_MATCHING_SKILLS = [
    "python", "sql", "power bi", "excel", "tableau", "dashboard", "data cleaning",
    "data analysis", "data analytics", "machine learning", "pandas", "numpy",
    "laravel", "php", "mysql", "javascript", "typescript", "react", "vue",
    "html", "css", "fastapi", "django", "flask", "api", "rest api", "git",
    "docker", "aws", "azure", "firebase", "figma", "canva", "seo", "sem",
    "social media", "marketing", "finance", "accounting", "sap", "order management",
    "automation", "selenium", "vba", "power automate", "communication",
    "leadership", "stakeholder management", "problem solving", "reporting",
]

KNOWN_MATCHING_SKILLS = sorted(set(
    EXTRA_MATCHING_SKILLS +
    [skill for skills in CATEGORY_KEYWORDS.values() for skill in skills]
))


def recursive_text(value) -> str:
    """
    Convert nested dict/list candidate data into searchable text.
    """
    if value is None:
        return ""

    if isinstance(value, str):
        return value

    if isinstance(value, (int, float, bool)):
        return str(value)

    if isinstance(value, list):
        return " ".join(recursive_text(item) for item in value)

    if isinstance(value, dict):
        return " ".join(recursive_text(item) for item in value.values())

    return str(value)


def skill_in_text(skill: str, text: str) -> bool:
    """
    Search skill as a phrase with safe boundaries.
    Works better than plain 'skill in text' because it avoids many false matches.
    """
    skill = (skill or "").strip().lower()
    text = (text or "").lower()

    if not skill:
        return False

    pattern = r"(?<![a-z0-9])" + re.escape(skill) + r"(?![a-z0-9])"
    return re.search(pattern, text) is not None


def extract_required_years(job_description: str) -> float:
    """
    Extract minimum years of experience from JD.
    Examples:
    - minimum 2 years experience
    - 3+ years of experience
    - at least 1 year experience
    """
    jd = (job_description or "").lower()

    patterns = [
        r"(?:minimum|min\.?|at least)\s+(\d+(?:\.\d+)?)\+?\s*(?:years|year|yrs|yr)",
        r"(\d+(?:\.\d+)?)\+?\s*(?:years|year|yrs|yr)\s+(?:of\s+)?(?:experience|exp)",
        r"(?:experience|exp).*?(\d+(?:\.\d+)?)\+?\s*(?:years|year|yrs|yr)",
    ]

    for pattern in patterns:
        match = re.search(pattern, jd)
        if match:
            try:
                return float(match.group(1))
            except ValueError:
                return 0

    return 0


def extract_job_requirements(job_description: str) -> dict:
    """
    Extract required skills, preferred skills, required years, and likely category
    from the job description.
    """
    jd = job_description or ""
    jd_lower = jd.lower()

    detected_skills = [
        skill for skill in KNOWN_MATCHING_SKILLS
        if skill_in_text(skill, jd_lower)
    ]

    required_skills = []
    preferred_skills = []

    preferred_markers = [
        "preferred", "nice to have", "bonus", "advantage", "plus",
        "good to have", "would be a plus"
    ]

    for skill in detected_skills:
        skill_position = jd_lower.find(skill.lower())
        context_start = max(0, skill_position - 80)
        context_end = min(len(jd_lower), skill_position + len(skill) + 80)
        context = jd_lower[context_start:context_end]

        if any(marker in context for marker in preferred_markers):
            preferred_skills.append(skill)
        else:
            required_skills.append(skill)

    # If no required skills were detected but skills exist, treat them as required.
    if not required_skills and detected_skills:
        required_skills = detected_skills
        preferred_skills = []

    # Detect likely job category based on category keyword hits in the JD.
    category_scores = {}

    for category, keywords in CATEGORY_KEYWORDS.items():
        category_scores[category] = len(count_keyword_hits(jd_lower, keywords))

    likely_category = max(category_scores, key=category_scores.get)

    if category_scores[likely_category] == 0:
        likely_category = "Operations"

    return {
        "required_skills": sorted(set(required_skills)),
        "preferred_skills": sorted(set(preferred_skills)),
        "required_years": extract_required_years(jd),
        "likely_category": likely_category,
    }


def build_candidate_matching_text(candidate: JobRankingCandidate) -> str:
    """
    Build one searchable text block from candidate profile.
    """
    return " ".join([
        recursive_text(candidate.full_name),
        recursive_text(candidate.category),
        recursive_text(candidate.current_job_title),
        recursive_text(candidate.professional_summary),
        recursive_text(candidate.skills),
        recursive_text(candidate.experiences),
        recursive_text(candidate.projects),
        recursive_text(candidate.educations),
        recursive_text(candidate.certifications),
        recursive_text(candidate.raw_text),
    ]).lower()


def extract_evidence(candidate_text: str, matched_skills: list, limit: int = 3) -> list:
    """
    Extract short evidence lines from resume text based on matched skills.
    """
    if not candidate_text or not matched_skills:
        return []

    raw_lines = re.split(r"[\n\r\.•\-]+", candidate_text)
    evidence = []

    for line in raw_lines:
        clean_line = " ".join(line.strip().split())

        if len(clean_line) < 20:
            continue

        for skill in matched_skills:
            if skill_in_text(skill, clean_line):
                if len(clean_line) > 180:
                    clean_line = clean_line[:177] + "..."

                if clean_line not in evidence:
                    evidence.append(clean_line)

                break

        if len(evidence) >= limit:
            break

    return evidence


def calculate_job_match(job_requirements: dict, candidate: JobRankingCandidate) -> dict:
    """
    Calculate transparent math-based matching score.

    Formula:
    Final Match =
    45% required skills
    + 10% preferred skills
    + 15% experience
    + 15% evidence
    + 10% domain relevance
    + 5% education/certification
    """
    candidate_text = build_candidate_matching_text(candidate)

    required_skills = job_requirements.get("required_skills", [])
    preferred_skills = job_requirements.get("preferred_skills", [])
    required_years = float(job_requirements.get("required_years", 0) or 0)
    likely_category = job_requirements.get("likely_category", "Operations")

    matched_required = [
        skill for skill in required_skills
        if skill_in_text(skill, candidate_text)
    ]

    missing_required = [
        skill for skill in required_skills
        if skill not in matched_required
    ]

    matched_preferred = [
        skill for skill in preferred_skills
        if skill_in_text(skill, candidate_text)
    ]

    if required_skills:
        required_skill_score = (len(matched_required) / len(required_skills)) * 100
    else:
        required_skill_score = 50

    if preferred_skills:
        preferred_skill_score = (len(matched_preferred) / len(preferred_skills)) * 100
    else:
        preferred_skill_score = 50

    candidate_years = float(candidate.total_experience_years or 0)

    if required_years > 0:
        experience_score = min(candidate_years / required_years, 1) * 100
    else:
        experience_score = 70 if candidate_years > 0 else 50

    all_matched_skills = sorted(set(matched_required + matched_preferred))
    evidence = extract_evidence(candidate_text, all_matched_skills)

    if evidence:
        evidence_score = min(100, 50 + (len(evidence) * 15) + (len(all_matched_skills) * 5))
    else:
        evidence_score = 20 if all_matched_skills else 0

    candidate_category = (candidate.category or "").strip().lower()
    likely_category_lower = likely_category.lower()

    title_text = (candidate.current_job_title or "").lower()
    category_keywords = CATEGORY_KEYWORDS.get(likely_category, [])
    title_hits = count_keyword_hits(title_text, category_keywords)

    if candidate_category == likely_category_lower:
        domain_score = 90
    elif title_hits:
        domain_score = 75
    elif all_matched_skills:
        domain_score = 60
    else:
        domain_score = 30

    has_certification = bool(candidate.certifications)
    has_education = bool(candidate.educations)

    if has_certification and has_education:
        education_cert_score = 90
    elif has_certification or has_education:
        education_cert_score = 70
    else:
        education_cert_score = 40

    final_score = (
        required_skill_score * 0.45 +
        preferred_skill_score * 0.10 +
        experience_score * 0.15 +
        evidence_score * 0.15 +
        domain_score * 0.10 +
        education_cert_score * 0.05
    )

    final_score = round(min(100, max(0, final_score)), 2)

    if matched_required:
        reason = (
            f"Matches {len(matched_required)} out of {len(required_skills)} required skills. "
            f"Detected {candidate_years:g} years of experience. "
            f"Strongest evidence: {', '.join(matched_required[:5])}."
        )
    else:
        reason = (
            f"Limited direct match with the required skills. "
            f"Detected {candidate_years:g} years of experience. "
            f"Manual review is recommended."
        )

    return {
        "candidate_id": candidate.id,
        "candidate_name": candidate.full_name,
        "category": candidate.category,
        "current_job_title": candidate.current_job_title,
        "score": final_score,
        "match_percentage": final_score,
        "matched_skills": sorted(set(all_matched_skills)),
        "matched_required_skills": sorted(set(matched_required)),
        "matched_preferred_skills": sorted(set(matched_preferred)),
        "missing_skills": sorted(set(missing_required)),
        "evidence": evidence,
        "reason": reason,
        "score_breakdown": {
            "required_skill_score": round(required_skill_score, 2),
            "preferred_skill_score": round(preferred_skill_score, 2),
            "experience_score": round(experience_score, 2),
            "evidence_score": round(evidence_score, 2),
            "domain_score": round(domain_score, 2),
            "education_certification_score": round(education_cert_score, 2),
        },
    }


# ─── Routes ──────────────────────────────────────────────────────────────────

@app.get("/")
def health_check():
    return {
        "service":      "Intelligent Recruiter AI CV Scanner",
        "status":       "running",
        "ai_provider":  "ollama",
        "ollama_url":   OLLAMA_URL,
        "ollama_model": OLLAMA_MODEL,
        "mode":         "regex-first + llm-assist",
    }

@app.post("/rank-candidates")
def rank_candidates(req: RankCandidatesRequest):
    category = normalize_category(req.category)

    if not req.candidates:
        return {
            "status": "success",
            "category": category,
            "rankings": [],
        }

    scored = []

    for candidate in req.candidates:
        score, reason = score_candidate_for_category(candidate, category)

        scored.append({
            "candidate_id": candidate.id,
            "candidate_name": candidate.full_name,
            "category": category,
            "score": score,
            "match_percentage": score,
            "reason": reason,
        })

    scored.sort(key=lambda item: item["score"], reverse=True)

    for index, item in enumerate(scored, start=1):
        item["rank"] = index

    return {
        "status": "success",
        "category": category,
        "rankings": scored,
    }

@app.post("/rank-candidates-by-job")
def rank_candidates_by_job(req: JobRankCandidatesRequest):
    job_description = (req.job_description or "").strip()

    if not job_description:
        return JSONResponse(status_code=422, content={
            "status": "error",
            "message": "Job description is required.",
        })

    if not req.candidates:
        return {
            "status": "success",
            "job_requirements": extract_job_requirements(job_description),
            "rankings": [],
        }

    job_requirements = extract_job_requirements(job_description)

    scored = []

    for candidate in req.candidates:
        scored.append(calculate_job_match(job_requirements, candidate))

    scored.sort(key=lambda item: item["score"], reverse=True)

    for index, item in enumerate(scored, start=1):
        item["rank"] = index

    return {
        "status": "success",
        "job_requirements": job_requirements,
        "rankings": scored,
    }


@app.post("/parse-cv")
async def parse_cv(file: UploadFile = File(...)):
    suffix = os.path.splitext(file.filename)[1].lower()

    if suffix not in [".pdf", ".docx"]:
        return JSONResponse(status_code=400, content={
            "status": "error",
            "message": "Only PDF and DOCX files are supported.",
        })

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(await file.read())
        temp_path = tmp.name

    try:
        try:
            documents = load_document(temp_path, suffix)
        except Exception as e:
            return JSONResponse(status_code=200, content=empty_response(
                file.filename, "", f"Could not load document: {str(e)}"
            ))

        if not documents:
            return JSONResponse(status_code=200, content=empty_response(
                file.filename, "", "Document loaded but no pages found."
            ))

        raw_text = get_raw_text(documents)

        if not raw_text.strip():
            return JSONResponse(status_code=200, content=empty_response(
                file.filename, "", "No readable text found in CV."
            ))

        return build_result(file.filename, raw_text)

    finally:
        if os.path.exists(temp_path):
            os.remove(temp_path)