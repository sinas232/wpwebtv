#!/usr/bin/env python3
"""
Concurrency test of the submission claim + order reservation on a REAL database
engine (SQLite, file-backed, several OS processes racing on one file).

IMPORTANT, read before citing the result:
  * This is a PORT of the algorithm in pixva/inc/security.php (pixva_claim_submission,
    pixva_finish_submission) and pixva/inc/repairs.php (pixva_place_order_once).
    The PHP code is NOT executed here. The SQL statements are the same primitives:
    INSERT ... (UNIQUE option_name) for create-once, and
    UPDATE ... WHERE option_name = ? AND option_value = ? for compare-and-replace.
  * The engine is SQLite, not MySQL/MariaDB. MySQL/InnoDB locking differs. The
    real MySQL/MariaDB run is NOT TESTED in this environment (no server binary is
    reachable from the sandbox). Treat this as evidence for the primitive logic on
    a real engine, not as evidence for the WordPress deployment.
  * Crashes are simulated by os._exit() at random points between steps, which is
    what a PHP fatal error or a killed worker looks like to the database.

Run: python3 tests/unit/db/reservation_sqlite_race.py [rounds] [processes]
Prints one line per round and a final verdict. Exit code 1 on any violation.
"""

import multiprocessing as mp
import collections
import os
import random
import sqlite3
import sys
import tempfile
import time
import uuid

CLAIM_PENDING_TTL = 300
# Negative controls: each switch removes one primitive. The run must then report violations.
MUTANT = os.environ.get("RACE_MUTANT", "")
LINK_TTL = 2592000

SCHEMA = """
CREATE TABLE wp_options (
    option_id INTEGER PRIMARY KEY AUTOINCREMENT,
    option_name TEXT NOT NULL UNIQUE,
    option_value TEXT NOT NULL
);
CREATE TABLE wp_posts (
    ID INTEGER PRIMARY KEY AUTOINCREMENT,
    post_type TEXT NOT NULL,
    post_name TEXT NOT NULL,
    post_title TEXT NOT NULL
);
CREATE INDEX wp_posts_name ON wp_posts (post_name);
CREATE TABLE announce (sid TEXT NOT NULL, order_id INTEGER NOT NULL);
CREATE TABLE inserted (sid TEXT NOT NULL, order_id INTEGER NOT NULL);
"""


class SimulatedFatal(Exception):
    """Raised where a PHP fatal error would stop the request."""


def think():
    """Time a PHP request spends between two statements (read, then write)."""
    time.sleep(random.uniform(0.0, 0.003))


def create_once(db, name, value):
    try:
        db.execute("INSERT INTO wp_options (option_name, option_value) VALUES (?, ?)", (name, value))
        return True
    except sqlite3.IntegrityError:
        return False


def replace_row(db, name, expected, new):
    if MUTANT == "no_cas":
        # Read-then-write: ignores the value that was read.
        cur = db.execute("UPDATE wp_options SET option_value = ? WHERE option_name = ?", (new, name))
        return cur.rowcount == 1
    cur = db.execute(
        "UPDATE wp_options SET option_value = ? WHERE option_name = ? AND option_value = ?",
        (new, name, expected),
    )
    return cur.rowcount == 1


def delete_row(db, name, expected):
    cur = db.execute("DELETE FROM wp_options WHERE option_name = ? AND option_value = ?", (name, expected))
    return cur.rowcount == 1


def read_row(db, name):
    row = db.execute("SELECT option_value FROM wp_options WHERE option_name = ?", (name,)).fetchone()
    return row[0] if row else None


def orders_for(db, sid):
    return [r[0] for r in db.execute(
        "SELECT ID FROM wp_posts WHERE post_type = 'pixva_orders' AND (post_name = ? OR post_name LIKE ?) ORDER BY ID ASC LIMIT 20",
        (sid, sid + "-%"),
    ).fetchall()]


def remove_duplicates(db, sid, keep):
    for oid in orders_for(db, sid):
        if oid != keep:
            db.execute("DELETE FROM wp_posts WHERE ID = ?", (oid,))


