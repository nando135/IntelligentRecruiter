"""
CV Parser v2 — LlamaIndex + Ollama structured extraction.

Strategy:
- Load PDF/DOCX with LlamaIndex readers
- Send full CV text to Ollama LLM in one structured prompt
- LLM returns a complete JSON covering all fields
- No regex, no section splitting — the LLM reads it like a human would

Drop-in replacement for build_result() in main.py.
Call parse_cv_v2(file_path, file_name) instead of build_result().
"""

import json
import os
import re
import tempfile
from typing import Optional

from dotenv import load_dotenv
from llama_index.core import SimpleDirectoryReader
from langchain_ollama import ChatOllama

load_dotenv()

OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3.2:1b")
OLLAMA_URL   = os.getenv("OLLAMA_URL",   "http://127.0.0.1:11434")

# ─── Document Loading ────────────────────────────────────────────────────────

def load_cv_text(file_path: str) -> str:
    """Load PDF or DOCX and return plain text."""
    reader = SimpleDirectoryReader(input_files=[file_path])
    documents = reader.load_data()
    return "\n\n".join(doc.text for doc in documents).strip()


# ─── Extraction Prompt ───────────────────────────────────────────────────────

EXTRACTION_PROMPT = """You are a CV data extraction expert. Read the CV below and extract ALL information into a single valid JSON object.

Follow this exact schema. Use null for any field not found. Do NOT add extra keys.

{{
  "full_name": "string or null",
  "email": "string or null",
  "phone": "string or null",
  "location": "string or null",
  "date_of_birth": "string or null",
  "gender": "string or null",
  "nationality": "string or null",
  "religion": "string or null",
  "marital_status": "string or null",
  "driving_license": "string or null",
  "expected_salary": "string or null",
  "notice_period": "string or null",
  "willing_to_relocate": true/false/null,
  "professional_summary": "string or null",
  "current_job_title": "string or null",
  "latest_company": "string or null",
  "linkedin": "string or null",
  "github": "string or null",
  "portfolio": "string or null",
  "website": "string or null",

  "experiences": [
    {{
      "company_name": "string or null",
      "job_title": "string or null",
      "employment_type": "Full-time/Part-time/Internship/Contract or null",
      "location": "string or null",
      "start_date": "string or null",
      "end_date": "string or null (use 'Present' if current)",
      "is_current": true/false,
      "duration_months": integer or null,
      "responsibilities": ["string", ...],
      "achievements": ["string", ...],
      "tools_used": ["string", ...]
    }}
  ],

  "education": [
    {{
      "institution": "string or null",
      "degree": "string or null",
      "field_of_study": "string or null",
      "start_year": integer or null,
      "end_year": integer or null,
      "cgpa": float or null,
      "cgpa_scale": float or null,
      "honours": "string or null",
      "relevant_coursework": ["string", ...]
    }}
  ],

  "skills": {{
    "technical_skills": ["string", ...],
    "soft_skills": ["string", ...],
    "analytics_tools": ["string", ...],
    "programming_languages": ["string", ...],
    "databases": ["string", ...],
    "frameworks": ["string", ...],
    "tools": ["string", ...]
  }},

  "projects": [
    {{
      "project_name": "string or null",
      "project_type": "string or null",
      "description": "string or null",
      "technologies": ["string", ...],
      "role": "string or null",
      "outcome": "string or null",
      "url": "string or null",
      "year": integer or null
    }}
  ],

  "certifications": [
    {{
      "name": "string or null",
      "issuer": "string or null",
      "date_issued": "string or null",
      "expiry_date": "string or null",
      "credential_link": "string or null"
    }}
  ],

  "achievements": [
    {{
      "title": "string or null",
      "description": "string or null",
      "year": integer or null,
      "organization": "string or null",
      "position": "string or null"
    }}
  ],

  "languages": [
    {{
      "language": "string",
      "proficiency": "Native/Fluent/Proficient/Intermediate/Basic or null"
    }}
  ],

  "volunteer": [
    {{
      "organization": "string or null",
      "role": "string or null",
      "start_date": "string or null",
      "end_date": "string or null",
      "description": "string or null"
    }}
  ],

  "publications": [
    {{
      "title": "string or null",
      "journal": "string or null",
      "year": integer or null,
      "co_authors": ["string", ...]
    }}
  ],

  "competitions": [
    {{
      "event_name": "string or null",
      "position": "string or null",
      "year": integer or null,
      "organizer": "string or null"
    }}
  ],

  "extracurricular": [
    {{
      "activity": "string or null",
      "role": "string or null",
      "year": integer or null
    }}
  ],

  "hobbies": ["string", ...],

  "references": [
    {{
      "name": "string or null",
      "job_title": "string or null",
      "company": "string or null",
      "email": "string or null",
      "phone": "string or null"
    }}
  ]
}}

Rules:
- Return ONLY the JSON object. No explanation, no markdown, no code fences.
- For duration_months, calculate from start_date to end_date if both are present.
- For total experience, do NOT add a field — it will be calculated from experiences[].
- Extract ALL experiences, projects, certifications — do not truncate.
- For skills, categorize them properly into the correct sub-arrays.

CV TEXT:
{cv_text}
"""


