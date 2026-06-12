# Intelligent Recruiter

An AI-powered recruiter assistant that parses candidate resumes, classifies candidates, ranks candidates against job descriptions, and provides AI recruiter chatbot support.

---

# Local Setup Guide

Project Structure:

```text
intelligent-recruiter-ai/
intelligent-recruiter-laravel/
```

---

# MySQL Setup

Recommended GUI:

- MySQL Workbench

Create database:

```sql
CREATE DATABASE intelligent_recruiter;
```

Laravel `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intelligent_recruiter
DB_USERNAME=root
DB_PASSWORD=
```

---

# intelligent-recruiter-ai/db.py

## TCP Connection

```python
def get_conn():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "intelligent_recruiter"),
    )
```

## Unix Socket Connection

```python
def get_conn():
    return mysql.connector.connect(
        unix_socket="/tmp/mysql.sock",
        user="root",
        password="enter your password in here",
        database="intelligent_recruiter"
    )
```

---

# Laravel Environment Example

File:

```text
intelligent-recruiter-laravel/.env
```

Example:

```env
APP_NAME="Intelligent Recruiter"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intelligent_recruiter
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

PYTHON_AI_URL=http://127.0.0.1:8001
```

---

# Python AI Environment Example

File:

```text
intelligent-recruiter-ai/.env
```

Example:

```env
OLLAMA_MODEL=llama3.2:1b
OLLAMA_URL=http://127.0.0.1:11434

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intelligent_recruiter
DB_USERNAME=root
DB_PASSWORD=
```

---

# ============================================
# TERMINAL 1 — OLLAMA
# ============================================

## Bash (macOS/Linux)

```bash
# check installed models
ollama list

# pull model
ollama pull llama3.2:1b

# start ollama
ollama serve
```

## PowerShell (Windows)

```powershell
# check installed models
ollama list

# pull model
ollama pull llama3.2:1b

# start ollama
ollama serve
```

Keep terminal running.

---

# ============================================
# TERMINAL 2 — AI BACKEND SETUP
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-ai

# deactivate current venv
deactivate

# remove old venv
rm -rf venv

# create new venv
python3 -m venv venv

# activate venv
source venv/bin/activate

# upgrade pip
python -m pip install --upgrade pip

# install requirements
python -m pip install -r requirements.txt
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-ai

# deactivate current venv
deactivate

# remove old venv
Remove-Item -Recurse -Force venv

# create new venv
python -m venv venv

# activate venv
.\venv\Scripts\Activate.ps1

# upgrade pip
python -m pip install --upgrade pip

# install requirements
python -m pip install -r requirements.txt
```

If blocked:

```powershell
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```

Then activate again:

```powershell
.\venv\Scripts\Activate.ps1
```

---

# ============================================
# TERMINAL 3 — LARAVEL SETUP
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-laravel

# deactivate python venv
deactivate

# install dependencies
composer install

# install reverb
composer require laravel/reverb

# install reverb config
php artisan reverb:install
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-laravel

# deactivate python venv
deactivate

# install dependencies
composer install

# install reverb
composer require laravel/reverb

# install reverb config
php artisan reverb:install
```

---

# ============================================
# TERMINAL 4 — MYSQL MIGRATION
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-laravel

php artisan migrate
```

Fresh reset:

```bash
php artisan migrate:fresh
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-laravel

php artisan migrate
```

Fresh reset:

```powershell
php artisan migrate:fresh
```

---

# ============================================
# TERMINAL 5 — REVERB
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-laravel

php artisan reverb:start
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-laravel

php artisan reverb:start
```

Keep terminal running.

---

# ============================================
# TERMINAL 6 — QUEUE WORKER
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-laravel

php artisan queue:work
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-laravel

php artisan queue:work
```

Keep terminal running.

---

# ============================================
# TERMINAL 7 — LARAVEL SERVER
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-laravel

php artisan serve
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-laravel

php artisan serve
```

Laravel URL:

```text
http://127.0.0.1:8000
```

Keep terminal running.

---

# ============================================
# TERMINAL 8 — FASTAPI AI BACKEND
# ============================================

## Bash (macOS/Linux)

```bash
cd intelligent-recruiter-ai

source venv/bin/activate

python -m uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

## PowerShell (Windows)

```powershell
cd intelligent-recruiter-ai

