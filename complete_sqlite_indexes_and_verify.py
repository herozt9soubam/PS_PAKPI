import os
import re
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


def q(name):
    return '"' + name.replace('"', '""') + '"'


def pg_tables():
    return [
        line
        for line in pg("""
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_type = 'BASE TABLE'
            ORDER BY table_name
        """).splitlines()
        if line
    ]


def import_simple_indexes(conn):
    rows = pg("""
        SELECT tablename || E'\t' || indexname || E'\t' || indexdef
        FROM pg_indexes
        WHERE schemaname = 'public'
        ORDER BY tablename, indexname
    """)

    created = []
    skipped = []
    for line in rows.splitlines():
        table, index_name, index_def = line.split("\t", 2)
        if index_name.endswith("_pkey") or " USING btree " not in index_def:
            skipped.append(index_name)
            continue

        match = re.search(r" USING btree \((.+)\)$", index_def)
        if not match:
            skipped.append(index_name)
            continue

        columns_expr = match.group(1)
        if any(token in columns_expr.lower() for token in ["(", ")", "::", " desc", " asc", " nulls "]):
            skipped.append(index_name)
            continue

        columns = [col.strip().strip('"') for col in columns_expr.split(",")]
        unique = "UNIQUE INDEX" in index_def.upper()
        unique_sql = "UNIQUE " if unique else ""
        sql = (
            f"CREATE {unique_sql}INDEX IF NOT EXISTS {q(index_name)} "
            f"ON {q(table)} ({', '.join(q(col) for col in columns)})"
        )
        conn.execute(sql)
        created.append(index_name)

    return created, skipped


def compare_counts(conn):
    results = []
    for table in pg_tables():
        pg_count = int(pg(f'SELECT COUNT(*) FROM "{table}"').strip())
        sqlite_count = conn.execute(f"SELECT COUNT(*) FROM {q(table)}").fetchone()[0]
        results.append((table, pg_count, sqlite_count))
    return results


def main():
    conn = sqlite3.connect(SQLITE_PATH)
    try:
        created, skipped = import_simple_indexes(conn)
        conn.commit()
        print("Indexes created/verified:", len(created))
        for index_name in created:
            print("INDEX", index_name)
        if skipped:
            print("Indexes skipped:", len(skipped))
            for index_name in skipped:
                print("SKIP", index_name)

        print("Counts:")
        for table, pg_count, sqlite_count in compare_counts(conn):
            status = "OK" if pg_count == sqlite_count else "MISMATCH"
            print(f"{table}: postgres={pg_count}, sqlite={sqlite_count}, {status}")
    finally:
        conn.close()


if __name__ == "__main__":
    main()