def order_exists(db, oid):
    return db.execute("SELECT 1 FROM wp_posts WHERE ID = ? AND post_type = 'pixva_orders'", (oid,)).fetchone() is not None


def json_row(**kw):
    import json
    return json.dumps(kw, sort_keys=True)


def parse_row(value):
    import json
    try:
        return json.loads(value)
    except (TypeError, ValueError):
        return None


def submit(db_path, sid, now, crash_at, crash_step):
    """One request. crash_at: step index at which the process dies (or None)."""
    db = sqlite3.connect(db_path, timeout=60, isolation_level=None)
    step = [0]

    def maybe_crash():
        step[0] += 1
        if crash_at is not None and step[0] == crash_at:
            raise SimulatedFatal()  # Nothing after this point runs in this request.

    # ---- claim (pixva_claim_submission) ----
    claim_name = "pixva_sub_" + sid
    token = uuid.uuid4().hex
    pending = json_row(s="pending", k=token, t=now)
    claimed = False
    if create_once(db, claim_name, pending):
        claimed = True
    else:
        held = read_row(db, claim_name)
        think()
        row = parse_row(held) if held is not None else None
        if row is None:
            if held is not None and replace_row(db, claim_name, held, pending):
                claimed = True
        elif row.get("s") == "done":
            db.close()
            return ("replayed", None)
        elif now - int(row.get("t", 0)) < CLAIM_PENDING_TTL:
            db.close()
            return ("pending", None)
        elif held is not None and replace_row(db, claim_name, held, pending):
            claimed = True
    if not claimed:
        db.close()
        return ("pending", None)
    maybe_crash()

    # ---- reservation (pixva_place_order_once) ----
    name = "pixva_ord_" + sid
    mine = json_row(s="creating", k=token, t=now)
    result = None
    for _ in range(5):
        held = read_row(db, name)
        think()
        if held is None:
            if not create_once(db, name, mine):
                continue
            maybe_crash()
        else:
            row = parse_row(held) or {}
            if row.get("s") == "linked" and order_exists(db, int(row.get("o", 0))):
                remove_duplicates(db, sid, int(row["o"]))
                result = int(row["o"])
                break
            if not replace_row(db, name, held, mine):
                continue
        found = orders_for(db, sid) if MUTANT != "no_adopt" else []
        if found:
            oid = found[0]
        else:
            # Nothing carries this id yet, so WordPress keeps the plain slug.
            db.execute(
                "INSERT INTO wp_posts (post_type, post_name, post_title) VALUES ('pixva_orders', ?, 'PXV-TEST')",
                (sid,),
            )
            oid = db.execute("SELECT last_insert_rowid()").fetchone()[0]
            db.execute("INSERT INTO inserted (sid, order_id) VALUES (?, ?)", (sid, oid))
            maybe_crash()
        maybe_crash()
        linked = json_row(s="linked", o=oid, t=now)
        if replace_row(db, name, mine, linked):
            remove_duplicates(db, sid, oid)
            db.execute("INSERT INTO announce (sid, order_id) VALUES (?, ?)", (sid, oid))
            maybe_crash()
            result = oid
            break
        # Fenced out: re-read on the next pass.
    if result is None:
        db.close()
        return ("busy", None)

    # ---- finish (pixva_finish_submission) ----
    done = json_row(s="done", t=now, r={"order": result})
    replace_row(db, claim_name, pending, done)
    db.close()
    return ("created", result)


BARRIER = None  # Set before the recovery pool is forked: releases all racers together.


def worker(args):
    db_path, sid, now, crash_at = args
    if crash_at == "sync" and BARRIER is not None:
        BARRIER.wait()
        crash_at = None
    try:
        return submit(db_path, sid, now, crash_at, None)
    except SimulatedFatal:
        return ("crashed", None)


