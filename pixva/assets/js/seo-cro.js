/*!
 * Pixva SEO/CRO Modules — لایه ۴٫۰٫۰ (پوسته بنتو)
 * رفتار ماژول‌های رندرشده در هاب‌ها و ویجت‌های المنتور:
 * - فرم اعزام فوری [data-bx-express] (REST pixva/v1/express-booking)
 * - جدول قیمت پویا [data-bx-price] (REST pixva/v1/price-table)
 * ۱۰۰٪ Vanilla JS — بدون jQuery و بدون کتابخانه بیرونی.
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaSeoCro || { restUrl: '/wp-json/pixva/v1', i18n: {} };
	var i18n = cfg.i18n || {};
	var restBase = String(cfg.restUrl || '').replace(/\/$/, '');

	function qs(selector, root) {
		return (root || document).querySelector(selector);
	}

	function qsa(selector, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(selector));
	}

	function text(el, value) {
		if (el) {
			el.textContent = value;
		}
	}

	function normalizePhone(value) {
		var raw = String(value || '')
			.replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
			.replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); })
			.replace(/[^0-9]/g, '');

		if (raw.indexOf('98') === 0 && raw.length === 12) {
			raw = '0' + raw.slice(2);
		}
		if (raw.indexOf('9') === 0 && raw.length === 10) {
			raw = '0' + raw;
		}
		return raw;
	}

	function say(el, message, kind) {
		if (!el) {
			return;
		}
		el.textContent = message || '';
		el.classList.toggle('is-visible', Boolean(message));
		el.classList.toggle('bx-msg--ok', kind === 'ok');
		el.classList.toggle('bx-msg--err', kind === 'err');
	}

	/* ------------------------------------------------------------------
	 * جدول قیمت پویا (ماژول نرخ‌نامه با پوسته بنتو)
	 * ---------------------------------------------------------------- */
	function initPriceModule(section) {
		var brandSel = qs('[data-bx-price-brand]', section);
		var sizeSel = qs('[data-bx-price-size]', section);
		var body = qs('[data-bx-price-rows]', section);
		var caption = qs('[data-bx-price-caption]', section);
		var status = qs('[data-bx-price-status]', section);
		var pills = qsa('[data-bx-price-pill]', section);

		if (!brandSel || !sizeSel || !body) {
			return;
		}

		function renderRows(rows) {
			body.textContent = '';
			(rows || []).forEach(function (row) {
				var tr = document.createElement('tr');
				tr.setAttribute('data-service', row.service || '');

				var th = document.createElement('th');
				th.setAttribute('scope', 'row');
				th.textContent = row.label || '';
				tr.appendChild(th);

				var tdMin = document.createElement('td');
				tdMin.className = 'bx-price-num';
				var tdMax = document.createElement('td');
				tdMax.className = 'bx-price-num';

				if (row.panel_replacement || !row.min) {
					tdMin.setAttribute('colspan', '2');
					tdMin.className = 'bx-price-quotecell';
					tdMin.textContent = i18n.panelQuote || '';
					tr.appendChild(tdMin);
				} else {
					tdMin.textContent = row.min_label || '';
					tdMax.textContent = row.max_label || '';
					tr.appendChild(tdMin);
					tr.appendChild(tdMax);
				}

				var tdDays = document.createElement('td');
				tdDays.textContent = row.days || '—';
				tr.appendChild(tdDays);

				body.appendChild(tr);
			});
		}

		function syncPills() {
			pills.forEach(function (pill) {
				var on = pill.getAttribute('data-bx-price-pill') === brandSel.value;
				pill.classList.toggle('is-active', on);
				pill.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
		}

		function update() {
			var brand = brandSel.value;
			var size = sizeSel.value;
			section.setAttribute('data-price-active-brand', brand);
			section.setAttribute('data-price-active-size', size);
			syncPills();
			text(status, i18n.updating || '');

			window.fetch(restBase + '/price-table?brand=' + encodeURIComponent(brand) + '&size=' + encodeURIComponent(size), {
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().then(function (payload) {
					if (!response.ok || !payload || !payload.data) {
						throw new Error(payload && payload.message ? payload.message : (i18n.error || ''));
					}
					return payload.data;
				});
			}).then(function (data) {
				renderRows(data.rows);
				text(caption, data.caption || '');
				text(status, data.status_label || '');
			}).catch(function (error) {
				text(status, error && error.message ? error.message : (i18n.error || ''));
			});
		}

		pills.forEach(function (pill) {
			pill.addEventListener('click', function () {
				brandSel.value = pill.getAttribute('data-bx-price-pill');
				update();
			});
		});

		brandSel.addEventListener('change', update);
		sizeSel.addEventListener('change', update);
		syncPills();
	}

	/* ------------------------------------------------------------------
	 * فرم اعزام فوری (ماژول express با پوسته بنتو)
	 * ---------------------------------------------------------------- */
	function initExpressForm(form) {
		var phone = qs('[data-bx-express-phone]', form);
		var details = qs('[data-bx-express-details]', form);
		var brandSel = qs('[data-bx-express-brand]', form);
		var submit = qs('[data-bx-express-submit]', form);
		var msg = qs('[data-bx-express-msg]', form);

		function markField(field, invalid) {
			if (field) {
				field.classList.toggle('is-invalid', Boolean(invalid));
			}
		}

		if (phone) {
			phone.addEventListener('input', function () {
				markField(phone, false);
			});
		}
		if (details) {
			details.addEventListener('input', function () {
				markField(details, false);
			});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			say(msg, '', '');

			var phoneValue = normalizePhone(phone ? phone.value : '');
			var detailsValue = details ? String(details.value || '').trim() : '';

			if (!phoneValue) {
				say(msg, i18n.needPhone || '', 'err');
				markField(phone, true);
				if (phone) { phone.focus(); }
				return;
			}
			if (!/^09[0-9]{9}$/.test(phoneValue)) {
				say(msg, i18n.badPhone || '', 'err');
				markField(phone, true);
				if (phone) { phone.focus(); }
				return;
			}
			markField(phone, false);

			if (detailsValue.length < 3) {
				say(msg, i18n.needDetails || '', 'err');
				markField(details, true);
				if (details) { details.focus(); }
				return;
			}
			markField(details, false);

			var honeypot = qs('input[name="pixva_hp"]', form);
			var sourceInput = qs('input[name="source"]', form);
			var body = {
				phone: phoneValue,
				details: detailsValue,
				brand: brandSel ? brandSel.value : '',
				source: sourceInput ? sourceInput.value : 'home',
				nonce: cfg.nonce || '',
				pixva_hp: honeypot ? honeypot.value : ''
			};

			if (submit) {
				submit.classList.add('is-busy');
				submit.setAttribute('disabled', 'disabled');
			}
			say(msg, i18n.sending || '', '');

			window.fetch(restBase + '/express-booking', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			}).then(function (response) {
				return response.json().then(function (payload) {
					if (!response.ok || !payload || !payload.success) {
						throw new Error(payload && payload.message ? payload.message : (i18n.error || ''));
					}
					return payload.data || {};
				});
			}).then(function (data) {
				if (submit) {
					submit.classList.remove('is-busy');
				}
				var line = data.message || '';
				if (data.estimate) {
					line += ' — ' + data.estimate;
				}
				say(msg, line, 'ok');
				form.reset();
			}).catch(function (error) {
				if (submit) {
					submit.classList.remove('is-busy');
					submit.removeAttribute('disabled');
				}
				say(msg, error && error.message ? error.message : (i18n.error || ''), 'err');
			});
		});
	}

	function scan(root) {
		var scope = root || document;
		qsa('[data-bx-price]', scope).forEach(initPriceModule);
		qsa('[data-bx-express]', scope).forEach(initExpressForm);
	}

	function boot() {
		scan(document);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	document.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function ($scope) {
				if ($scope && $scope[0]) {
					scan($scope[0]);
				}
			});
		}
	});
}(window, document));
