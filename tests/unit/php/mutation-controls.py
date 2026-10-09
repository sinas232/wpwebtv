#!/usr/bin/env python3
"""
Negative controls for tests/unit/php/order-security.test.php.

Each control applies ONE mutation (removes or weakens one safety check) to a
temporary copy of pixva/inc/*.php and runs the PHP suite. A control PASSES when
the suite reports at least one FAIL. A control that leaves the suite green means
the test does not protect that behaviour.

The repository is never modified. Usage:
  PHP_CLI=php-wasm-cli python3 tests/unit/php/mutation-controls.py
"""

import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
PHP_CLI = os.environ.get("PHP_CLI", "php-wasm-cli")

# (name, file under pixva/inc, exact text that must occur once, replacement)
MUTATIONS = [
    # Claims (round 1)
    ("C1 claim create is not atomic (always succeeds)", "security.php",
     "if ( pixva_create_once( $name, $pending ) ) {", "if ( true ) {"),
    ("C2 finish without the ownership check (overwrites)", "security.php",
     "$ok = pixva_replace_option_row( 'pixva_sub_' . $id, $pending, $done );",
     "$ok = pixva_replace_option_row( 'pixva_sub_' . $id, (string) pixva_option_value( 'pixva_sub_' . $id ), $done ) || true;"),
    ("C3 IPv6 scoped by full address (no /64)", "security.php",
     "return 'v6:' . inet_ntop( substr( $bin, 0, 8 ) . str_repeat( \"\\0\", 8 ) );",
     "return 'v6:' . inet_ntop( $bin );"),
    # Status lock (round 1)
    ("L1 no fencing check before writes", "repairs.php",
     "if ( null !== $owner && ! pixva_order_lock_held( $order_id, $owner ) ) {", "if ( false ) {"),
    # Order placement (round 2)
    ("O1 no adoption of an order a crashed attempt inserted", "repairs.php",
     "\t\t$found = pixva_orders_by_submission( $sid );", "\t\t$found = array();"),
    ("O2 link is read-then-write (no compare on the old value)", "repairs.php",
     "if ( pixva_replace_option_row( $name, $mine, $linked ) ) {",
     "if ( pixva_replace_option_row( $name, (string) pixva_option_value( $name ), $linked ) ) {"),
    ("O3 announce at insert, before the link", "repairs.php",
     "\tpixva_write_order_data( (int) $id, $d, $code );\n\treturn (int) $id;",
     "\tpixva_write_order_data( (int) $id, $d, $code );\n\tdo_action( 'pixva_order_created', (int) $id );\n\treturn (int) $id;"),
    ("O4 order not named by the submission id", "repairs.php",
     "$args['post_name'] = $sid;", "// removed"),
    ("O5 no removal of unlinked duplicates on the link path", "repairs.php",
     "\t\t\tpixva_remove_duplicate_orders( $sid, $id );\n\t\t\tdo_action( 'pixva_order_created', $id );",
     "\t\t\tdo_action( 'pixva_order_created', $id );"),
    ("O6 linked reservation does not short-circuit (re-announces)", "repairs.php",
     "\t\t\t\tpixva_remove_duplicate_orders( $sid, (int) $row['o'] );\n\t\t\t\treturn (int) $row['o'];",
     "\t\t\t\tdo_action( 'pixva_order_created', (int) $row['o'] );\n\t\t\t\treturn (int) $row['o'];"),
    ("O7 fence ignored: a stalled attempt announces after losing the link", "repairs.php",
     "if ( pixva_replace_option_row( $name, $mine, $linked ) ) {", "if ( true ) {"),
    ("O8 prune deletes linked reservations after the claim TTL", "security.php",
     "$ttl = 'linked' === $row['s'] ? PIXVA_ORDER_LINK_TTL : PIXVA_CLAIM_PENDING_TTL;",
     "$ttl = PIXVA_CLAIM_PENDING_TTL;"),
    # Lookup budget (round 2)
    ("B1 general budget refuses correct lookups too", "repairs.php",
     "\t$id = pixva_find_order_by_code( $code );\n\tif ( $id && pixva_order_phone_matches( $id, $phone ) ) {",
     "\tif ( pixva_rate_exhausted( 'lookup', 20, 10 * MINUTE_IN_SECONDS ) ) {\n\t\treturn new WP_Error( 'rate', 'x', array( 'status' => 429 ) );\n\t}\n\t$id = pixva_find_order_by_code( $code );\n\tif ( $id && pixva_order_phone_matches( $id, $phone ) ) {"),
    # Client address (round 2)
    ("I1 forwarded header trusted from any peer", "security.php",
     "if ( array() === $trusted || ! pixva_ip_in_list( $remote, $trusted ) ) {", "if ( false ) {"),
    ("I2 chain walked from the left (spoofable)", "security.php",
     "foreach ( array_reverse( array_map( 'trim', explode( ',', $raw ) ) ) as $hop ) {",
     "foreach ( array_map( 'trim', explode( ',', $raw ) ) as $hop ) {"),
    # Read-only audit (H-section tests).
    ("A1 audit does not report a link to a missing order", "audit.php",
     "if ( ! isset( $orders_by_id[ $oid ] ) ) {", "if ( false ) {"),
    ("A2 audit does not group duplicate submission ids", "audit.php",
     "if ( count( $ids ) > 1 ) {\n\t\t\t$add( 'submission_duplicate_orders'", "if ( false ) {\n\t\t\t$add( 'submission_duplicate_orders'"),
    ("A3 audit does not report unreferenced photo files", "audit.php",
     "if ( ! isset( $photo_owner[ $file ] ) ) {", "if ( false ) {"),
    ("A4 audit gains a write (deletes an unreferenced file)", "audit.php",
     "$add( 'photo_unreferenced', 'warn', 'photo', $file,", "@unlink( $private_dir_gone ?? '' ); $add( 'photo_unreferenced', 'warn', 'photo', $file,"),
]