def run_round(seed, processes):
    random.seed(seed)
    fd, db_path = tempfile.mkstemp(prefix="pixva-race-", suffix=".sqlite")
    os.close(fd)
    db = sqlite3.connect(db_path, isolation_level=None)
    db.executescript(SCHEMA)
    db.close()
    sid = uuid.uuid4().hex
    now = 1_000_000

    # Phase 1: concurrent submissions of the same id, some crashing at random steps.
    # Crash points are only meaningful inside a single claimant; the others see "pending".
    jobs = []
    for i in range(processes):
        crash_at = random.choice([1, 2, 3, 4, 5]) if i % 2 == 0 else None
        jobs.append((db_path, sid, now, crash_at))
    ctx = mp.get_context("fork")
    with ctx.Pool(processes) as pool:
        phase1 = pool.map(worker, jobs)

    # Phase 2: retries after the claim TTL, all at once. Several requests see the same
    # stale claim and reservation, so the takeover itself is raced.
    global BARRIER
    later = now + CLAIM_PENDING_TTL + 1
    BARRIER = ctx.Barrier(8)
    with ctx.Pool(8) as pool:
        phase2 = pool.map(worker, [(db_path, sid, later, "sync") for _ in range(8)])

    db = sqlite3.connect(db_path, isolation_level=None)
    orders = orders_for(db, sid)
    announced = [r[1] for r in db.execute("SELECT sid, order_id FROM announce WHERE sid = ?", (sid,)).fetchall()]
    inserted = [r[1] for r in db.execute("SELECT sid, order_id FROM inserted WHERE sid = ? ORDER BY rowid", (sid,)).fetchall()]
    reservation = parse_row(read_row(db, "pixva_ord_" + sid)) or {}
    claim = parse_row(read_row(db, "pixva_sub_" + sid)) or {}
    db.close()
    os.remove(db_path)

    violations = []
    if len(orders) != 1:
        violations.append("orders=%d (expected 1)" % len(orders))
    if len(inserted) > 1:
        violations.append("inserted=%d orders (expected at most 1)" % len(inserted))
    if inserted and orders and orders[0] != inserted[0]:
        violations.append("recovery did not adopt the order the crashed attempt inserted")
    if len(announced) != 1:
        violations.append("announced=%d (expected 1)" % len(announced))
    if orders and announced and announced[0] != orders[0]:
        violations.append("announced order is not the surviving order")
    if reservation.get("s") != "linked" or (orders and int(reservation.get("o", -1)) != orders[0]):
        violations.append("reservation not linked to the surviving order")
    created_phase2 = [o for s_, o in phase2 if s_ == "created"]
    crashed = sum(1 for s_, _ in phase1 if s_ == "crashed")
    phase2 = [(s_, o) for s_, o in phase2]
    for s_, o in phase2:
        if o is not None and orders and o != orders[0]:
            violations.append("a later attempt returned a different order")
    if any(s_ == "created" for s_, _ in phase1) and len(set(o for s_, o in phase1 if o)) > 1:
        violations.append("concurrent attempts reported different orders")
    return violations, len(orders), len(announced), dict(collections.Counter(s_ for s_, _ in phase1)), (crashed, dict(collections.Counter(s_ for s_, _ in phase2)))


def main():
    rounds = int(sys.argv[1]) if len(sys.argv) > 1 else 30
    processes = int(sys.argv[2]) if len(sys.argv) > 2 else 24
    bad = 0
    stats = {"orders": {}, "announced": {}}
    totals = collections.Counter()
    for r in range(rounds):
        violations, n_orders, n_announced, outcomes, phase2 = run_round(r + 1, processes)
        totals.update(outcomes)
        totals["crashed_requests"] += phase2[0]
        totals.update({"recovery_" + k: v for k, v in phase2[1].items()})
        stats["orders"][n_orders] = stats["orders"].get(n_orders, 0) + 1
        stats["announced"][n_announced] = stats["announced"].get(n_announced, 0) + 1
        if violations:
            bad += 1
            print("round %d VIOLATION: %s (phase-1 outcomes %s)" % (r + 1, "; ".join(violations), outcomes))
    print("rounds=%d processes=%d violations=%d" % (rounds, processes, bad))
    print("orders per round=%s announced per round=%s" % (stats["orders"], stats["announced"]))
    print("request outcomes over all rounds=%s" % dict(totals))
    print("engine=sqlite %s (NOT MySQL/MariaDB) mutant=%r" % (sqlite3.sqlite_version, MUTANT or "none"))
    return 1 if bad else 0


if __name__ == "__main__":
    sys.exit(main())
