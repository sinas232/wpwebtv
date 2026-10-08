/**
 * PIXVA lookups (§09, §12, §13).
 *
 * 1. Tracking / warranty: form[data-lookup] posts {code, phone} as JSON to
 *    /pixva/v1/{track|warranty}. The phone never appears in a URL or in
 *    browser history. The response `html` is rendered server-side by the same
 *    functions the no-JS POST path uses (pixva_order_view /
 *    pixva_warranty_view). Rate limits and lockouts are enforced in PHP.
 * 2. Error-code database: live filtering re-requests the archive URL and swaps
 *    the results region (server-side query, pagination and empty states stay
 *    the single source of truth).
 */
(function () {
	'use strict';

	var cfg = window.PIXVA || {};
	var pixva = window.pixva || {};
	if (!pixva.track || !window.fetch) {
		return;
	}
	var t = cfg.i18n || {};

	/* ---------------- Tracking / warranty ---------------- */

	document.querySelectorAll('form[data-lookup]').forEach(function (form) {
		var endpoint = form.getAttribute('data-lookup');
		var event = form.getAttribute('data-track-event');
		var out = form.parentNode.querySelector('[data-lookup-result]');
		if (!cfg.rest || !out || ['track', 'warranty'].indexOf(endpoint) === -1) {
			return;
		}
		var busy = false;
		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			if (busy) {
				return;
			}
			var code = form.querySelector('[name="code"]');
			var phone = form.querySelector('[name="phone"]');
			var missing = [code, phone].filter(function (c) { return c && c.value.trim() === ''; });
			[code, phone].forEach(function (c) {
				var wrap = c.closest('.field');
				var err = wrap && wrap.querySelector('.field__error');
				var bad = missing.indexOf(c) !== -1;
				if (wrap) { wrap.classList.toggle('field--error', bad); }
				if (err) { err.textContent = bad ? (t.required || '') : ''; err.hidden = !bad; }
				if (bad) { c.setAttribute('aria-invalid', 'true'); } else { c.removeAttribute('aria-invalid'); }
			});
			if (missing.length) {
				missing[0].focus();
				return;
			}
			busy = true;
			pixva.setBusy(form, true);
			out.setAttribute('aria-busy', 'true');
			fetch(cfg.rest + endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
				body: JSON.stringify({ code: code.value.trim(), phone: phone.value.trim() })
			})
				.then(function (r) {
					return r.json().then(function (d) { return { ok: r.ok, status: r.status, d: d }; });
				})
				.then(function (x) {
					out.textContent = '';
					if (x.ok && x.d && typeof x.d.html === 'string') {
						out.innerHTML = x.d.html; // server-rendered, escaped in PHP; no PII
						if (event) {
							pixva.track(event, { result: 'found', status: endpoint === 'track' ? x.d.status : (x.d.warranty && x.d.warranty.state) });
						}
					} else {
						out.appendChild(pixva.notice('error', (x.d && x.d.message) || t.error || '', true));
						if (event) {
							pixva.track(event, { result: x.status === 429 ? 'rate_limited' : 'not_found' });
						}
					}
					out.focus();
				})
				.catch(function () {
					out.textContent = '';
					out.appendChild(pixva.notice('error', t.error || '', true));
				})
				.then(function () {
					busy = false;
					pixva.setBusy(form, false);
					out.removeAttribute('aria-busy');
				});
		});
	});

	/* ---------------- Error-code live search ---------------- */

	var search = document.querySelector('form[data-error-search]');
	var results = document.querySelector('[data-error-results]');
	var count = document.querySelector('[data-error-count]');
	if (search && results && window.DOMParser) {
		var timer = null;
		var seq = 0;
		var lastQuery = null;

		var run = function (fromSubmit) {
			var params = new URLSearchParams(new FormData(search));
			Array.from(params.keys()).forEach(function (k) {
				if (!params.get(k)) {
					params.delete(k);
				}
			});
			var q = params.toString();
			if (q === lastQuery && !fromSubmit) {
				return;
			}
			lastQuery = q;
			var url = search.action.split('?')[0] + (q ? '?' + q : '');
			var my = ++seq;
			results.setAttribute('aria-busy', 'true');
			fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' } })
				.then(function (r) { return r.text(); })
				.then(function (html) {
					if (my !== seq) {
						return;
					}
					var doc = new DOMParser().parseFromString(html, 'text/html');
					var nr = doc.querySelector('[data-error-results]');
					var nc = doc.querySelector('[data-error-count]');
					if (!nr) {
						window.location.assign(url);
						return;
					}
					results.innerHTML = nr.innerHTML;
					if (count && nc) {
						count.textContent = nc.textContent.trim();
					}
					history.replaceState(null, '', url);
					if (q) {
						pixva.track('error_code_search', { results: results.querySelectorAll('.card--error').length, brand_known: params.has('brand') });
					}
				})
				.catch(function () {
					if (my === seq) {
						window.location.assign(url);
					}
				})
				.then(function () {
					if (my === seq) {
						results.removeAttribute('aria-busy');
					}
				});
		};

		search.removeAttribute('data-track-submit'); // the live search tracks itself
		search.addEventListener('submit', function (ev) {
			ev.preventDefault();
			clearTimeout(timer);
			run(true);
		});
		search.addEventListener('input', function (ev) {
			if (ev.target.name !== 'q') {
				return;
			}
			clearTimeout(timer);
			var v = ev.target.value.trim();
			if (v !== '' && v.length < 2) {
				return;
			}
			timer = setTimeout(function () { run(false); }, 400);
		});
		search.addEventListener('change', function (ev) {
			if (ev.target.tagName === 'SELECT') {
				run(false);
			}
		});
	}
}());
