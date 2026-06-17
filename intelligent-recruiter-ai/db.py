import os
import mysql.connector
from dotenv import load_dotenv

load_dotenv()

def get_conn():
    host = os.getenv("DB_HOST", "127.0.0.1")
    port = int(os.getenv("DB_PORT", 3306))
    # Use TCP when a host is explicitly set, otherwise fall back to socket
    if host in ("localhost", "127.0.0.1") and os.path.exists("/tmp/mysql.sock"):
        return mysql.connector.connect(
            unix_socket="/tmp/mysql.sock",
            user=os.getenv("DB_USERNAME", "root"),
            password=os.getenv("DB_PASSWORD", "(Tpi12345)"),
            database=os.getenv("DB_DATABASE", "intelligent_recruiter"),
        )
    return mysql.connector.connect(
        host=host,
        port=port,
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", "(Tpi12345)"),
        database=os.getenv("DB_DATABASE", "intelligent_recruiter"),
    )