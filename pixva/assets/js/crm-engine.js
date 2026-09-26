/**
 * موتور اتوماسیون و CRM پیکسوا (assets/js/crm-engine.js)
 *
 * بخش‌ها:
 *  ۱) جادوگر چهارمرحله‌ای ثبت سفارش تعمیر (برآورد زنده از سرور + کد پیگیری آنی)
 *  ۲) ایستگاه کاری تعمیرکار (فیلتر وضعیت، گزارش فنی با ردیف قطعات، صدور گارانتی)
 *  ۳) پنل دیسپچ پیشخوان (تخصیص تعمیرکار + تغییر وضعیت)
 *  ۴) هولوگرام زنده کارت گارانتی (ماوس/لمس + ژیروسکوپ، بدون کتابخانه)
 *  ۵) چاپ/PDF فاکتور رسمی
 *  ۶) استعلام اصالت گارانتی با سریال (REST)
 *  ۷) جست‌وجوی هوشمند کدهای خطا در سایدبار (REST)
 *  ۸) استعلام سریع هزینه در سایدبار (نرخ‌نامه سمت سرور)
 *  ۹) کپی در کلیپ‌برد و اعلان‌های سبک
 *
 * همه درخواست‌ها با nonce و از مسیر admin-ajax یا REST انجام می‌شود؛
 * هیچ قیمتی در جاوااسکریپت ساخته نمی‌شود (فقط نمایش مقدار سرور).
 *
 * @package Pixva
 * @since 1.4.0
 */
