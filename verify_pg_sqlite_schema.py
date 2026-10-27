import os
import sqlite3
import subprocess

PG_BIN = r"C:\Program Files\PostgreSQL\12\bin\psql.exe"
SQLITE_PATH = r"I:\C\htdocs\ps-pakpi\data\pspakpi.sqlite"
PG_ENV = {
    **os.environ,
    "PGPASSWORD": "postgres",
}


def pg(sql):
    return subprocess.check_output(
        [PG_BIN, "-h", "localhost", "-U", "postgres", "-d", "pspakpi", "-w", "-At", "-c", sql],
        env=PG_ENV,
        text=True,
    )


pg_cols = {}
for line in pg("""
    SELECT table_name || '|' || column_name
    FROM information_schema.columns
    WHERE table_schema = 'public'
    ORDER BY table_name, ordinal_position
""").splitlines():
    table, column = line.split("|", 1)
    pg_cols.setdefault(table, set()).add(column)

conn = sqlite3.connect(SQLITE_PATH)
sqlite_cols = {}
for (table,) in conn.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"):
    sqlite_cols[table] = {
        row[1]
        for row in conn.execute(f'PRAGMA table_info("{table}")')
    }

missing_tables = sorted(set(pg_cols) - set(sqlite_cols))
extra_tables = sorted(set(sqlite_cols) - set(pg_cols))
missing_columns = {
    table: sorted(pg_cols[table] - sqlite_cols.get(table, set()))
    for table in sorted(pg_cols)
    if pg_cols[table] - sqlite_cols.get(table, set())
}

print("Missing tables:", missing_tables)
print("Extra SQLite tables:", extra_tables)
print("Missing columns:", missing_columns)
