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
     "\t\t\t$add(\n\t\t\t\t'photo_unreferenced',", "\t\t\t@unlink( $private_dir_gone ?? '' );\n\t\t\t$add(\n\t\t\t\t'photo_unreferenced',"),
    # Inbox, Round 4 / F1 (inbox.php)
    ("F1a no adoption of an existing message for the id", "inbox.php",
     "\t\t$found = pixva_inbox_by_submission( $sid );", "\t\t$found = array();"),
    ("F1b no live-attempt guard (a live creating row is taken over)", "inbox.php",
     "if ( is_array( $row ) && 'creating' === ( $row['s'] ?? '' ) && (int) ( $row['t'] ?? 0 ) + PIXVA_CLAIM_PENDING_TTL > pixva_now() ) {",
     "if ( false ) {"),
    ("F1c no fence before insert (stalled attempt inserts after takeover)", "inbox.php",
     "if ( pixva_option_value( $name ) !== $mine ) {", "if ( false ) {"),
    ("F1d marker written before the mail (email can be lost)", "inbox.php",
     "\tdo_action( 'pixva_inbox_before_mail', $sid, (int) $id );\n\tcall_user_func( $send, (int) $id );",
     "\tpixva_replace_option_row( $name, $held, (string) wp_json_encode( array( 's' => 'linked', 'o' => (int) $id, 't' => (int) ( $row['t'] ?? pixva_now() ), 'm' => 1 ) ) );\n\tdo_action( 'pixva_inbox_before_mail', $sid, (int) $id );\n\tcall_user_func( $send, (int) $id );"),
    # Notes compare-and-write, Round 4 / F2 (repairs.php)
    ("F2a notes saved without the fingerprint compare (overwrite)", "repairs.php",
     "if ( '' === $base || ! hash_equals( pixva_notes_fingerprint( $current ), $base ) ) {", "if ( false ) {"),
    # Authorisation, Round 4 / F3 (capabilities.php)
    ("F3a non-responsible technician gets the responsible-technician path", "capabilities.php",
     "if ( $tech_id && $tech_id === (int) $user_id && user_can( $user_id, 'pixva_work_orders' ) ) {",
     "if ( $tech_id && user_can( $user_id, 'pixva_work_orders' ) ) {"),
    # Migration, Round 4 / F7 (migration.php)
    # Round 5 / F1-order, F2, F4, F7
    ("R1 live creating reservation is taken over (busy guard removed)", "repairs.php",
     "if ( is_array( $row ) && 'creating' === ( $row['s'] ?? '' ) && (int) ( $row['t'] ?? 0 ) + PIXVA_CLAIM_PENDING_TTL > pixva_now() ) {",
     "if ( false ) {"),
    ("R2 no fence after before_insert (stale attempt inserts)", "repairs.php",
     "\t\t\tdo_action( 'pixva_order_before_insert', $sid );\n\t\t\t// Fence again after the hook: a takeover during the pause means insert nothing.\n\t\t\tif ( pixva_option_value( $name ) !== $mine ) {\n\t\t\t\tcontinue;\n\t\t\t}",
     "\t\t\tdo_action( 'pixva_order_before_insert', $sid );"),
    ("R3 no fence before the adopt write (stale attempt overwrites)", "repairs.php",
     "\t\t// Fence: a stalled attempt that lost the reservation must not write or insert any order.\n\t\t// Residual: this check and the write below are two statements, not one transaction.\n\t\tif ( pixva_option_value( $name ) !== $mine ) {\n\t\t\tcontinue;\n\t\t}",
     "\t\t// fence removed"),
    ("R4 duplicate order deleted instead of marked superseded", "repairs.php",
     "update_post_meta( $id, '_pixva_superseded_by', (int) $keep );",
     "wp_delete_post( $id, true );"),
    ("R5 failed order insert keeps a live reservation (no release)", "repairs.php",
     "\t\t\t\tpixva_delete_option_row( $name, $mine );\n\t\t\t\treturn $id;",
     "\t\t\t\treturn $id;"),
    ("R6 failed inbox insert keeps a live reservation (no release)", "inbox.php",
     "\t\t\t\tpixva_delete_option_row( $name, $mine );\n\t\t\t\treturn new WP_Error( 'save',",
     "\t\t\t\treturn new WP_Error( 'save',"),
    ("R7 refused status change from the meta-box is dropped silently", "repairs.php",
     "\t\t\tpixva_notes_set_notice( get_current_user_id(), $post_id, 'status' );",
     "\t\t\t// removed"),
    ("R8 technician and warranty saves not under the order lock", "repairs.php",
     "$tw_result = pixva_with_order_lock(",
     "$tw_result = ( static function ( $pid, $fn ) { return $fn(); } )("),
    ("R9 migration step 7 not under the order lock (static)", "migration.php",
     "\t\tpixva_with_order_lock(\n\t\t\t$oid,\n\t\t\tstatic function () use ( $oid ) {\n\t\t\t\tpixva_migrate_order_v2( $oid );",
     "\t\t( static function ( $pid, $fn ) { return $fn(); } )(\n\t\t\t$oid,\n\t\t\tstatic function () use ( $oid ) {\n\t\t\t\tpixva_migrate_order_v2( $oid );"),
    ("F7a no migration lock (second run proceeds)", "migration.php",
     "if ( null === $token ) {\n\t\treturn;", "if ( false ) {\n\t\treturn;"),
    ("F7b valid status overwritten by the fallback", "migration.php",
     "if ( ! isset( pixva_order_statuses()[ (string) get_post_meta( $oid, '_pixva_order_status', true ) ] ) ) {",
     "if ( true ) {"),
    ("F7c history list re-converted on every run (duplicates)", "migration.php",
     "if ( is_array( $steps ) && $steps && ! isset( $steps[0] ) ) {", "if ( is_array( $steps ) && $steps ) {"),
    ("F1e inbox reservations pruned on the order TTL (not the message TTL)", "security.php",
     "$life = 'linked' === $row['s'] ? PIXVA_ORDER_LINK_TTL : PIXVA_CLAIM_PENDING_TTL;\n\t\t\treturn (int) $row['t'] + $life <= $now;",
     "$life = PIXVA_CLAIM_PENDING_TTL;\n\t\t\treturn (int) $row['t'] + $life <= $now;"),
    # Read-only report, Round 4 / F6 (audit.php)
    ("F6a unreferenced photo report drops its size", "audit.php",
     "'bytes'    => $info['bytes'],", "'bytes'    => null,"),
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


def run_control(item):
    name, file_name, old, new = item
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
    # A fatal error or a missing summary line is NOT a detection.
    caught = len(fails) > 0 and "passed," in summary
    return name, caught, summary, len(fails)


def main():
    from concurrent.futures import ThreadPoolExecutor
    workers = int(os.environ.get("MUT_WORKERS", "4"))
    with ThreadPoolExecutor(max_workers=workers) as pool:
        results = list(pool.map(run_control, MUTATIONS))
    survivors = []
    for name, caught, summary, nfail in results:
        print("%s  %s  [%s] %d failing checks" % ("CAUGHT  " if caught else "SURVIVED", name, summary, nfail))
        if not caught:
            survivors.append(name)
    print("controls=%d survivors=%d" % (len(MUTATIONS), len(survivors)))
    return 1 if survivors else 0


if __name__ == "__main__":
    sys.exit(main())