# ─── LLM Call ────────────────────────────────────────────────────────────────

def extract_with_llm(cv_text: str) -> dict:
    """Send CV text to Ollama and get back structured JSON."""
    llm = ChatOllama(
        model=OLLAMA_MODEL,
        base_url=OLLAMA_URL,
        temperature=0,
        format="json",
    )

    prompt = EXTRACTION_PROMPT.format(cv_text=cv_text[:8000])  # cap to avoid token overflow
    response = llm.invoke([
        {"role": "system", "content": "You are a CV data extractor. Reply with only valid JSON."},
        {"role": "user",   "content": prompt},
    ])
    raw = response.content.strip()

    # Strip markdown code fences if the model wraps it anyway
    raw = re.sub(r"^```(?:json)?\s*", "", raw)
    raw = re.sub(r"\s*```$", "", raw)

    return json.loads(raw)


# ─── Experience totals ────────────────────────────────────────────────────────

def calc_total_experience(experiences: list):
    total_months = sum(e.get("duration_months") or 0 for e in experiences if isinstance(e, dict))
    if total_months == 0:
        return None, None
    return total_months, round(total_months / 12, 1)


# ─── Classification (keyword-based, same as v1) ───────────────────────────────

VALID_CATEGORIES = ["IT", "Business", "Data", "Marketing", "Finance", "Operations"]

CATEGORY_KEYWORDS = {
    "IT": [
        "software", "developer", "programmer", "engineer", "backend", "frontend",
        "full stack", "web", "mobile", "api", "laravel", "php", "java", "javascript",
        "typescript", "react", "vue", "angular", "node", "fastapi", "django", "flask",
        "spring", "mysql", "postgresql", "mongodb", "firebase", "git", "docker",
        "cloud", "aws", "azure", "devops", "cybersecurity", "network", "system",
    ],
    "Business": [
        "business", "strategy", "sales", "client", "stakeholder", "account", "crm",
        "partnership", "market research", "business development", "consulting",
        "proposal", "commercial", "management", "administration",
    ],
    "Data": [
        "data", "analytics", "analysis", "analyst", "dashboard", "power bi",
        "tableau", "excel", "sql", "python", "pandas", "numpy", "machine learning",
        "statistics", "forecast", "visualization", "reporting", "etl", "big data",
        "data science", "data warehouse",
    ],
    "Marketing": [
        "marketing", "seo", "sem", "campaign", "content", "copywriting", "brand",
        "social media", "instagram", "tiktok", "facebook", "google ads",
        "digital marketing", "creative", "canva", "adobe", "public relations",
    ],
    "Finance": [
        "finance", "accounting", "audit", "tax", "budget", "forecasting", "financial",
        "invoice", "payable", "receivable", "reconciliation", "bookkeeping",
        "investment", "risk", "banking", "sap", "procurement",
    ],
    "Operations": [
        "operations", "supply chain", "logistics", "inventory", "warehouse", "process",
        "workflow", "order management", "customer service", "admin", "coordination",
        "quality", "planning", "scheduler", "fulfillment", "vendor",
    ],
}


def classify(extracted: dict) -> dict:
    """Classify candidate into a category based on extracted data."""
    parts = [
        extracted.get("current_job_title") or "",
        extracted.get("professional_summary") or "",
        " ".join(s for v in (extracted.get("skills") or {}).values() if isinstance(v, list) for s in v),
        " ".join(
            " ".join([
                e.get("job_title") or "",
                " ".join(e.get("responsibilities") or []),
                " ".join(e.get("tools_used") or []),
            ])
            for e in (extracted.get("experiences") or [])
        ),
    ]
    full_text = " ".join(parts).lower()

    scores = {}
    hits_map = {}
    for cat, kws in CATEGORY_KEYWORDS.items():
        hits = [kw for kw in kws if re.search(r"\b" + re.escape(kw) + r"\b", full_text)]
        scores[cat] = len(hits)
        hits_map[cat] = hits

    best = max(scores, key=scores.get)
    if scores[best] == 0:
        return {"category": "Operations", "confidence": 35.0, "reason": "No strong category evidence found."}

    total = sum(scores.values())
    confidence = round(min(95, max(45, (scores[best] / max(total, 1)) * 100)), 2)
    return {
        "category": best,
        "confidence": confidence,
        "reason": f"Classified as {best} based on: {', '.join(hits_map[best][:5])}.",
    }