(function () {
	'use strict';

	var cfg = window.pixvaCrm || {};
	var theme = window.pixvaTheme || { ajaxUrl: '', nonce: {}, i18n: {} };
	var i18n = cfg.i18n || {};
	var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function qs(sel, root) {
		return (root || document).querySelector(sel);
	}

	function qsa(sel, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(sel));
	}

	function fa(value) {
		return String(value == null ? '' : value).replace(/[0-9]/g, function (d) {
			return FA_DIGITS[Number(d)];
		});
	}

	function money(value) {
		var n = parseInt(value, 10);
		if (isNaN(n)) {
			n = 0;
		}
		return fa(n.toLocaleString('en-US')).replace(/,/g, '٬');
	}

	function toast(message, kind) {
		var host = qs('[data-crm-toast]');
		if (!host) {
			host = document.createElement('div');
			host.className = 'pixva-toast';
			host.setAttribute('data-crm-toast', '');
			host.setAttribute('role', 'status');
			host.setAttribute('aria-live', 'polite');
			document.body.appendChild(host);
		}
		host.textContent = message || '';
		host.className = 'pixva-toast is-visible' + (kind ? ' pixva-toast--' + kind : '');
		window.clearTimeout(host.dataset.timer);
		host.dataset.timer = window.setTimeout(function () {
			host.className = 'pixva-toast';
		}, 4200);
	}

	function notify(host, message, kind) {
		if (!host) {
			if (message) {
				toast(message, kind);
			}
			return;
		}
		host.hidden = !message;
		host.textContent = message || '';
		host.className = 'pixva-notice' + (kind ? ' pixva-notice--' + kind : '');
	}

	function busy(button, state) {
		if (!button) {
			return;
		}
		if (state) {
			button.dataset.label = button.textContent;
			button.disabled = true;
			button.classList.add('is-loading');
			button.textContent = i18n.sending || theme.i18n && theme.i18n.loading || '…';
		} else {
			button.disabled = false;
			button.classList.remove('is-loading');
			if (button.dataset.label) {
				button.textContent = button.dataset.label;
			}
		}
	}

	/**
	 * POST به admin-ajax با nonce موتور CRM.
	 */
	function post(action, fields, nonce) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', nonce || cfg.nonce || '');
		body.append('pixva_hp', '');
		Object.keys(fields || {}).forEach(function (key) {
			var value = fields[key];
			if (Array.isArray(value)) {
				value.forEach(function (item, index) {
					Object.keys(item).forEach(function (part) {
						body.append(key + '[' + index + '][' + part + ']', item[part] == null ? '' : item[part]);
					});
				});
				return;
			}
			body.append(key, value == null ? '' : value);
		});

		return fetch(cfg.ajaxUrl || theme.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (json) {
				if (!json || json.success !== true) {
					var message = json && json.data && json.data.message ? json.data.message : (i18n.error || 'خطا');
					throw new Error(message);
				}
				return json.data;
			});
	}

	/**
	 * GET از REST پیکسوا.
	 */
	function rest(path) {
		var base = cfg.restUrl || '';
		if (!base) {
			return Promise.reject(new Error('REST unavailable'));
		}
		return fetch(base + path, { credentials: 'same-origin' }).then(function (response) {
			return response.json().then(function (json) {
				if (!response.ok) {
					throw new Error(json && json.message ? json.message : 'error');
				}
				return json;
			});
		});
	}

	function debounce(fn, wait) {
		var timer = null;
		return function () {
			var args = arguments;
			var self = this;
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				fn.apply(self, args);
			}, wait);
		};
	}

	/* ==================================================================
	   ۱) جادوگر ثبت سفارش تعمیر
	   ================================================================== */
	function initWizard(root) {
		if (!root || root.dataset.wzBound === '1') {
			return;
		}
		root.dataset.wzBound = '1';

		var form = qs('[data-pixva-wizard]', root) || root;
		var steps = qsa('[data-wz-step]', root).filter(function (step) {
			return step.getAttribute('data-wz-step') !== 'done';
		});
		var done = qs('[data-wz-step="done"]', root);
		var rail = qs('[data-wz-rail]', root);
		var dots = qsa('[data-wz-dot]', root);
		var summary = qs('[data-wz-summary]', root);
		var priceBox = qs('[data-wz-price]', root);
		var daysBox = qs('[data-wz-days]', root);
		var warning = qs('[data-wz-warning]', root);
		var errorBox = qs('[data-wz-error]', root);
		var submit = qs('[data-wz-submit]', root);
		var current = 0;
		var quoted = false;

		function field(name) {
			// فیلدهای جادوگر شامل input (پنهان/متنی)، select و textarea هستند.
			return qs('input[name="' + name + '"], select[name="' + name + '"], textarea[name="' + name + '"]', form);
		}

		function showError(message) {
			notify(errorBox, message, message ? 'error' : '');
		}

		function chipLabel(group) {
			var chip = qs('[data-wz-group="' + group + '"].is-active', root);
			if (!chip) {
				return '';
			}
			var span = qs('span', chip);
			return span ? span.textContent.trim() : chip.textContent.trim();
		}

		function requiredOf(step) {
			var key = step.getAttribute('data-wz-step');
			if (key === 'device') {
				return ['brand', 'tech', 'size'];
			}
			if (key === 'fault') {
				return ['problem'];
			}
			return [];
		}

		function validate(step) {
			var ok = requiredOf(step).every(function (name) {
				var input = field(name);
				return input && input.value !== '';
			});

			if (step.getAttribute('data-wz-step') === 'contact') {
				var name = field('name');
				var phone = field('phone');
				var phoneValue = phone ? phone.value.replace(/[^\d]/g, '') : '';
				ok = Boolean(name && name.value.trim().length >= 2 && /^09\d{9}$/.test(phoneValue));
			}

			return ok;
		}

		function syncStep() {
			steps.forEach(function (step, index) {
				var active = index === current;
				step.hidden = !active;
				step.classList.toggle('is-active', active);
				step.setAttribute('aria-hidden', active ? 'false' : 'true');
			});

			dots.forEach(function (dot, index) {
				dot.classList.toggle('is-current', index === current);
				dot.classList.toggle('is-done', index < current);
			});

			var step = steps[current];
			qsa('[data-wz-next]', step).forEach(function (button) {
				button.disabled = !validate(step);
			});

			showError('');
		}

		function goTo(index) {
			current = Math.max(0, Math.min(steps.length - 1, index));
			syncStep();
		}

		// انتخاب چیپ‌ها → پرکردن ورودی پنهان
		qsa('[data-wz-choices]', root).forEach(function (group) {
			var name = group.getAttribute('data-wz-choices');
			qsa('[data-wz-group]', group).forEach(function (chip) {
				chip.addEventListener('click', function () {
					qsa('[data-wz-group="' + name + '"]', root).forEach(function (peer) {
						peer.classList.remove('is-active');
						peer.setAttribute('aria-checked', 'false');
					});
					chip.classList.add('is-active');
					chip.setAttribute('aria-checked', 'true');

					var input = field(name);
					if (input) {
						input.value = chip.getAttribute('data-wz-value') || '';
					}
					quoted = false;
					if (submit) {
						submit.disabled = true;
					}
					syncStep();
				});
			});
		});

		// اعتبارسنجی زنده فیلدهای متنی
		qsa('input, textarea, select', form).forEach(function (input) {
			input.addEventListener('input', function () {
				quoted = false;
				syncStep();
			});
			input.addEventListener('change', function () {
				syncStep();
			});
		});

		function fillSummary() {
			if (!summary) {
				return;
			}
			var device = [chipLabel('brand'), chipLabel('size'), chipLabel('tech')].filter(Boolean).join(' · ');
			var model = field('model');
			if (model && model.value.trim()) {
				device += ' — ' + model.value.trim();
			}
			var values = {
				device: device || '—',
				problem: chipLabel('problem') || '—',
				preferred: (function () {
					var select = field('preferred');
					return select && select.selectedIndex > -1 ? select.options[select.selectedIndex].text : '—';
				}()),
				zone: (function () {
					var select = field('zone');
					return select && select.selectedIndex > -1 ? select.options[select.selectedIndex].text : '—';
				}()),
				name: field('name') && field('name').value.trim() ? field('name').value.trim() : '—',
				phone: field('phone') && field('phone').value.trim() ? fa(field('phone').value.trim()) : '—'
			};

			Object.keys(values).forEach(function (key) {
				var cell = qs('[data-wz-sum="' + key + '"]', summary);
				if (cell) {
					cell.textContent = values[key];
				}
			});
		}

		/**
		 * برآورد هزینه فقط از سرور (موتور نرخ‌نامه).
		 */
		function requestQuote() {
			if (!window.pixvaPostAjax || !theme.nonce || !theme.nonce.calculator) {
				return Promise.resolve(null);
			}

			return window.pixvaPostAjax('pixva_get_estimate', theme.nonce.calculator, {
				brand: field('brand') ? field('brand').value : '',
				tech: field('tech') ? field('tech').value : '',
				size: field('size') ? field('size').value : '',
				problem: field('problem') ? field('problem').value : '',
				pixva_hp: ''
			});
		}

		qsa('[data-wz-next]', root).forEach(function (button) {
			button.addEventListener('click', function () {
				var step = steps[current];
				if (!validate(step)) {
					syncStep();
					return;
				}

				if (button.hasAttribute('data-wz-quote')) {
					busy(button, true);
					fillSummary();
					requestQuote()
						.then(function (data) {
							busy(button, false);
							if (data) {
								quoted = true;
								if (priceBox) {
									priceBox.textContent = data.panelReplacement ? '—' : data.minFormatted + ' تا ' + data.maxFormatted + ' ' + (theme.i18n && theme.i18n.toman ? theme.i18n.toman : 'تومان');
								}
								if (daysBox) {
									daysBox.textContent = data.days || '';
								}
								notify(warning, data.warning || '', data.warning ? 'warning' : '');
								if (submit) {
									submit.disabled = Boolean(data.panelReplacement);
								}
							} else if (submit) {
								quoted = true;
								submit.disabled = false;
							}
							goTo(current + 1);
						})
						.catch(function (error) {
							busy(button, false);
							showError(error.message);
						});
					return;
				}

				goTo(current + 1);
			});
		});

		qsa('[data-wz-prev]', root).forEach(function (button) {
			button.addEventListener('click', function () {
				goTo(current - 1);
			});
		});

		// کلیک روی نقطه‌های ریل (بازگشت به گام‌های طی‌شده)
		dots.forEach(function (dot, index) {
			dot.addEventListener('click', function () {
				if (index <= current) {
					goTo(index);
				}
			});
		});

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (!quoted) {
				showError('ابتدا برآورد هزینه را دریافت کنید.');
				return;
			}

			var fields = {
				name: field('name') ? field('name').value.trim() : '',
				phone: field('phone') ? field('phone').value.trim() : '',
				brand: field('brand') ? field('brand').value : '',
				tech: field('tech') ? field('tech').value : '',
				size: field('size') ? field('size').value : '',
				problem: field('problem') ? field('problem').value : '',
				model: field('model') ? field('model').value.trim() : '',
				address: field('address') ? field('address').value.trim() : '',
				zone: field('zone') ? field('zone').value : '',
				preferred: field('preferred') ? field('preferred').value : '',
				notes: field('notes') ? field('notes').value.trim() : '',
				source: form.getAttribute('data-wz-source') || 'wizard'
			};

			busy(submit, true);
			post('pixva_crm_create_order', fields)
				.then(function (data) {
					busy(submit, false);
					steps.forEach(function (step) {
						step.hidden = true;
						step.classList.remove('is-active');
					});
					if (rail) {
						rail.classList.add('is-complete');
					}
					dots.forEach(function (dot) {
						dot.classList.add('is-done');
						dot.classList.remove('is-current');
					});
					if (done) {
						done.hidden = false;
						done.classList.add('is-active');
					}
					var code = qs('[data-wz-code]', root);
					var message = qs('[data-wz-message]', root);
					var status = qs('[data-wz-status]', root);
					var track = qs('a[data-wz-track]', root);
					if (code) {
						code.textContent = data.code || '—';
					}
					if (message) {
						message.textContent = data.message || '';
					}
					if (status) {
						status.textContent = data.status || '';
					}
					if (track && data.trackUrl) {
						track.href = data.trackUrl + (data.trackUrl.indexOf('?') > -1 ? '&' : '?') + 'code=' + encodeURIComponent(data.code || '');
					}
					toast(data.message || i18n.saved || 'ثبت شد', 'success');
				})
				.catch(function (error) {
					busy(submit, false);
					showError(error.message);
				});
		});

		var restart = qs('[data-wz-restart]', root);
		if (restart) {
			restart.addEventListener('click', function () {
				form.reset();
				qsa('[data-wz-group].is-active', root).forEach(function (chip) {
					chip.classList.remove('is-active');
					chip.setAttribute('aria-checked', 'false');
				});
				qsa('input[type="hidden"]', form).forEach(function (input) {
					input.value = '';
				});
				quoted = false;
				// پاک‌سازی خروجی سفارش قبلی تا کد پیگیری کهنه نمایش داده نشود.
				['[data-wz-code]', '[data-wz-status]', '[data-wz-message]'].forEach(function (selector) {
					var cell = qs(selector, root);
					if (cell) {
						cell.textContent = selector === '[data-wz-code]' ? '—' : '';
					}
				});
				qsa('[data-wz-sum]', root).forEach(function (cell) {
					cell.textContent = '—';
				});
				if (done) {
					done.hidden = true;
					done.classList.remove('is-active');
				}
				if (rail) {
					rail.classList.remove('is-complete');
				}
				if (priceBox) {
					priceBox.textContent = '—';
				}
				if (daysBox) {
					daysBox.textContent = '';
				}
				if (submit) {
					submit.disabled = true;
				}
				goTo(0);
			});
		}

		syncStep();
	}

	/* ==================================================================
	   ۲) ایستگاه کاری تعمیرکار
	   ================================================================== */
	function initTechnicianPanel(panel) {
		if (!panel || panel.dataset.crmBound === '1') {
			return;
		}
		panel.dataset.crmBound = '1';

		var message = qs('[data-crm-message]', panel);
		var cards = qsa('[data-crm-order]', panel);

		// فیلتر وضعیت (کلاینت‌ساید؛ پیوند سروری هم بدون JS کار می‌کند)
		qsa('[data-crm-filter]', panel).forEach(function (chip) {
			chip.addEventListener('click', function (event) {
				var filter = chip.getAttribute('data-crm-filter');
				if (filter === 'all') {
					filter = '';
				}
				qsa('[data-crm-filter]', panel).forEach(function (peer) {
					peer.classList.toggle('is-active', peer === chip);
				});
				cards.forEach(function (card) {
					card.hidden = Boolean(filter) && card.getAttribute('data-crm-status') !== filter;
				});
				event.preventDefault();
			});
		});

		// باز/بسته کردن فرم گزارش و جزئیات
		qsa('[data-crm-toggle]', panel).forEach(function (button) {
			button.addEventListener('click', function () {
				var target = document.getElementById(button.getAttribute('data-crm-toggle'));
				if (!target) {
					return;
				}
				var open = target.hidden;
				target.hidden = !open;
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
				target.classList.toggle('is-open', open);
			});
		});

		function statusChip(card, status) {
			var chip = qs('.pixva-crm-order__status', card);
			if (!chip) {
				return;
			}
			chip.className = 'pixva-crm-order__status pixva-crm-order__status--' + status;
			chip.textContent = (cfg.statuses && cfg.statuses[status]) || status;
			card.setAttribute('data-crm-status', status);
		}

		// تغییر وضعیت پرونده
		qsa('[data-crm-action="status"]', panel).forEach(function (button) {
			button.addEventListener('click', function () {
				var card = button.closest('[data-crm-order]');
				if (!card) {
					return;
				}
				var status = button.getAttribute('data-status');
				busy(button, true);
				post('pixva_crm_status', {
					order_id: card.getAttribute('data-crm-order'),
					status: status
				})
					.then(function (data) {
						busy(button, false);
						statusChip(card, data.order.status);
						notify(message, data.message, 'success');
						toast(data.message, 'success');
						qsa('[data-crm-action="status"]', card).forEach(function (peer) {
							peer.disabled = peer.getAttribute('data-status') === status;
						});
					})
					.catch(function (error) {
						busy(button, false);
						notify(message, error.message, 'error');
					});
			});
		});

		// گزارش فنی: ردیف قطعات + جمع زنده
		qsa('[data-crm-report]', panel).forEach(function (form) {
			var partsHost = qs('[data-crm-parts]', form);
			var totalBox = qs('[data-crm-total]', form);
			var labor = qs('[data-crm-labor]', form);

			function reindex() {
				qsa('[data-crm-part-row]', partsHost).forEach(function (row, index) {
					qsa('[data-crm-part]', row).forEach(function (input) {
						var part = input.getAttribute('data-crm-part');
						input.name = 'parts[' + index + '][' + part + ']';
					});
				});
			}

			function sum() {
				var parts = 0;
				qsa('[data-crm-part-row]', partsHost).forEach(function (row) {
					var qty = parseInt(qs('[data-crm-part="qty"]', row).value, 10) || 0;
					var price = parseInt(qs('[data-crm-part="price"]', row).value, 10) || 0;
					parts += qty * price;
				});
				var laborValue = labor ? parseInt(labor.value, 10) || 0 : 0;
				if (totalBox) {
					totalBox.textContent = money(parts + laborValue) + ' تومان';
				}
			}

			var add = qs('[data-crm-add-part]', form);
			if (add && partsHost) {
				add.addEventListener('click', function () {
					var first = qs('[data-crm-part-row]', partsHost);
					if (!first) {
						return;
					}
					var clone = first.cloneNode(true);
					qsa('input', clone).forEach(function (input) {
						if (input.getAttribute('data-crm-part') === 'qty') {
							input.value = '1';
						} else if (input.getAttribute('data-crm-part') === 'price') {
							input.value = '0';
						} else {
							input.value = '';
						}
					});
					partsHost.appendChild(clone);
					reindex();
					sum();
					var nameInput = qs('[data-crm-part="name"]', clone);
					if (nameInput) {
						nameInput.focus();
					}
				});
			}

			if (partsHost) {
				partsHost.addEventListener('click', function (event) {
					var remove = event.target.closest('[data-crm-remove-part]');
					if (!remove) {
						return;
					}
					var rows = qsa('[data-crm-part-row]', partsHost);
					if (rows.length <= 1) {
						qsa('input', rows[0]).forEach(function (input) {
							input.value = input.getAttribute('data-crm-part') === 'qty' ? '1' : '0';
						});
						sum();
						return;
					}
					remove.closest('[data-crm-part-row]').remove();
					reindex();
					sum();
				});

				partsHost.addEventListener('input', sum);
			}
			if (labor) {
				labor.addEventListener('input', sum);
			}

			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var card = form.closest('[data-crm-order]');
				var button = qs('[data-crm-action="report"]', form);
				var payload = new FormData(form);
				var fields = { order_id: card ? card.getAttribute('data-crm-order') : '' };

				var parts = [];
				qsa('[data-crm-part-row]', form).forEach(function (row) {
					var name = qs('[data-crm-part="name"]', row).value.trim();
					if (!name) {
						return;
					}
					parts.push({
						name: name,
						spec: qs('[data-crm-part="spec"]', row).value.trim(),
						serial: qs('[data-crm-part="serial"]', row).value.trim(),
						qty: qs('[data-crm-part="qty"]', row).value || '1',
						price: qs('[data-crm-part="price"]', row).value || '0'
					});
				});

				fields.parts = parts;
				fields.diagnosis = payload.get('diagnosis') || '';
				fields.actions = payload.get('actions') || '';
				fields.minutes = payload.get('minutes') || '0';
				fields.labor = payload.get('labor') || '0';
				fields.qc_passed = payload.get('qc_passed') ? 1 : 0;

				busy(button, true);
				post('pixva_crm_report', fields)
					.then(function (data) {
						busy(button, false);
						notify(message, data.message, 'success');
						toast(data.message, 'success');
						if (card && data.order) {
							statusChip(card, data.order.status);
						}
					})
					.catch(function (error) {
						busy(button, false);
						notify(message, error.message, 'error');
					});
			});

			reindex();
			sum();
		});

		// تأیید نهایی و صدور گارانتی دیجیتال
		qsa('[data-crm-action="warranty"]', panel).forEach(function (button) {
			button.addEventListener('click', function () {
				var card = button.closest('[data-crm-order]');
				if (!card) {
					return;
				}
				if (!window.confirm(i18n.confirmWarranty || 'گارانتی دیجیتال صادر شود؟')) {
					return;
				}

				busy(button, true);
				post('pixva_crm_warranty', { order_id: card.getAttribute('data-crm-order') })
					.then(function (data) {
						busy(button, false);
						card.classList.add('is-warrantied');
						button.remove();
						if (data.order) {
							statusChip(card, data.order.status);
						}
						var host = qs('.pixva-crm-order__head', card);
						if (host && data.warranty) {
							var badge = document.createElement('span');
							badge.className = 'pixva-crm-order__serial';
							badge.textContent = 'گارانتی ' + data.warranty.serial;
							badge.setAttribute('dir', 'ltr');
							host.appendChild(badge);
						}
						notify(message, data.message + ' — برای دیدن کارت هولوگرافیک، پنل را تازه کنید.', 'success');
						toast(data.message, 'success');
					})
					.catch(function (error) {
						busy(button, false);
						notify(message, error.message, 'error');
					});
			});
		});
	}

	/* ==================================================================
	   ۳) پنل دیسپچ (پیشخوان وردپرس)
	   ================================================================== */
	function initDispatcher(panel) {
		if (!panel || panel.dataset.crmBound === '1') {
			return;
		}
		panel.dataset.crmBound = '1';

		var message = qs('[data-crm-message]', panel);

		qsa('[data-crm-toggle]', panel).forEach(function (button) {
			button.addEventListener('click', function () {
				var target = document.getElementById(button.getAttribute('data-crm-toggle'));
				if (!target) {
					return;
				}
				var open = target.hidden;
				target.hidden = !open;
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		});

		qsa('[data-crm-action="save"]', panel).forEach(function (button) {
			button.addEventListener('click', function () {
				var row = button.closest('[data-crm-order]');
				if (!row) {
					return;
				}
				var orderId = row.getAttribute('data-crm-order');
				var techSelect = qs('[data-crm-field="technician"]', row);
				var statusSelect = qs('[data-crm-field="status"]', row);

				button.disabled = true;
				button.classList.add('is-loading');

				post('pixva_crm_assign', { order_id: orderId, technician: techSelect ? techSelect.value : '0' })
					.then(function (data) {
						return post('pixva_crm_status', { order_id: orderId, status: statusSelect ? statusSelect.value : '' }).then(function (statusData) {
							return statusData || data;
						});
					})
					.then(function (data) {
						button.disabled = false;
						button.classList.remove('is-loading');
						if (data && data.order) {
							row.setAttribute('data-crm-status', data.order.status);
							if (statusSelect) {
								statusSelect.value = data.order.status;
							}
						}
						notify(message, (data && data.message) || i18n.saved || 'ذخیره شد', 'success');
					})
					.catch(function (error) {
						button.disabled = false;
						button.classList.remove('is-loading');
						notify(message, error.message, 'error');
					});
			});
		});
	}

	/* ==================================================================
	   ۴) هولوگرام زنده کارت گارانتی
	   ================================================================== */
	function initHologram(card) {
		if (!card || card.dataset.holoBound === '1') {
			return;
		}
		card.dataset.holoBound = '1';

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduce) {
			card.classList.add('pixva-warranty--static');
			return;
		}

		var frame = null;
		var pending = null;

		function apply(x, y) {
			if (frame) {
				return;
			}
			frame = window.requestAnimationFrame(function () {
				frame = null;
				if (!pending) {
					return;
				}
				var point = pending;
				pending = null;
				card.style.setProperty('--holo-x', point.x.toFixed(2) + '%');
				card.style.setProperty('--holo-y', point.y.toFixed(2) + '%');
				card.style.setProperty('--holo-tilt-x', ((point.y - 50) / -8).toFixed(2) + 'deg');
				card.style.setProperty('--holo-tilt-y', ((point.x - 50) / 8).toFixed(2) + 'deg');
			});
		}

		function reset() {
			card.style.setProperty('--holo-x', '50%');
			card.style.setProperty('--holo-y', '50%');
			card.style.setProperty('--holo-tilt-x', '0deg');
			card.style.setProperty('--holo-tilt-y', '0deg');
		}

		card.addEventListener('pointermove', function (event) {
			var box = card.getBoundingClientRect();
			if (!box.width || !box.height) {
				return;
			}
			pending = {
				x: ((event.clientX - box.left) / box.width) * 100,
				y: ((event.clientY - box.top) / box.height) * 100
			};
			apply();
			card.classList.add('is-live');
		});

		card.addEventListener('pointerleave', function () {
			card.classList.remove('is-live');
			reset();
		});

		// ژیروسکوپ موبایل (بدون درخواست مجوز؛ در صورت عدم پشتیبانی، حلقه نورانی CSS فعال می‌ماند)
		if (typeof window.DeviceOrientationEvent === 'function' && typeof window.DeviceOrientationEvent.requestPermission !== 'function') {
			var gyro = debounce(function (event) {
				if (event.gamma === null || event.beta === null) {
					return;
				}
				pending = {
					x: Math.max(0, Math.min(100, 50 + event.gamma)),
					y: Math.max(0, Math.min(100, 50 + (event.beta - 45)))
				};
				apply();
				card.classList.add('is-live');
			}, 60);
			window.addEventListener('deviceorientation', gyro, true);
		}
	}

	/* ==================================================================
	   ۵) چاپ / ذخیره PDF فاکتور
	   ================================================================== */
	function initPrint() {
		qsa('[data-pixva-print]').forEach(function (button) {
			if (button.dataset.printBound === '1') {
				return;
			}
			button.dataset.printBound = '1';

			button.addEventListener('click', function () {
				var areaId = button.getAttribute('data-print-area');
				var area = areaId ? document.getElementById(areaId) : null;
				if (!area) {
					area = button.closest('[data-print-area]');
				}
				if (!area) {
					return;
				}

				document.body.classList.add('pixva-printing');
				area.classList.add('is-print-source');

				var cleanup = function () {
					document.body.classList.remove('pixva-printing');
					area.classList.remove('is-print-source');
					window.removeEventListener('afterprint', cleanup);
				};

				window.addEventListener('afterprint', cleanup);
				window.setTimeout(function () {
					window.print();
					// در مرورگرهایی که afterprint نمی‌دهند.
					window.setTimeout(cleanup, 1200);
				}, 60);
			});
		});
	}

	/* ==================================================================
	   ۶) استعلام اصالت گارانتی با سریال
	   ================================================================== */
	function initWarrantyCheck(root) {
		if (!root || root.dataset.checkBound === '1') {
			return;
		}
		root.dataset.checkBound = '1';

		var form = qs('[data-warranty-form]', root);
		var input = qs('[data-warranty-serial]', root);
		var result = qs('[data-warranty-result]', root);
		var state = qs('[data-warranty-state]', root);
		var errorBox = qs('[data-warranty-error]', root);
		var button = qs('[data-warranty-submit]', root);

		if (!form || !input) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var serial = input.value.trim().toUpperCase();
			if (!serial) {
				notify(errorBox, 'سریال گارانتی را وارد کنید.', 'error');
				return;
			}

			busy(button, true);
			notify(errorBox, '');
			rest('crm/warranty/' + encodeURIComponent(serial))
				.then(function (data) {
					busy(button, false);
					if (result) {
						result.hidden = false;
						result.classList.toggle('is-invalid', !data.valid);
					}
					if (state) {
						state.textContent = data.state || '';
					}
					var fill = function (sel, value) {
						var cell = qs(sel, root);
						if (cell) {
							cell.textContent = value || '—';
						}
					};
					fill('[data-warranty-tech]', data.technician);
					fill('[data-warranty-issued]', data.issuedAt);
					fill('[data-warranty-expires]', data.expiresAt);
					fill('[data-warranty-covers]', (data.covers || []).join('، '));
					toast(data.state || '', data.valid ? 'success' : 'error');
				})
				.catch(function (error) {
					busy(button, false);
					if (result) {
						result.hidden = true;
					}
					notify(errorBox, error.message || 'استعلام ناموفق بود.', 'error');
				});
		});
	}

	/* ==================================================================
	   ۷) جست‌وجوی هوشمند کدهای خطا (سایدبار)
	   ================================================================== */
	function initErrorSearch(root) {
		if (!root || root.dataset.errBound === '1') {
			return;
		}
		root.dataset.errBound = '1';

		var input = qs('[data-error-input]', root);
		var list = qs('[data-error-results]', root);
		var empty = qs('[data-error-empty]', root);
		if (!input || !list) {
			return;
		}

		function render(rows) {
			list.textContent = '';
			if (!rows.length) {
				if (empty) {
					empty.hidden = false;
				}
				return;
			}
			if (empty) {
				empty.hidden = true;
			}

			rows.slice(0, 6).forEach(function (row) {
				var item = document.createElement('li');
				item.className = 'pixva-widget__result';

				var head = document.createElement('div');
				var code = document.createElement('strong');
				code.textContent = row.code || row.title || '';
				code.setAttribute('dir', 'ltr');
				head.appendChild(code);

				if (row.brand) {
					var brand = document.createElement('span');
					brand.className = 'pixva-widget__result-brand';
					brand.textContent = row.brand;
					head.appendChild(brand);
				}
				item.appendChild(head);

				if (row.cause) {
					var cause = document.createElement('p');
					cause.textContent = row.cause;
					item.appendChild(cause);
				}
				if (row.fix) {
					var fix = document.createElement('small');
					fix.textContent = row.fix;
					item.appendChild(fix);
				}
				list.appendChild(item);
			});
		}

		var search = debounce(function () {
			var query = input.value.trim();
			if (query.length < 2) {
				render([]);
				if (empty) {
					empty.hidden = true;
				}
				return;
			}
			rest('errors?q=' + encodeURIComponent(query))
				.then(function (data) {
					render((data && data.errors) || []);
				})
				.catch(function () {
					render([]);
				});
		}, 320);

		input.addEventListener('input', search);

		var form = qs('[data-error-form]', root);
		if (form) {
			form.addEventListener('submit', function (event) {
				if (input.value.trim().length >= 2) {
					event.preventDefault();
					search();
				}
			});
		}
	}

	/* ==================================================================
	   ۸) استعلام سریع هزینه (سایدبار)
	   ================================================================== */
	function initQuickQuote(root) {
		if (!root || root.dataset.quoteBound === '1') {
			return;
		}
		root.dataset.quoteBound = '1';

		var form = qs('[data-quote-form]', root);
		var result = qs('[data-quote-result]', root);
		var price = qs('[data-quote-price]', root);
		var days = qs('[data-quote-days]', root);
		var errorBox = qs('[data-quote-error]', root);
		var button = qs('[data-quote-submit]', root);

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (!window.pixvaPostAjax || !theme.nonce || !theme.nonce.calculator) {
				notify(errorBox, 'برآورد هزینه در این صفحه در دسترس نیست.', 'error');
				return;
			}

			busy(button, true);
			window.pixvaPostAjax('pixva_get_estimate', theme.nonce.calculator, {
				brand: form.brand ? form.brand.value : '',
				tech: 'led',
				size: form.size ? form.size.value : '',
				problem: form.problem ? form.problem.value : '',
				pixva_hp: ''
			})
				.then(function (data) {
					busy(button, false);
					notify(errorBox, '');
					if (result) {
						result.hidden = false;
					}
					if (price) {
						price.textContent = data.panelReplacement ? (data.warning || '—') : data.minFormatted + ' تا ' + data.maxFormatted + ' تومان';
					}
					if (days) {
						days.textContent = data.days || '';
					}
				})
				.catch(function (error) {
					busy(button, false);
					notify(errorBox, error.message, 'error');
				});
		});
	}

	/* ==================================================================
	   ۹) کپی در کلیپ‌برد
	   ================================================================== */
	function initCopy() {
		qsa('[data-copy]').forEach(function (button) {
			if (button.dataset.copyBound === '1') {
				return;
			}
			button.dataset.copyBound = '1';

			button.addEventListener('click', function () {
				var value = button.getAttribute('data-copy') || '';
				if (!value) {
					return;
				}
				var done = function () {
					toast(i18n.copied || 'کپی شد', 'success');
					button.classList.add('is-copied');
					window.setTimeout(function () {
						button.classList.remove('is-copied');
					}, 1600);
				};

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(value).then(done).catch(function () {
						legacyCopy(value);
						done();
					});
					return;
				}
				legacyCopy(value);
				done();
			});
		});
	}

	function legacyCopy(value) {
		var area = document.createElement('textarea');
		area.value = value;
		area.setAttribute('readonly', '');
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild(area);
		area.select();
		try {
			document.execCommand('copy');
		} catch (error) {
			// مرورگر اجازه کپی نداد؛ کاربر می‌تواند دستی کپی کند.
		}
		area.remove();
	}

	/* ==================================================================
	   راه‌اندازی
	   ================================================================== */
	function boot() {
		qsa('[data-pixva-wizard]').forEach(initWizard);
		qsa('[data-crm-panel]').forEach(function (panel) {
			// نمای دیسپچ (پیشخوان) و نمای تعمیرکار (فرانت‌اند) رفتار متفاوتی دارند.
			if (panel.getAttribute('data-crm-view') === 'dispatch') {
				initDispatcher(panel);
			} else {
				initTechnicianPanel(panel);
			}
		});
		qsa('[data-pixva-warranty]').forEach(initHologram);
		qsa('[data-pixva-warranty-check]').forEach(initWarrantyCheck);
		qsa('[data-pixva-error-search]').forEach(initErrorSearch);
		qsa('[data-pixva-quick-quote]').forEach(initQuickQuote);
		initPrint();
		initCopy();
		document.documentElement.classList.add('pixva-crm-ready');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaCrmBoot = boot;
}());
