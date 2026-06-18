# Intelligent Recruiter

An AI-powered recruitment assistant that parses candidate resumes, classifies candidates, ranks them against job descriptions, and provides an AI recruiter chatbot.

**Live Demo:** [https://intelligent-recruiter.mooo.com](https://intelligent-recruiter.mooo.com)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.2) |
| AI Service | Python 3 + FastAPI + LangChain + LangGraph |
| Database | MySQL 8.0 |
| Web Server | Nginx |
| Containerization | Docker + Docker Compose |
| Cloud | Google Cloud Platform (Compute Engine, Jakarta) |
| Auth | Google OAuth 2.0 (Socialite, stateless) |

---

## Features

- Upload PDF/DOCX resumes — AI parses and extracts candidate data
- Candidate classification by category (IT, Business, etc.)
- Job description matching with score, matched/missing skills, and ranking
- AI recruiter chatbot — ask questions about candidates in natural language
- Candidate approval workflow
- Leaderboard view

---

## Project Structure

```
IntelligentRecruiter/
├── intelligent-recruiter-laravel/   # Laravel app (PHP-FPM)
├── intelligent-recruiter-ai/        # Python FastAPI AI service
├── docker-compose.yml               # Multi-service Docker setup
└── README.md
```

---

## Docker Services

| Service | Description | Port |
|---|---|---|
| `mysql` | MySQL 8.0 database | 3307 (host) |
| `app` | Laravel PHP-FPM | internal |
| `nginx` | Web server | 80, 443 |
| `queue` | Laravel queue worker | internal |
| `python` | FastAPI AI backend | 8001 |

---

## Local Setup (Docker)

### Prerequisites

- Docker + Docker Compose
- Git

### Steps

**1. Clone the repo**
```bash
git clone https://github.com/nando135/IntelligentRecruiter.git
cd IntelligentRecruiter
```

**2. Configure environment files**

`intelligent-recruiter-laravel/.env`:
```env
APP_NAME="Intelligent Recruiter"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=intelligent_recruiter
DB_USERNAME=root
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
PYTHON_AI_URL=http://python:8001

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

`intelligent-recruiter-ai/.env`:
```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=intelligent_recruiter
DB_USERNAME=root
DB_PASSWORD=your_password
```

**3. Create root `.env` for Docker Compose**
```bash
echo "DB_PASSWORD=your_password
DB_DATABASE=intelligent_recruiter" > .env
```

**4. Start the containers**
```bash
docker compose up -d --build
```

**5. Run setup commands**
```bash
docker exec intelligentrecruiter-app-1 composer install --no-dev --optimize-autoloader
docker exec intelligentrecruiter-app-1 php artisan key:generate
docker exec intelligentrecruiter-app-1 php artisan migrate --force
docker exec intelligentrecruiter-app-1 php artisan storage:link
docker exec intelligentrecruiter-app-1 chmod -R 775 storage bootstrap/cache
docker exec intelligentrecruiter-app-1 chown -R www-data:www-data storage bootstrap/cache
```

**6. Build frontend assets**
```bash
cd intelligent-recruiter-laravel
npm install
npm run build
cd ..
```

**7. Open the app**
```
http://localhost:8000
```

---

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create an OAuth 2.0 Client ID (Web application)
3. Add authorized redirect URI: `http://localhost:8000/auth/google/callback`
4. Copy Client ID and Secret into `intelligent-recruiter-laravel/.env`

---

## How to Use

**1. Login** with Google OAuth

**2. Upload CV** — PDF or DOCX resume. The system will:
- Parse candidate details (name, skills, experience, education, projects)
- Classify candidate by category
- Store profile in MySQL

**3. View Candidate Profile** — parsed skills, experience, education, and AI classification

**4. Match against Job Description** — paste a JD and get:
- Match percentage
- Matched and missing skills
- Resume evidence
- Candidate ranking and recommendation

**5. Chat with AI Recruiter** — example prompts:
```
Who is the best candidate for AI Engineer?
Compare top 3 Laravel candidates.
Which candidates know FastAPI and MySQL?
Why is this candidate ranked first?
```

**6. Approve Candidates** — shortlist and track approved candidates

---

## System Flow

```
Recruiter Upload Resume
        │
        ▼
Laravel (PHP-FPM + Nginx)
        │
        ▼
Python FastAPI AI Service
        │
        ├── Resume Parsing
        ├── Candidate Classification
        ├── JD Matching
        └── AI Chat Agent (LangGraph)
        │
        ▼
MySQL Database
        │
        ▼
Laravel Dashboard + Chatbot
```

---

## Live Services

| Service | URL |
|---|---|
| Web App | https://intelligent-recruiter.mooo.com |
| FastAPI AI | http://intelligent-recruiter.mooo.com:8001 |
