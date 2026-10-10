#!/usr/bin/env node
/**
 * PIXVA harness runner — drives the runtime test suites against a local
 * WordPress Playground test server.
 *
 * Prerequisites (see tools/harness/README.md):
 *   1. Test server running (default http://127.0.0.1:9412) with the
 *      mu-plugin mounted and PIXVA_TEST_TOKEN set to "pixva-test-token".
 *   2. A clean install for a reproducible run (restart the server).
 *
 * Stages:
 *   A. POST /wp-json/pixva-test/v1/run   → in-process suite (ids, CAS, caps…)
 *   B. bash tools/harness/http-tests.sh  → end-to-end HTTP flows
 *
 * Honest scope: WordPress 7.1.3 + SQLite + PHP-wasm, single worker.
 * Sequential replays, not true parallel writers; no real SMTP/MySQL/webserver.
 */
'use strict';

const { spawnSync } = require('node:child_process');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..', '..');
const BASE = process.env.PIXVA_BASE || 'http://127.0.0.1:9412';
const TOKEN = process.env.PIXVA_TEST_TOKEN || 'pixva-test-token';
const TOKEN_HEADER = 'X-Pixva-Test';

async function unitStage() {
	const res = await fetch(`${BASE}/wp-json/pixva-test/v1/run`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', [TOKEN_HEADER]: TOKEN },
		body: '{}',
	}).catch((err) => {
		console.error(`UNIT  cannot reach ${BASE}: ${err.message}`);
		console.error('      start the test server first (tools/harness/README.md)');
		process.exit(2);
	});
	if (!res.ok) {
		console.error(`UNIT  HTTP ${res.status}`);
		process.exit(2);
	}
	const data = await res.json();
	console.log(`UNIT  wp=${data.wp} php=${data.php} passed=${data.passed} failed=${data.failed}`);
	let failed = 0;
	for (const t of data.tests || []) {
		const mark = t.status === 'PASS' ? 'PASS' : 'FAIL';
		if (mark === 'FAIL') failed += 1;
		console.log(`  ${mark}  ${t.test} (${t.assertions} assertions, ${t.ms}ms)`);
		for (const f of t.failures || []) console.log(`        - ${f}`);
	}
	return failed;
}

function httpStage() {
	const res = spawnSync('bash', [path.join(ROOT, 'tools', 'harness', 'http-tests.sh')], {
		cwd: ROOT,
		encoding: 'utf8',
		env: { ...process.env, PIXVA_BASE: BASE, PIXVA_TEST_TOKEN: TOKEN },
		stdio: 'inherit',
	});
	return res.status === 0 ? 0 : 1;
}

(async () => {
	const unitFailed = await unitStage();
	const httpFailed = httpStage();
	const failed = unitFailed + httpFailed;
	console.log('----------------------------------------');
	console.log(failed ? `RESULT: FAIL (${failed} failing checks)` : 'RESULT: ALL SUITES PASS');
	process.exit(failed ? 1 : 0);
})();
