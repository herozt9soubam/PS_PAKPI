import json
import os
import shutil
import sqlite3
import subprocess
import sys
from datetime import datetime

PG_BIN = r"C:\Program Files\PostgreSQL\12\bin\psql.exe"
PG_ENV = {
    **os.environ,
    "PGHOST": "localhost",
    "PGUSER": "postgres",
    "PGPASSWORD": "postgres",
    "PGDATABASE": "pspakpi",
}
SQLITE_PATH = r"I:\C\htdocs\ps-pakpi\data\pspakpi.sqlite"


def run_psql(sql):
    result = subprocess.run(
        [PG_BIN, "-w", "-q", "-t", "-A", "-c", sql],
        env=PG_ENV,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip())
    return result.stdout


def pg_ident(name):
    return '"' + name.replace('"', '""') + '"'


def sqlite_ident(name):
    return '"' + name.replace('"', '""') + '"'


def sqlite_type(pg_type):
    pg_type = pg_type.lower()
    if pg_type in {"integer", "bigint", "smallint", "boolean"}:
        return "INTEGER"
    if pg_type in {"numeric", "real", "double precision", "decimal"}:
        return "REAL"
    return "TEXT"


def normalize_value(value):
    if isinstance(value, bool):
        return 1 if value else 0
    if isinstance(value, (list, dict)):
        return json.dumps(value, ensure_ascii=False)
    return value


def get_pg_tables():
    sql = """
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE'
        ORDER BY table_name
    """
    return [line for line in run_psql(sql).splitlines() if line]


def get_pg_columns(table):
    sql = f"""
        SELECT column_name || E'\\t' || data_type
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = '{table.replace("'", "''")}'
        ORDER BY ordinal_position
    """
    columns = []
    for line in run_psql(sql).splitlines():
        if not line:
            continue
        name, data_type = line.split("\t", 1)
        columns.append((name, data_type))
    return columns


def get_pg_rows(table):
    sql = f"COPY (SELECT row_to_json(t)::text FROM {pg_ident(table)} t) TO STDOUT"
    rows = []
    for line in run_psql(sql).splitlines():
        if line:
            rows.append(json.loads(line))
    return rows


def get_sqlite_columns(conn, table):
    try:
        return {row[1] for row in conn.execute(f"PRAGMA table_info({sqlite_ident(table)})")}
    except sqlite3.Error:
        return set()


def sqlite_table_exists(conn, table):
    return conn.execute(
        "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?",
        (table,),
    ).fetchone() is not None


def ensure_sqlite_table(conn, table, columns):
    if not sqlite_table_exists(conn, table):
        definitions = []
        for name, data_type in columns:
            definition = f"{sqlite_ident(name)} {sqlite_type(data_type)}"
            if name == "id":
                definition = f"{sqlite_ident(name)} INTEGER PRIMARY KEY"
            definitions.append(definition)
        conn.execute(f"CREATE TABLE {sqlite_ident(table)} ({', '.join(definitions)})")
        return

    existing = get_sqlite_columns(conn, table)
    for name, data_type in columns:
        if name not in existing:
            conn.execute(
                f"ALTER TABLE {sqlite_ident(table)} "
                f"ADD COLUMN {sqlite_ident(name)} {sqlite_type(data_type)}"
            )


def replace_table_rows(conn, table, columns, rows):
    column_names = [name for name, _ in columns]
    placeholders = ", ".join(["?"] * len(column_names))
    column_sql = ", ".join(sqlite_ident(name) for name in column_names)

    conn.execute(f"DELETE FROM {sqlite_ident(table)}")
    if not rows:
        return

    insert_sql = f"INSERT INTO {sqlite_ident(table)} ({column_sql}) VALUES ({placeholders})"
    values = [
        [normalize_value(row.get(column)) for column in column_names]
        for row in rows
    ]
    conn.executemany(insert_sql, values)


def refresh_sqlite_sequence(conn, table):
    if "id" not in get_sqlite_columns(conn, table):
        return
    if not sqlite_table_exists(conn, "sqlite_sequence"):
        return
    max_id = conn.execute(f"SELECT COALESCE(MAX(id), 0) FROM {sqlite_ident(table)}").fetchone()[0]
    conn.execute("DELETE FROM sqlite_sequence WHERE name = ?", (table,))
    if max_id:
        conn.execute("INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?)", (table, max_id))


def main():
    if not os.path.exists(PG_BIN):
        raise SystemExit(f"psql not found: {PG_BIN}")
    if not os.path.exists(SQLITE_PATH):
        raise SystemExit(f"SQLite database not found: {SQLITE_PATH}")

    backup_path = SQLITE_PATH + ".bak-" + datetime.now().strftime("%Y%m%d-%H%M%S")
    shutil.copy2(SQLITE_PATH, backup_path)

    tables = get_pg_tables()
    imported = []

    conn = sqlite3.connect(SQLITE_PATH)
    try:
        conn.execute("PRAGMA foreign_keys = OFF")
        conn.execute("BEGIN")
        for table in tables:
            columns = get_pg_columns(table)
            ensure_sqlite_table(conn, table, columns)
            rows = get_pg_rows(table)
            replace_table_rows(conn, table, columns, rows)
            refresh_sqlite_sequence(conn, table)
            imported.append((table, len(rows)))
        conn.commit()
    except Exception:
        conn.rollback()
        shutil.copy2(backup_path, SQLITE_PATH)
        raise
    finally:
        conn.close()

    print(f"Backup: {backup_path}")
    for table, count in imported:
        print(f"{table}: {count}")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(f"Import failed: {exc}", file=sys.stderr)
        raise
