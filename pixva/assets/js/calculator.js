/**
 * PIXVA price calculator enhancement (§08).
 * No-JS: GET form → server-rendered result. With JS: live result from
 * POST /pixva/v1/estimate, whose `html` is rendered by the same PHP function
 * as the no-JS result (pixva_calc_result) — one markup source. The size
 * field is shown only for services priced by screen size.
 */
(function () {
	'use strict';

	var form = document.querySelector('form[data-calculator]');
	var cfg = window.PIXVA || {};
	var pixva = window.pixva || {};
	if (!form || !pixva.track || !window.fetch || !cfg.rest) {
		return;
	}
	var result = form.querySelector('[data-calc-result]');
	var service = form.querySelector('[name="service"]');
	var size = form.querySelector('[name="size"]');
	var brand = form.querySelector('[name="brand"]');
	var sizeField = form.querySelector('[data-size-field]');
	var started = false;
	var seq = 0;

	function syncSize() {
		if (!sizeField || !service) {
			return;
		}
		var opt = service.options[service.selectedIndex];
		var sized = opt && opt.getAttribute('data-sized') === '1';
		sizeField.hidden = !sized;
		if (!sized && size) {
			size.value = '';
		}
	}

	function start() {
		if (!started) {
			started = true;
			pixva.track('price_calculator_started', {});
		}
	}

	function run(focusResult) {
		if (!service.value) {
			result.textContent = '';
			if (focusResult) {
				result.appendChild(pixva.notice('error', (cfg.i18n && cfg.i18n.required) || '', true));
				service.focus();
			}
			return;
		}
		var my = ++seq;
		result.setAttribute('aria-busy', 'true');
		var body = { service: service.value, size: size ? size.value : '', brand: brand ? parseInt(brand.value || '0', 10) : 0 };
		fetch(cfg.rest + 'estimate', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
			body: JSON.stringify(body)
		})
			.then(function (r) {
				return r.json().then(function (d) { return { ok: r.ok, d: d }; });
			})
			.then(function (x) {
				if (my !== seq) {
					return; // a newer request is in flight
				}
				if (!x.ok) {
					result.textContent = '';
					result.appendChild(pixva.notice('error', (x.d && x.d.message) || (cfg.i18n && cfg.i18n.error) || '', true));
					return;
				}
				result.innerHTML = x.d.html || ''; // server-rendered, escaped by PHP
				pixva.track('price_calculator_completed', { available: !!x.d.available, label: body.service });
				if (focusResult) {
					result.focus();
				}
				var url = new URL(window.location.href);
				['service', 'size', 'brand'].forEach(function (k) {
					if (body[k]) {
						url.searchParams.set(k, String(body[k]));
					} else {
						url.searchParams.delete(k);
					}
				});
				history.replaceState(null, '', url.toString());
			})
			.catch(function () {
				if (my === seq) {
					result.textContent = '';
					result.appendChild(pixva.notice('error', (cfg.i18n && cfg.i18n.error) || '', true));
				}
			})
			.then(function () {
				if (my === seq) {
					result.removeAttribute('aria-busy');
				}
			});
	}

	syncSize();
	form.addEventListener('focusin', start);
	form.addEventListener('change', function () {
		start();
		syncSize();
		run(false);
	});
	form.addEventListener('submit', function (ev) {
		ev.preventDefault();
		start();
		run(true);
	});
}());
