/**
 * ماژول سئو و تبدیل پیکسوا — لایه ۲٫۰٫۰ (Master Prompt v11).
 *
 * فقط دو کار سبک و ضروری:
 *  ۱) فیلتر سریع جدول شفاف قیمت (برند/سایز) با یک واکشی به اندپوینت داخلی.
 *  ۲) ارسال فرم یک‌مرحله‌ای اعزام فوری تکنسین با اعتبارسنجی شماره ۰۹xx.
 *
 * هیچ کتابخانه بیرونی، انیمیشن اسکرول یا قفل‌کننده‌ای بارگذاری نمی‌شود.
 * هر دو بخش بدون JS هم کار می‌کنند (جدول سمت سرور رندر شده و متن کامل است).
 *
 * @package Pixva
 * @since   2.0.0
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaSeoCro || {};
	var i18n = cfg.i18n || {};

	function qs(selector, root) {
		return (root || document).querySelector(selector);
	}

	function text(el, value) {
		if (el) {
			el.textContent = value;
		}
	}

	function normalizePhone(value) {
		var digits = String(value || '').replace(/[^\d]/g, '');
		if (digits.indexOf('0098') === 0) {
			digits = '0' + digits.slice(4);
		} else if (digits.indexOf('98') === 0 && digits.length > 10) {
			digits = '0' + digits.slice(2);
		} else if (digits.charAt(0) !== '0' && digits.length === 10) {
			digits = '0' + digits;
		}
		return digits;
	}

	/* ------------------------------------------------------------------
	 * ۱) فیلتر جدول قیمت
	 * --------------------------------------------------------------- */
	function initPriceTable(section) {
		var brandSel = qs('[data-price-brand]', section);
		var sizeSel = qs('[data-price-size]', section);
		var body = qs('[data-price-body]', section);
		var table = qs('[data-price-table]', section);
		var status = qs('[data-price-status]', section);

		if (!brandSel || !sizeSel || !body || !table || !window.fetch) {
			return;
		}

		var busy = false;

		function renderRows(rows, meta) {
			body.innerHTML = '';

			rows.forEach(function (row) {
				var tr = document.createElement('tr');
				tr.setAttribute('data-service', row.service || '');

				var th = document.createElement('th');
				th.setAttribute('scope', 'row');
				th.textContent = row.label || '';
				tr.appendChild(th);

				if (row.panel_replacement || !row.min) {
					var tdQuote = document.createElement('td');
					tdQuote.setAttribute('colspan', '2');
					tdQuote.className = 'pixva-pricetable__quote';
					tdQuote.textContent = (meta && meta.quote_label) || i18n.panelQuote || '';
					tr.appendChild(tdQuote);
				} else {
					var tdMin = document.createElement('td');
					tdMin.setAttribute('data-min', String(row.min));
					tdMin.textContent = row.min_label || '';
					tr.appendChild(tdMin);

					var tdMax = document.createElement('td');
					tdMax.setAttribute('data-max', String(row.max));
					tdMax.textContent = row.max_label || '';
					tr.appendChild(tdMax);
				}

				var tdDays = document.createElement('td');
				tdDays.textContent = row.days || '';
				tr.appendChild(tdDays);

				body.appendChild(tr);
			});

			// عنوان جدول از رشته ترجمه‌شده سمت سرور ساخته می‌شود (بدون متن سخت‌کد).
			var caption = qs('caption', table);
			if (caption && meta && meta.caption) {
				caption.textContent = meta.caption;
			}
		}

		function update() {
			if (busy) {
				return;
			}

			var brand = brandSel.value;
			var size = sizeSel.value;
			if (!brand || !size) {
				return;
			}

			busy = true;
			section.classList.add('is-updating');
			text(status, i18n.updating || 'در حال به‌روزرسانی جدول قیمت…');

			var url = (cfg.restUrl || '/wp-json/pixva/v1') + '/price-table?brand=' +
				encodeURIComponent(brand) + '&size=' + encodeURIComponent(size);

			window.fetch(url, {
				method: 'GET',
				credentials: 'same-origin',
				headers: { Accept: 'application/json' }
			}).then(function (response) {
				return response.json();
			}).then(function (payload) {
				busy = false;
				section.classList.remove('is-updating');

				if (!payload || !payload.success || !payload.data || !payload.data.rows) {
					text(status, i18n.error || 'خطا در دریافت نرخ‌نامه.');
					return;
				}

				renderRows(payload.data.rows, payload.data);
				text(status, payload.data.status_label || '');
				section.setAttribute('data-price-active-brand', brand);
				section.setAttribute('data-price-active-size', size);
			}).catch(function () {
				busy = false;
				section.classList.remove('is-updating');
				text(status, i18n.error || 'خطا در ارتباط با سرور؛ جدول قبلی نگه داشته شد.');
			});
		}

		brandSel.addEventListener('change', update);
		sizeSel.addEventListener('change', update);
	}

	/* ------------------------------------------------------------------
	 * ۲) فرم اعزام فوری تکنسین
	 * --------------------------------------------------------------- */
	function initExpressForm(form) {
		if (form.getAttribute('data-express-bound') === '1' || !window.fetch) {
			return;
		}
		form.setAttribute('data-express-bound', '1');

		var msg = qs('[data-express-msg]', form);
		var submit = qs('[data-express-submit]', form);
		var phone = qs('[name="phone"]', form);
		var details = qs('[name="details"]', form);
		var nonceField = qs('[data-express-nonce]', form);

		function say(message, kind) {
			if (!msg) {
				return;
			}
			msg.hidden = false;
			msg.textContent = message;
			msg.className = 'pixva-express__msg is-' + (kind || 'info');
		}

		function markField(field, invalid) {
			if (!field) {
				return;
			}
			if (invalid) {
				field.classList.add('is-invalid');
				field.focus();
			} else {
				field.classList.remove('is-invalid');
			}
		}

		if (phone) {
			phone.addEventListener('input', function () {
				if (/^09[0-9]{9}$/.test(normalizePhone(phone.value))) {
					markField(phone, false);
				}
			});
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var phoneValue = normalizePhone(phone ? phone.value : '');

			if (!phoneValue) {
				markField(phone, true);
				say(i18n.needPhone || 'برای هماهنگی اعزام، شماره موبایل الزامی است.', 'error');
				return;
			}

			if (!/^09[0-9]{9}$/.test(phoneValue)) {
				markField(phone, true);
				say(i18n.badPhone || 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', 'error');
				return;
			}

			markField(phone, false);

			if (!details || String(details.value).trim().length < 3) {
				markField(details, true);
				say(i18n.needDetails || 'برند و مشکل دستگاه را کوتاه بنویسید.', 'error');
				return;
			}
			markField(details, false);

			var body = new window.FormData(form);
			if (phone) {
				body.set('phone', phoneValue);
			}
			if (nonceField && !body.get('nonce')) {
				body.set('nonce', nonceField.value || '');
			} else if (!body.get('nonce')) {
				body.set('nonce', cfg.nonce || '');
			}

			if (submit) {
				submit.disabled = true;
			}
			say(i18n.sending || 'در حال ثبت درخواست…', 'busy');

			window.fetch((cfg.restUrl || '/wp-json/pixva/v1') + '/express-booking', {
				method: 'POST',
				body: body,
				headers: { 'X-Pixva-Nonce': (nonceField && nonceField.value) || cfg.nonce || '' },
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().then(function (data) {
					return { ok: response.ok, data: data };
				});
			}).then(function (result) {
				var payload = result.data || {};
				var info = payload.data || payload;

				if (result.ok && payload.success) {
					form.classList.add('is-done');
					if (details) {
						details.value = '';
					}
					say(info.message || ('درخواست ثبت شد. کد پیگیری: ' + (info.code || '')), 'success');
					form.setAttribute('data-express-code', info.code || '');
				} else {
					say((payload.message || i18n.error || 'خطا در ثبت درخواست؛ دوباره تلاش کنید.'), 'error');
					if (submit) {
						submit.disabled = false;
					}
				}
			}).catch(function () {
				say(i18n.error || 'خطا در ارتباط با سرور؛ دوباره تلاش کنید.', 'error');
				if (submit) {
					submit.disabled = false;
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * راه‌اندازی
	 * --------------------------------------------------------------- */
	function scan(root) {
		var scope = root || document;

		var price = qs('.pixva-pricetable', scope);
		if (price) {
			initPriceTable(price);
		}

		Array.prototype.forEach.call(scope.querySelectorAll('[data-express-form]'), initExpressForm);
	}

	function boot() {
		document.documentElement.classList.add('pixva-seo-js');
		scan(document);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaSeoCroScan = scan;

	document.addEventListener('elementor/frontend/init', function () {
		scan(document);
	});
}(window, document));
