/*!
 * Pixva Bento Engine — لایه ۴٫۰٫۰ (Master Prompt v14)
 * موتور صفحه اصلی بنتو: ناوبری قرصی، دروئر تمام‌صفحه، موتور رزرو تب‌دار
 * با برآورد زنده، انتخابگر عیب، رهگیر اعزام، استعلام گارانتی و مقایسه قیمت.
 * ۱۰۰٪ Vanilla JS — بدون jQuery و بدون هیچ کتابخانه بیرونی.
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaBento || { restUrl: '/wp-json/pixva/v1', i18n: {} };
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

	function toFa(value) {
		return String(value).replace(/[0-9]/g, function (d) {
			return '۰۱۲۳۴۵۶۷۸۹'[Number(d)];
		});
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

	function busy(el, on) {
		if (!el) {
			return;
		}
		el.classList.toggle('is-busy', Boolean(on));
		if (on) {
			el.setAttribute('disabled', 'disabled');
		} else {
			el.removeAttribute('disabled');
		}
	}

	function api(path, options) {
		return window.fetch(restBase + path, options || {}).then(function (response) {
			return response.json().then(function (payload) {
				if (!response.ok) {
					var err = new Error(payload && payload.message ? payload.message : (i18n.error || 'Error'));
					err.payload = payload;
					throw err;
				}
				return payload;
			});
		});
	}

	/* ------------------------------------------------------------------
	 * ۱) هدر قرصی شناور + دروئر تمام‌صفحه
	 * ---------------------------------------------------------------- */
	var headerReady = false;

	function initHeader() {
		if (headerReady) {
			return;
		}
		headerReady = true;

		var header = qs('[data-bx-header]');
		if (header) {
			var onScroll = function () {
				header.classList.toggle('is-stuck', window.scrollY > 8);
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			onScroll();
		}

		var burger = qs('[data-bx-burger]');
		var drawer = qs('[data-bx-drawer]');
		if (!burger || !drawer) {
			return;
		}
		var closeBtn = qs('[data-bx-drawer-close]', drawer);

		function setOpen(open) {
			drawer.classList.toggle('is-open', open);
			drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			document.documentElement.style.overflow = open ? 'hidden' : '';
			if (open) {
				var first = qs('a, button', drawer);
				if (first && typeof first.focus === 'function') {
					first.focus({ preventScroll: true });
				}
			}
		}

		burger.addEventListener('click', function () {
			setOpen(!drawer.classList.contains('is-open'));
		});
		if (closeBtn) {
			closeBtn.addEventListener('click', function () { setOpen(false); });
		}
		drawer.addEventListener('click', function (event) {
			if (event.target === drawer) {
				setOpen(false);
			}
		});
		qsa('a', drawer).forEach(function (link) {
			link.addEventListener('click', function () { setOpen(false); });
		});
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
				setOpen(false);
				burger.focus();
			}
		});
	}

	/* ------------------------------------------------------------------
	 * ۲) تب‌های موتور رزرو
	 * ---------------------------------------------------------------- */
	function initTabs(root) {
		var tabs = qsa('[data-bx-tab]', root);
		if (!tabs.length) {
			return;
		}

		function activate(name) {
			tabs.forEach(function (tab) {
				var on = tab.getAttribute('data-bx-tab') === name;
				tab.classList.toggle('is-active', on);
				tab.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			qsa('[data-bx-panel]', root).forEach(function (panel) {
				var on = panel.getAttribute('data-bx-panel') === name;
				panel.classList.toggle('is-active', on);
				if (on) {
					panel.removeAttribute('hidden');
				} else {
					panel.setAttribute('hidden', 'hidden');
				}
			});
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				activate(tab.getAttribute('data-bx-tab'));
			});
		});

		root.__bxActivateTab = activate;
	}

	/* ------------------------------------------------------------------
	 * ۳) حافظه مشترک نرخ‌نامه (price-table)
	 * ---------------------------------------------------------------- */
	var priceCache = {};

	function fetchPriceTable(brand, size) {
		var key = brand + '|' + size;
		if (priceCache[key]) {
			return priceCache[key];
		}
		priceCache[key] = api('/price-table?brand=' + encodeURIComponent(brand) + '&size=' + encodeURIComponent(size))
			.then(function (payload) {
				return payload && payload.data ? payload.data : null;
			})
			.catch(function () {
				delete priceCache[key];
				return null;
			});
		return priceCache[key];
	}

	/* ------------------------------------------------------------------
	 * ۴) موتور رزرو: برآورد زنده + ثبت درخواست
	 * ---------------------------------------------------------------- */
	function initBooking(root) {
		var form = qs('[data-bx-booking-form]', root);
		if (!form) {
			return;
		}

		var brandSel = qs('[data-bx-brand]', form);
		var sizeSel = qs('[data-bx-size]', form);
		var serviceSel = qs('[data-bx-service]', form);
		var valueEl = qs('[data-bx-estimate-value]', root);
		var daysEl = qs('[data-bx-estimate-days]', root);
		var msgEl = qs('[data-bx-booking-msg]', form);
		var submit = qs('[data-bx-submit]', form);
		var phone = qs('[data-bx-phone]', form);
		var details = qs('[data-bx-details]', form);

		function refreshEstimate() {
			if (!brandSel || !sizeSel || !valueEl) {
				return;
			}
			var brand = brandSel.value;
			var size = sizeSel.value;
			var service = serviceSel ? serviceSel.value : '';
			valueEl.textContent = i18n.loading || '…';

			fetchPriceTable(brand, size).then(function (data) {
				if (!data || !data.rows) {
					valueEl.textContent = i18n.estimateWait || '';
					text(daysEl, '');
					return;
				}
				var row = null;
				data.rows.forEach(function (candidate) {
					if (candidate.service === service) {
						row = candidate;
					}
				});
				if (!row) {
					row = data.rows[0];
				}
				if (row.panel_replacement || !row.min) {
					valueEl.textContent = i18n.panelQuote || '';
					text(daysEl, row.days || '');
					return;
				}
				valueEl.textContent = row.min_label + ' — ' + row.max_label + ' ' + (data.currency || i18n.toman || '');
				text(daysEl, row.days ? (i18n.daysPrefix || 'مدت تعمیر: %s').replace('%s', row.days) : '');
			});
		}

		[brandSel, sizeSel, serviceSel].forEach(function (el) {
			if (el) {
				el.addEventListener('change', refreshEstimate);
			}
		});

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			say(msgEl, '', '');

			var phoneValue = normalizePhone(phone ? phone.value : '');
			var detailsValue = details ? String(details.value || '').trim() : '';

			if (phone) {
				phone.classList.toggle('is-invalid', !/^09[0-9]{9}$/.test(phoneValue));
			}
			if (!/^09[0-9]{9}$/.test(phoneValue)) {
				say(msgEl, i18n.badPhone || '', 'err');
				if (phone) { phone.focus(); }
				return;
			}
			if (detailsValue.length < 3) {
				say(msgEl, i18n.needDetails || '', 'err');
				if (details) {
					details.classList.add('is-invalid');
					details.focus();
				}
				return;
			}
			if (details) {
				details.classList.remove('is-invalid');
			}

			var honeypot = qs('input[name="pixva_hp"]', form);
			var body = {
				phone: phoneValue,
				details: detailsValue,
				brand: brandSel ? brandSel.value : '',
				service: serviceSel ? serviceSel.value : '',
				size: sizeSel ? sizeSel.value : '',
				source: (qs('input[name="source"]', form) || {}).value || 'bento-hero',
				nonce: cfg.nonce || (qs('input[name="nonce"]', form) || {}).value || '',
				pixva_hp: honeypot ? honeypot.value : ''
			};

			busy(submit, true);
			say(msgEl, i18n.loading || '', '');

			api('/express-booking', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			}).then(function (payload) {
				busy(submit, false);
				var data = payload && payload.data ? payload.data : {};

				var success = document.createElement('div');
				success.className = 'bx-success';
				success.setAttribute('role', 'status');

				var icon = document.createElement('span');
				icon.className = 'bx-success__icon';
				icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

				var title = document.createElement('h3');
				title.className = 'bx-fault-view__title';
				title.textContent = data.message || '';

				success.appendChild(icon);
				success.appendChild(title);

				if (data.code) {
					var code = document.createElement('span');
					code.className = 'bx-success__code';
					code.textContent = data.code;
					success.appendChild(code);
				}
				if (data.estimate) {
					var estimate = document.createElement('p');
					estimate.className = 'bx-cell__lead';
					estimate.textContent = data.estimate;
					success.appendChild(estimate);
				}

				form.parentNode.replaceChild(success, form);
			}).catch(function (error) {
				busy(submit, false);
				say(msgEl, error && error.message ? error.message : (i18n.error || ''), 'err');
			});
		});

		if (phone) {
			phone.addEventListener('input', function () {
				phone.classList.remove('is-invalid');
			});
		}

		refreshEstimate();
	}

	/* ------------------------------------------------------------------
	 * ۵) استعلام قیمت فوری (تب دوم موتور رزرو)
	 * ---------------------------------------------------------------- */
	function initQuote(root) {
		var brandSel = qs('[data-bx-quote-brand]', root);
		var sizeSel = qs('[data-bx-quote-size]', root);
		var body = qs('[data-bx-quote-rows]', root);
		var msgEl = qs('[data-bx-quote-msg]', root);
		if (!brandSel || !sizeSel || !body) {
			return;
		}

		function renderRows(data) {
			body.textContent = '';
			(data.rows || []).forEach(function (row) {
				var tr = document.createElement('tr');

				var th = document.createElement('th');
				th.setAttribute('scope', 'row');
				th.textContent = row.label;
				tr.appendChild(th);

				var tdPrice = document.createElement('td');
				tdPrice.className = 'bx-price-num';
				tdPrice.textContent = row.panel_replacement || !row.min
					? (i18n.afterVisit || '')
					: row.min_label + ' — ' + row.max_label;
				tr.appendChild(tdPrice);

				var tdDays = document.createElement('td');
				tdDays.textContent = row.days || '—';
				tr.appendChild(tdDays);

				body.appendChild(tr);
			});
		}

		function update() {
			say(msgEl, i18n.updating || '', '');
			fetchPriceTable(brandSel.value, sizeSel.value).then(function (data) {
				if (!data) {
					say(msgEl, i18n.error || '', 'err');
					return;
				}
				renderRows(data);
				say(msgEl, '', '');
			});
		}

		brandSel.addEventListener('change', update);
		sizeSel.addEventListener('change', update);
		update();
	}

	/* ------------------------------------------------------------------
	 * ۶) پیگیری پرونده و استعلام گارانتی (REST /track)
	 * ---------------------------------------------------------------- */
	function initLookup(form) {
		var code = qs('[data-bx-track-code]', form);
		var phone = qs('[data-bx-track-phone]', form);
		var msgEl = qs('[data-bx-lookup-msg]', form);
		var result = qs('[data-bx-lookup-result]', form);
		var submit = qs('button[type="submit"]', form);
		if (!code || !phone || !result) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			say(msgEl, '', '');
			result.classList.remove('is-visible');
			result.textContent = '';

			var codeValue = String(code.value || '').trim();
			var phoneValue = normalizePhone(phone.value);

			if (!codeValue || !/^09[0-9]{9}$/.test(phoneValue)) {
				say(msgEl, i18n.needCode || '', 'err');
				return;
			}

			busy(submit, true);
			say(msgEl, i18n.loading || '', '');

			api('/track?code=' + encodeURIComponent(codeValue) + '&phone=' + encodeURIComponent(phoneValue))
				.then(function (payload) {
					busy(submit, false);
					say(msgEl, '', '');

					var order = (payload && payload.order) || {};

					var status = document.createElement('span');
					status.className = 'bx-lookup__status';
					status.textContent = payload.statusLabel || payload.status || '';

					var rows = document.createElement('dl');
					rows.className = 'bx-lookup__rows';

					[[i18n.resultCode, payload.code || codeValue],
						[i18n.resultDevice, order.device || ''],
						[i18n.resultWarranty, order.warranty || ''],
						[i18n.resultEstimate, payload.estimate || order.estimate || '']
					].forEach(function (pair) {
						if (!pair[1]) {
							return;
						}
						var wrap = document.createElement('div');
						var dt = document.createElement('dt');
						dt.textContent = pair[0] || '';
						var dd = document.createElement('dd');
						dd.textContent = pair[1];
						wrap.appendChild(dt);
						wrap.appendChild(dd);
						rows.appendChild(wrap);
					});

					result.appendChild(status);
					result.appendChild(rows);
					result.classList.add('is-visible');
				})
				.catch(function (error) {
					busy(submit, false);
					say(msgEl, error && error.message ? error.message : (i18n.error || ''), 'err');
				});
		});
	}

	/* ------------------------------------------------------------------
	 * ۷) انتخابگر عیب (Fault Selector)
	 * ---------------------------------------------------------------- */
	function initFault(root) {
		var chips = qsa('[data-bx-fault-chip]', root);
		if (!chips.length) {
			return;
		}

		function activate(id) {
			chips.forEach(function (chip) {
				var on = chip.getAttribute('data-bx-fault-chip') === id;
				chip.classList.toggle('is-active', on);
				chip.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			qsa('[data-bx-fault-view]', root).forEach(function (view) {
				var on = view.getAttribute('data-bx-fault-view') === id;
				if (on) {
					view.removeAttribute('hidden');
				} else {
					view.setAttribute('hidden', 'hidden');
				}
			});
		}

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				activate(chip.getAttribute('data-bx-fault-chip'));
			});
		});

		qsa('[data-bx-fault-book]', root).forEach(function (button) {
			button.addEventListener('click', function () {
				var booking = qs('[data-bx-booking]');
				var details = qs('[data-bx-details]');
				if (booking && details) {
					details.value = button.getAttribute('data-bx-fault-book');
					details.classList.remove('is-invalid');
					if (typeof booking.__bxActivateTab === 'function') {
						booking.__bxActivateTab('book');
					}
					if (typeof booking.scrollIntoView === 'function') {
						try {
							booking.scrollIntoView({ behavior: 'smooth', block: 'center' });
						} catch (e) {
							booking.scrollIntoView();
						}
					}
					window.setTimeout(function () { details.focus({ preventScroll: true }); }, 450);
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * ۸) رهگیر اعزام زنده (REST /dispatch-live حالت دمو)
	 * ---------------------------------------------------------------- */
	function initDispatch(root) {
		var eta = qs('[data-bx-dispatch-eta]', root);
		var bar = qs('[data-bx-dispatch-progress]', root);
		var status = qs('[data-bx-dispatch-status]', root);
		var updated = qs('[data-bx-dispatch-updated]', root);
		var tech = qs('[data-bx-dispatch-tech]', root);
		var skill = qs('[data-bx-dispatch-skill]', root);
		if (!eta || !bar) {
			return;
		}

		var timer = null;

		function paint(payload) {
			if (!payload) {
				return;
			}
			text(eta, toFa(payload.eta != null ? payload.eta : '—'));
			bar.style.width = Math.round(Math.max(0, Math.min(1, Number(payload.progress) || 0)) * 100) + '%';
			text(status, payload.statusLabel || '');
			text(updated, payload.updatedAt ? toFa(payload.updatedAt) : '');
			if (payload.technician) {
				text(tech, payload.technician.name || '');
				text(skill, payload.technician.skill || '');
			}
			var refresh = Math.max(10, Math.min(120, Number(payload.refresh) || 20));
			if (timer) {
				window.clearTimeout(timer);
			}
			timer = window.setTimeout(poll, refresh * 1000);
		}

		function poll() {
			api('/dispatch-live?demo=1').then(paint).catch(function () {
				text(status, i18n.error || '');
			});
		}

		poll();
	}

	/* ------------------------------------------------------------------
	 * ۹) مقایسه قیمت برند (بنتو ۸ ستونه)
	 * ---------------------------------------------------------------- */
	function initPricing(root) {
		var pills = qsa('[data-bx-price-pill]', root);
		var sizeSel = qs('[data-bx-price-size]', root);
		var body = qs('[data-bx-price-rows]', root);
		var caption = qs('[data-bx-price-caption]', root);
		var msgEl = qs('[data-bx-price-msg]', root);
		if (!body) {
			return;
		}

		var brand = root.getAttribute('data-brand') || (pills[0] ? pills[0].getAttribute('data-bx-price-pill') : '');

		function renderRows(data) {
			body.textContent = '';
			(data.rows || []).forEach(function (row) {
				var tr = document.createElement('tr');
				tr.setAttribute('data-service', row.service || '');

				var tdLabel = document.createElement('td');
				var strong = document.createElement('strong');
				strong.textContent = row.label || '';
				tdLabel.appendChild(strong);
				tr.appendChild(tdLabel);

				var tdPrice = document.createElement('td');
				tdPrice.className = 'bx-price-num';
				tdPrice.textContent = row.panel_replacement || !row.min ? '—' : row.min_label + ' — ' + row.max_label;
				tr.appendChild(tdPrice);

				var tdDays = document.createElement('td');
				tdDays.textContent = row.days || '—';
				tr.appendChild(tdDays);

				var tdTag = document.createElement('td');
				var tag = document.createElement('span');
				tag.className = 'bx-price-tag' + (row.panel_replacement ? ' bx-price-tag--warn' : '');
				tag.textContent = row.panel_replacement ? (i18n.afterVisit || '') : (i18n.approved || '');
				tdTag.appendChild(tag);
				tr.appendChild(tdTag);

				body.appendChild(tr);
			});
			text(caption, data.caption || '');
		}

		function update(nextBrand) {
			if (nextBrand) {
				brand = nextBrand;
			}
			pills.forEach(function (pill) {
				var on = pill.getAttribute('data-bx-price-pill') === brand;
				pill.classList.toggle('is-active', on);
				pill.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			root.setAttribute('data-brand', brand);
			say(msgEl, i18n.updating || '', '');

			fetchPriceTable(brand, sizeSel ? sizeSel.value : root.getAttribute('data-size')).then(function (data) {
				if (!data) {
					say(msgEl, i18n.error || '', 'err');
					return;
				}
				renderRows(data);
				say(msgEl, '', '');
			});
		}

		pills.forEach(function (pill) {
			pill.addEventListener('click', function () {
				update(pill.getAttribute('data-bx-price-pill'));
			});
		});
		if (sizeSel) {
			sizeSel.addEventListener('change', function () { update(); });
		}
	}

	/* ------------------------------------------------------------------
	 * ۱۰) ظهور هنگام اسکرول (با احترام به prefers-reduced-motion)
	 * ---------------------------------------------------------------- */
	function initReveal() {
		var items = qsa('.bx-reveal');
		if (!items.length) {
			return;
		}
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduce || !('IntersectionObserver' in window)) {
			items.forEach(function (item) { item.classList.add('is-in'); });
			return;
		}
		var observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-in');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.08 });
		items.forEach(function (item) { observer.observe(item); });
	}

	/* ------------------------------------------------------------------
	 * راه‌اندازی
	 * ---------------------------------------------------------------- */
	function scan(root) {
		var scope = root || document;

		initHeader();

		qsa('[data-bx-booking]', scope).forEach(function (booking) {
			initTabs(booking);
			initBooking(booking);
			initQuote(booking);
		});

		qsa('[data-bx-lookup-form]', scope).forEach(initLookup);
		qsa('[data-bx-fault]', scope).forEach(initFault);
		qsa('[data-bx-dispatch]', scope).forEach(initDispatch);
		qsa('[data-bx-pricing]', scope).forEach(initPricing);
		initReveal();
	}

	function boot() {
		scan(document);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// اسکن مجدد وقتی ویجت‌های المنتور در فرانت‌اند رندر می‌شوند.
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
