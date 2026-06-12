import json
from langchain_core.tools import tool
from db import get_conn


@tool
def get_candidates_summary() -> str:
    """
    Fetch a summary of all candidates from the database.
    Returns name, title, experience, location, and top skills.
    Use this when comparing candidates or filtering by job requirements.
    """
    conn = get_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute(
        """
        SELECT
            c.id,
            c.full_name,
            c.location,
            c.current_job_title,
            c.latest_company,
            c.total_experience_years,
            c.professional_summary,
            GROUP_CONCAT(DISTINCT cs.skill ORDER BY cs.skill SEPARATOR ', ') AS skills
        FROM candidates c
        LEFT JOIN candidate_skills cs ON cs.candidate_id = c.id
        GROUP BY c.id
        ORDER BY c.id
        """
    )
    rows = cur.fetchall()
    cur.close()
    conn.close()
    if not rows:
        return "No candidates found in the database."
    return json.dumps(rows, default=str)


@tool
def get_candidate_by_name(name: str) -> str:
    """
    Fetch the full profile of a specific candidate by name.
    Includes education, experience, skills, projects, certifications,
    achievements, and languages. Use for candidate detail questions.
    """
    conn = get_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute(
        "SELECT * FROM candidates WHERE full_name LIKE %s LIMIT 1",
        (f"%{name}%",),
    )
    candidate = cur.fetchone()
    if not candidate:
        cur.close()
        conn.close()
        return f"No candidate named '{name}' found."

    cid = candidate["id"]

    def q(sql):
        cur.execute(sql, (cid,))
        return cur.fetchall()

    candidate["educations"]      = q("SELECT * FROM candidate_educations WHERE candidate_id = %s")
    candidate["experiences"]     = q("SELECT * FROM candidate_experiences WHERE candidate_id = %s")
    candidate["projects"]        = q("SELECT * FROM candidate_projects WHERE candidate_id = %s")
    candidate["certifications"]  = q("SELECT * FROM candidate_certifications WHERE candidate_id = %s")
    candidate["achievements"]    = q("SELECT * FROM candidate_achievements WHERE candidate_id = %s")
    candidate["languages"]       = q("SELECT * FROM candidate_languages WHERE candidate_id = %s")
    cur.execute(
        "SELECT category, GROUP_CONCAT(skill SEPARATOR ', ') AS skills "
        "FROM candidate_skills WHERE candidate_id = %s GROUP BY category",
        (cid,),
    )
    candidate["skills"] = cur.fetchall()

    cur.close()
    conn.close()
    return json.dumps(candidate, default=str)


ALL_TOOLS = [get_candidates_summary, get_candidate_by_name]