.\venv\Scripts\Activate.ps1

python -m uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

FastAPI URL:

```text
http://127.0.0.1:8001
```

Keep terminal running.


---

# ============================================
# HOW TO USE THE SYSTEM
# ============================================

## Step 1 — Open Laravel

Open browser:

```text
http://127.0.0.1:8000
```

---

## Step 2 — Upload Resume

Upload PDF or DOCX resume.

The system will:

1. Upload resume to Laravel
2. Send file to FastAPI
3. Parse candidate data
4. Save candidate into MySQL
5. Classify candidate category
6. Store candidate profile

---

## Step 3 — View Candidate Profile

You can view:

- Name
- Skills
- Experience
- Education
- Projects
- Certifications
- Resume summary
- Candidate category

---

## Step 4 — Use Job Description Matching

Paste job description such as:

```text
We are hiring a Junior AI Automation Engineer with Laravel, Python, FastAPI, MySQL, AI chatbot, and workflow automation experience.
```

Then calculate JD match.

The system will show:

- Match percentage
- Matched skills
- Missing skills
- Resume evidence
- Candidate ranking
- Recommendation reason

---

## Step 5 — Use AI Recruiter Chatbot

Example prompts:

```text
Who is the best candidate for AI Engineer?
```

```text
Compare top 3 Laravel candidates.
```

```text
Which candidates know FastAPI and MySQL?
```

```text
Why is this candidate ranked first?
```

---

## Step 6 — Approve Candidate

Approve shortlisted candidate.

The system can:

- Update approval status
- Save approved candidates
- Queue approval emails
- Track approved candidate list

---

# ============================================
# FINAL RUNNING SERVICES
# ============================================

| Service | URL |
|---|---|
| Laravel | http://127.0.0.1:8000 |
| FastAPI | http://127.0.0.1:8001 |
| Ollama | http://127.0.0.1:11434 |



# ============================================
# HOW TO USE THE SYSTEM
# ============================================

## Step 1 — Open Laravel System

Open browser:

```text
http://127.0.0.1:8000
```

---

## Step 2 — Upload Candidate Resume

Upload:

- PDF resume
- DOCX resume

The system will:

1. Upload the resume to Laravel
2. Send the file to FastAPI
3. Extract candidate details
4. Save candidate data into MySQL
5. Classify candidate category
6. Store skills, education, projects, and experience

---

## Step 3 — View Candidate Profile

Open candidate detail page.

You can view:

- Name
- Skills
- Experience
- Education
- Projects
- Certifications
- AI classification
- Resume evidence

---

## Step 4 — Job Description Matching

Go to leaderboard or matching page.

Paste job description example:

```text
We are hiring a Junior AI Automation Engineer with Laravel, PHP, MySQL, Python, API development, AI workflow automation, and chatbot development experience.
```

Then click:

```text
Calculate JD Match
```

The system will calculate:

- Match percentage
- Matched skills
- Missing skills
- Resume evidence
- Recommendation reason
- Candidate ranking

---

## Step 5 — Use AI Recruiter Chatbot

Use chatbot examples:

```text
Who is the best candidate for AI Engineer?
```

```text
Compare the top 3 Laravel candidates.
```

```text
Which candidates know MySQL and FastAPI?
```

```text
Why is this candidate ranked first?
```

The AI assistant can:

- Compare candidates
- Explain rankings
- Filter candidates
- Retrieve candidate details
- Recommend shortlisted candidates

---

## Step 6 — Approve Candidate

Recruiter can approve candidate.

The system will:

1. Update approval status
2. Save approved candidate
3. Queue approval email
4. Show approved candidate status

---

# ============================================
# SYSTEM FLOW
# ============================================

```text
Recruiter Upload Resume
        |
        v
Laravel Upload System
        |
        v
FastAPI AI Service
        |
        |-- Resume Parsing
        |-- Candidate Classification
        |-- JD Matching
        |-- AI Chat Agent
        |
        v
MySQL Database
        |
        v
Laravel Dashboard + Chatbot
```

---

# ============================================
# FINAL RUNNING SERVICES
# ============================================

| Service | Port |
|---|---|
| Laravel | 8000 |
| FastAPI AI | 8001 |
| Ollama | 11434 |
