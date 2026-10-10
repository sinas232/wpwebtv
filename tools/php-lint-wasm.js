#!/usr/bin/env node
/**
 * PHP syntax lint for the PIXVA theme using php-wasm-cli (no system PHP).
 *
 * Usage:  node tools/php-lint-wasm.js [files...]
 *         (no args → every PHP file under the pixva theme)
 *
 * Exit code 0 = all files parse, 1 = at least one failure.
 */
'use strict';

const { spawnSync } = require('node:child_process');
const { readdirSync, statSync } = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const CLI = path.join(ROOT, 'node_modules', '.bin', 'php-wasm-cli');

function walk(dir, out = []) {
	for (const entry of readdirSync(dir)) {
		const p = path.join(dir, entry);
		if (statSync(p).isDirectory()) walk(p, out);
		else if (p.endsWith('.php')) out.push(p);
	}
	return out;
}

const files = process.argv.length > 2
	? process.argv
			.slice(2)
			.map((f) => path.resolve(ROOT, f))
			.flatMap((p) => (statSync(p).isDirectory() ? walk(p) : [p]))
			.sort()
	: walk(path.join(ROOT, 'pixva')).sort();

let failed = 0;
for (const file of files) {
	const res = spawnSync(CLI, ['-l', file], { encoding: 'utf8' });
	const out = `${res.stdout || ''}${res.stderr || ''}`;
	if (res.status === 0 && out.includes('No syntax errors')) {
		console.log(`OK    ${path.relative(ROOT, file)}`);
	} else {
		failed += 1;
		console.error(`FAIL  ${path.relative(ROOT, file)}\n${out.trim()}`);
	}
}
console.log(`\n${files.length - failed}/${files.length} PHP files passed php-wasm lint`);
process.exit(failed ? 1 : 0);