# ─── Main Entry Point ─────────────────────────────────────────────────────────

def parse_cv_v2(file_path: str, file_name: str) -> dict:
    """
    Full CV parse using LlamaIndex + Ollama.
    Returns the same response shape as the original build_result().
    """
    # 1. Load document text
    try:
        raw_text = load_cv_text(file_path)
    except Exception as e:
        return _empty(file_name, "", f"Could not load document: {e}")

    if not raw_text.strip():
        return _empty(file_name, "", "No readable text found in CV.")

    # 2. LLM extraction
    try:
        extracted = extract_with_llm(raw_text)
    except json.JSONDecodeError as e:
        return _empty(file_name, raw_text, f"LLM returned invalid JSON: {e}")
    except Exception as e:
        return _empty(file_name, raw_text, f"LLM extraction failed: {e}")

    # 3. Derived fields
    experiences = extracted.get("experiences") or []
    total_months, total_years = calc_total_experience(experiences)
    classification = classify(extracted)

    return {
        "status":         "success",
        "parser_status":  "success",
        "parser_warning": None,
        "file_name":      file_name,
        "candidate": {
            "full_name":                    extracted.get("full_name"),
            "email":                        extracted.get("email"),
            "phone":                        extracted.get("phone"),
            "location":                     extracted.get("location"),
            "date_of_birth":                extracted.get("date_of_birth"),
            "gender":                       extracted.get("gender"),
            "nationality":                  extracted.get("nationality"),
            "religion":                     extracted.get("religion"),
            "marital_status":               extracted.get("marital_status"),
            "driving_license":              extracted.get("driving_license"),
            "expected_salary":              extracted.get("expected_salary"),
            "notice_period":                extracted.get("notice_period"),
            "willing_to_relocate":          extracted.get("willing_to_relocate"),
            "professional_summary":         extracted.get("professional_summary"),
            "current_job_title":            extracted.get("current_job_title"),
            "latest_company":               experiences[0].get("company_name") if experiences else None,
            "total_experience_months":      total_months,
            "total_experience_years":       total_years,
            "internship_experience_months": None,
            "full_time_experience_months":  None,
            "candidate_category":           classification["category"],
        },
        "classification":  classification,
        "experiences":     experiences,
        "education":       extracted.get("education") or [],
        "skills":          extracted.get("skills") or {},
        "projects":        extracted.get("projects") or [],
        "certifications":  extracted.get("certifications") or [],
        "achievements":    extracted.get("achievements") or [],
        "languages":       extracted.get("languages") or [],
        "volunteer":       extracted.get("volunteer") or [],
        "publications":    extracted.get("publications") or [],
        "competitions":    extracted.get("competitions") or [],
        "extracurricular": extracted.get("extracurricular") or [],
        "hobbies":         extracted.get("hobbies") or [],
        "references":      extracted.get("references") or [],
        "links": {
            "linkedin":  extracted.get("linkedin"),
            "github":    extracted.get("github"),
            "portfolio": extracted.get("portfolio"),
            "website":   extracted.get("website"),
        },
        "raw_text": raw_text,
    }


def _empty(file_name: str, raw_text: str, warning: str) -> dict:
    return {
        "status": "success", "parser_status": "failed",
        "parser_warning": warning, "file_name": file_name,
        "candidate": {}, "classification": {}, "experiences": [], "education": [],
        "skills": {}, "projects": [], "certifications": [], "achievements": [],
        "languages": [], "volunteer": [], "publications": [], "competitions": [],
        "extracurricular": [], "hobbies": [], "references": [], "links": {},
        "raw_text": raw_text,
    }


# ─── Quick Test ───────────────────────────────────────────────────────────────

if __name__ == "__main__":
    import sys

    if len(sys.argv) < 2:
        print("Usage: python cv_parser_v2.py path/to/cv.pdf")
        sys.exit(1)

    path = sys.argv[1]
    result = parse_cv_v2(path, os.path.basename(path))
    print(json.dumps(result, indent=2, default=str))
