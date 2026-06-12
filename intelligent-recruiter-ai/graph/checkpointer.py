import json
from db import get_conn


class MySQLCheckpointer:

    _DDL = """
    CREATE TABLE IF NOT EXISTS graph_checkpoints (
        thread_id  VARCHAR(64) PRIMARY KEY,
        state_json LONGTEXT    NOT NULL,
        updated_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP
    )
    """

    def __init__(self):
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(self._DDL)
        conn.commit()
        cur.close()
        conn.close()

    def load(self, thread_id: str) -> dict | None:
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(
            "SELECT state_json FROM graph_checkpoints WHERE thread_id = %s",
            (thread_id,),
        )
        row = cur.fetchone()
        cur.close()
        conn.close()
        return json.loads(row[0]) if row else None

    def save(self, thread_id: str, state: dict):
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(
            """
            INSERT INTO graph_checkpoints (thread_id, state_json)
            VALUES (%s, %s)
            ON DUPLICATE KEY UPDATE state_json = VALUES(state_json)
            """,
            (thread_id, json.dumps(state, default=str)),
        )
        conn.commit()
        cur.close()
        conn.close()

    def delete(self, thread_id: str):
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(
            "DELETE FROM graph_checkpoints WHERE thread_id = %s",
            (thread_id,),
        )
        conn.commit()
        cur.close()
        conn.close()