def apply(tmp, file_name, old, new):
    path = os.path.join(tmp, "pixva", "inc", file_name)
    with open(path, encoding="utf-8") as fh:
        text = fh.read()
    count = text.count(old)
    if count != 1:
        raise SystemExit("pattern occurs %d times in %s: %r" % (count, file_name, old[:60]))
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(text.replace(old, new, 1))


def run_suite(tmp):
    env = dict(os.environ, PHP="8.3")
    proc = subprocess.run(
        [PHP_CLI, os.path.join(tmp, "tests", "unit", "php", "order-security.test.php")],
        capture_output=True, text=True, env=env, timeout=900,
    )
    lines = proc.stdout.splitlines()
    fails = [l for l in lines if l.startswith("FAIL")]
    summary = [l for l in lines if "passed," in l]
    return fails, summary[-1] if summary else "(no summary line)"


def main():
    survivors = []
    for name, file_name, old, new in MUTATIONS:
        tmp = tempfile.mkdtemp(prefix="pixva-mut-")
        try:
            shutil.copytree(os.path.join(ROOT, "pixva", "inc"), os.path.join(tmp, "pixva", "inc"))
            os.makedirs(os.path.join(tmp, "tests", "unit", "php"))
            shutil.copy(os.path.join(ROOT, "tests", "unit", "php", "order-security.test.php"),
                        os.path.join(tmp, "tests", "unit", "php"))
            apply(tmp, file_name, old, new)
            fails, summary = run_suite(tmp)
        finally:
            shutil.rmtree(tmp, ignore_errors=True)
        caught = len(fails) > 0
        print("%s  %s  [%s] %d failing checks" % ("CAUGHT  " if caught else "SURVIVED", name, summary, len(fails)))
        if not caught:
            survivors.append(name)
    print("controls=%d survivors=%d" % (len(MUTATIONS), len(survivors)))
    return 1 if survivors else 0


if __name__ == "__main__":
    sys.exit(main())
