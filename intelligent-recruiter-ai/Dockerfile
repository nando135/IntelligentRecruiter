FROM python:3.11-slim

WORKDIR /app

# System deps for PDF/DOCX processing
RUN apt-get update && apt-get install -y \
    gcc g++ curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source
COPY . .

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001"]
