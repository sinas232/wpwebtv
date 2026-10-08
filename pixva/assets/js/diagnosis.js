/**
 * PIXVA diagnosis wizard enhancement (§06–07).
 *
 * The wizard is a complete server-rendered GET flow (step 1 → step 2 →
 * result) validated in PHP. This script only adds:
 * - inline validation of the required problem choice (no round trip);
 * - brand → model suggestions via GET /pixva/v1/models;
 * - a loading state while the next step loads;
 * - analytics: diagnosis_started (step 1 submitted), diagnosis_completed
 *   (result view, fired by app.js from data-track-view) and
 *   diagnosis_abandoned (page left mid-wizard; sessionStorage, no PII).
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-diagnosis]');
	var cfg = window.PIXVA || {};
	var pixva = window.pixva || {};
	if (!root || !pixva.track) {
		return;
	}
	var KEY = 'pixva_diag';
	var step = root.getAttribute('data-step') || '1';
	var status = root.querySelector('[data-wizard-status]');
	var leavingInside = false;

	function store(v) {
		try {
			if (v === null) {
				sessionStorage.removeItem(KEY);
			} else {
				sessionStorage.setItem(KEY, v);
			}
		} catch (e) { /* storage disabled */ }
	}

	function stored() {
		try {
			return sessionStorage.getItem(KEY);
		} catch (e) {
			return null;
		}
	}

	function say(type, msg) {
		if (!status) {
			return;
		}
		status.textContent = '';
		status.appendChild(pixva.notice(type, msg, true));
		if (type === 'error') {
			status.focus();
		}
	}

	/* Completed: stop abandonment tracking. */
	if (step === 'result') {
		store(null);
	}

	/* Step 1: inline validation + started event. */
	var form1 = root.querySelector('form[data-wizard-step="1"]');
	if (form1) {
		var err = form1.querySelector('#dg-problem-err');
		form1.addEventListener('change', function (ev) {
			if (ev.target.name === 'problem' && err) {
				err.hidden = true;
				err.textContent = '';
				form1.querySelector('.choice-grid').classList.remove('field--error');
			}
		});
		form1.addEventListener('submit', function (ev) {
			var chosen = form1.querySelector('[name="problem"]:checked');
			if (!chosen) {
				ev.preventDefault();
				var msg = (cfg.i18n && cfg.i18n.required) || '';
				if (err) {
					err.textContent = msg;
					err.hidden = false;
					form1.querySelector('.choice-grid').classList.add('field--error');
				}
				say('error', msg);
				var first = form1.querySelector('[name="problem"]');
				if (first) {
					first.focus();
				}
				return;
			}
			if (stored() !== 'active') {
				pixva.track('diagnosis_started', { problem: chosen.value, brand_known: !!form1.querySelector('[name="brand"]').value });
			}
			store('active');
			leavingInside = true;
			root.setAttribute('aria-busy', 'true');
			say('info', (cfg.i18n && cfg.i18n.loading) || '');
		});

		/* Brand → model suggestions. */
		var brand = form1.querySelector('[data-models-for]');
		var list = brand ? document.getElementById(brand.getAttribute('data-models-for')) : null;
		if (brand && list && window.fetch && cfg.rest) {
			brand.addEventListener('change', function () {
				list.textContent = '';
				var id = parseInt(brand.value, 10);
				if (!id) {
					return;
				}
				fetch(cfg.rest + 'models?brand=' + id, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
					.then(function (r) { return r.ok ? r.json() : { items: [] }; })
					.then(function (d) {
						(d.items || []).forEach(function (m) {
							list.appendChild(pixva.el('option', { value: m }));
						});
					})
					.catch(function () { /* suggestions are optional */ });
			});
		}
	}

	/* Step 2: loading state; links/forms inside the wizard are not abandonment. */
	var form2 = root.querySelector('form[data-wizard-step="2"]');
	if (form2) {
		form2.addEventListener('submit', function () {
			leavingInside = true;
			root.setAttribute('aria-busy', 'true');
			say('info', (cfg.i18n && cfg.i18n.loading) || '');
		});
		if (stored() !== 'active') {
			store('active'); // arrived from a deep link with a problem preselected
		}
	}
	root.addEventListener('click', function (ev) {
		if (ev.target.closest && ev.target.closest('a[href]')) {
			var a = ev.target.closest('a[href]');
			leavingInside = a.href.split('?')[0] === window.location.href.split('?')[0];
		}
	});

	/* Abandonment: left the page while a wizard was in progress. */
	window.addEventListener('pagehide', function () {
		if (step !== 'result' && stored() === 'active' && !leavingInside) {
			pixva.track('diagnosis_abandoned', { step: step === '2' ? 2 : 1 });
			store(null);
		}
	});

	/* bfcache restore: clear the busy state. */
	window.addEventListener('pageshow', function (ev) {
		if (ev.persisted) {
			root.removeAttribute('aria-busy');
			leavingInside = false;
		}
	});
}());
