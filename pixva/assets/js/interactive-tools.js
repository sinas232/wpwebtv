/**
 * جاوااسکریپت ۶۰ ابزار تعاملی و هوشمند پیکسوا (assets/js/interactive-tools.js)
 *
 * شامل منطق کلاینت برای:
 * - دستیار شناور AI (اتصال امن AJAX با Fallback محلی)
 * - شبیه‌ساز لمسی خرابی روی تلویزیون مجازی (Canvas Simulator)
 * - تستر زنده RGB و احیای OLED
 * - کنترل‌ها و میکروانیمیشن‌های لمسی
 */

(function () {
	'use strict';

	// ۱) چت‌بات هوش مصنوعی شناور پیکسوا
	const initAIBot = () => {
		const widgets = document.querySelectorAll('[data-pixva-ai-bot]');
		if (!widgets.length) return;

		const setOpen = (widget, open, shouldFocus) => {
			const panel = widget.querySelector('[data-ai-panel]');
			const toggleBtn = widget.querySelector('[data-ai-toggle]');
			const input = widget.querySelector('[data-ai-input]');
			if (!panel) return;
			panel.hidden = !open;
			panel.setAttribute('aria-hidden', open ? 'false' : 'true');
			widget.classList.toggle('is-open', open);
			if (toggleBtn) {
				toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
			}
			if (shouldFocus && !open && toggleBtn && toggleBtn.offsetParent !== null) {
				toggleBtn.focus();
			} else if (open && input) {
				input.focus();
			}
		};

		widgets.forEach((widget) => setOpen(widget, false, false));

		document.addEventListener('click', (event) => {
			const closer = event.target.closest('[data-ai-close]');
			if (closer) {
				event.preventDefault();
				event.stopPropagation();
				setOpen(closer.closest('[data-pixva-ai-bot]') || widgets[0], false, true);
				return;
			}
			const opener = event.target.closest('[data-ai-toggle]');
			if (!opener) {
				return;
			}
			event.preventDefault();
			const host = opener.closest('[data-pixva-ai-bot]') || widgets[0];
			const panel = host.querySelector('[data-ai-panel]');
			const next = panel ? panel.hidden : true;
			widgets.forEach((other) => {
				if (other !== host) setOpen(other, false, false);
			});
			setOpen(host, next, false);
		});

		document.addEventListener('keydown', (event) => {
			if (event.key !== 'Escape') return;
			widgets.forEach((widget) => {
				const panel = widget.querySelector('[data-ai-panel]');
				if (panel && !panel.hidden) setOpen(widget, false, true);
			});
		});

		widgets.forEach((widget) => {
			const form = widget.querySelector('[data-ai-form]');
			const input = widget.querySelector('[data-ai-input]');
			const brandSelect = widget.querySelector('[data-ai-brand]');
			const messages = widget.querySelector('[data-ai-messages]');
			if (!form || !messages) return;

			const appendMsg = (text, sender = 'bot') => {
				const div = document.createElement('div');
				div.className = `pixva-ai-msg pixva-ai-msg--${sender}`;
				const p = document.createElement('p');
				p.innerText = text;
				div.appendChild(p);
				messages.appendChild(div);
				messages.scrollTop = messages.scrollHeight;
			};

			form.addEventListener('submit', (e) => {
				e.preventDefault();
				const msg = (input ? input.value : '').trim();
				const brand = brandSelect ? brandSelect.value : '';
				if (!msg) return;

				appendMsg(msg, 'user');
				input.value = '';

				appendMsg('در حال تطبیق علامت با پرونده‌های کارگاه...', 'bot');
				const loadingMsg = messages.lastElementChild;

				const data = new FormData();
				data.append('action', 'pixva_ai_diagnose');
				data.append('nonce', window.pixvaVars ? window.pixvaVars.nonce : '');
				data.append('message', msg);
				data.append('brand', brand);

				const ajaxUrl = window.pixvaVars ? window.pixvaVars.ajaxUrl : '/wp-admin/admin-ajax.php';

				fetch(ajaxUrl, {
					method: 'POST',
					body: data,
				})
					.then((res) => res.json())
					.then((res) => {
						if (loadingMsg && loadingMsg.parentNode) {
							loadingMsg.remove();
						}
						if (res.success && res.data && res.data.reply) {
							appendMsg(res.data.reply, 'bot');
						} else {
							appendMsg(
								'علامت ثبت شد. برای نوبت کارگاه با شماره ۰۲۱۹۱۰۰۹۹۹۰ تماس بگیرید.',
								'bot'
							);
						}
					})
					.catch(() => {
						if (loadingMsg && loadingMsg.parentNode) {
							loadingMsg.remove();
						}
						appendMsg(
							'اتصال برقرار نشد. علامت را برای کارگاه علاءالدین بفرستید یا تلفنی بگویید.',
							'bot'
						);
					});
			});
		});
	};

	// ۲) شبیه‌ساز لمسی تلویزیون مجازی
	const initTVSimulator = () => {
		const root = document.querySelector('[data-tv-simulator]');
		if (!root) return;

		const hotspots = root.querySelectorAll('[data-part]');
		const tag = root.querySelector('[data-sim-tag]');
		const title = root.querySelector('[data-sim-title]');
		const desc = root.querySelector('[data-sim-desc]');
		const priceBox = root.querySelector('[data-sim-price-box]');
		const price = root.querySelector('[data-sim-price]');
		const screenMsg = root.querySelector('[data-sim-screen-msg]');

		const partData = {
			backlight: {
				tag: 'شایع‌ترین خرابی',
				title: 'خرابی سوختگی بک‌لایت (صدا هست، تصویر تاریک است)',
				desc: 'لامپ‌های ال‌ای‌دی شاخه‌ای فرسوده شده‌اند. دست کامل با نمونه هیت‌سینک‌دار فابریک تعویض شده و اصلاحیه ولتاژ برد تغذیه برای جلوگیری از تکرار انجام می‌شود.',
				price: 'از ۸٬۰۰۰٬۰۰۰ تومان (۳۲ اینچ) تا ۲۶٬۰۰۰٬۰۰۰ تومان (۶۵ اینچ)',
				screen: '⬛ صفحه کاملاً تاریک ولی صدای برنامه‌ها پخش می‌شود',
			},
			water: {
				tag: 'آسیب مایعات و بندینگ',
				title: 'آب‌خوردگی فلت پنل COF و خرابی تیکان',
				desc: 'نفوذ مایعات شیشه‌شور یا رطوبت باعث ایجاد خطوط رنگی عمودی روی تصویر شده است. با دستگاه بندینگ صنعتی و لیزر میکرومتری ترمیم می‌شود.',
				price: 'از ۱۶٬۰۰۰٬۰۰۰ تومان (۳۲ اینچ) تا ۵۸٬۰۰۰٬۰۰۰ تومان (۶۵ اینچ)',
				screen: '📶 ایجاد خطوط عمودی رنگی روی کل صفحه',
			},
			mainboard: {
				tag: 'پردازنده و سیستم‌عامل',
				title: 'خرابی مین‌برد / ماندن روی لوگو / خاموشی پورت‌های HDMI',
				desc: 'چیپ پردازنده، حافظه eMMC یا بایوس دچار لحیم‌پریدگی یا سوختگی شده است. با دستگاه BGA تخصصی ریبال یا پروگرام می‌شود.',
				price: 'از ۱۲٬۵۰۰٬۰۰۰ تومان (۳۲ اینچ) تا ۴۵٬۰۰۰٬۰۰۰ تومان (۶۵ اینچ)',
				screen: '🔄 تلویزیون روی لوگوی سازنده گیر کرده و ریستارت می‌شود',
			},
			powerboard: {
				tag: 'مدار تغذیه و خازن‌ها',
				title: 'سوختگی برد پاور (خاموشی کامل / استندبای خاموش)',
				desc: 'نوسان برق شهری به طبقه ورودی آسیب زده است. خازن‌ها، ترانزیستورهای سوئیچینگ و فیوز با قطعات اورجینال جایگزین می‌شوند.',
				price: 'از ۹٬۵۰۰٬۰۰۰ تومان (۳۲ اینچ) تا ۳۲٬۰۰۰٬۰۰۰ تومان (۶۵ اینچ)',
				screen: '🔌 چراغ قرمز استندبای کاملاً خاموش است و روشن نمی‌شود',
			},
		};

		hotspots.forEach((spot) => {
			spot.addEventListener('click', () => {
				const part = spot.dataset.part;
				const info = partData[part];
				if (!info) return;

				hotspots.forEach((s) => s.classList.remove('is-active'));
				spot.classList.add('is-active');

				if (tag) tag.textContent = info.tag;
				if (title) title.textContent = info.title;
				if (desc) desc.textContent = info.desc;
				if (price) price.textContent = info.price;
				if (priceBox) priceBox.hidden = false;
				if (screenMsg) {
					screenMsg.innerHTML = `<p style="color:#06B6D4;font-weight:800;">${info.screen}</p>`;
				}
			});
		});
	};

	// ۳) تستر زنده RGB و احیاکننده OLED
	const initRGBTester = () => {
		const tester = document.querySelector('[data-rgb-tester]');
		if (!tester) return;

		const canvas = tester.querySelector('[data-rgb-canvas]');
		const colorBtns = tester.querySelectorAll('[data-color]');
		const oledBtn = tester.querySelector('[data-oled-cleaner]');
		let cleanInterval = null;

		colorBtns.forEach((btn) => {
			btn.addEventListener('click', () => {
				if (cleanInterval) {
					clearInterval(cleanInterval);
					cleanInterval = null;
				}
				const color = btn.dataset.color;
				if (canvas) {
					canvas.style.background = color;
					canvas.innerHTML = `<span style="padding:10px 18px;background:rgba(0,0,0,0.6);border-radius:999px;color:#fff;font-weight:700;">رنگ فعال: ${color} — با دقت به دنبال نقاط کدر یا روشن غیرعادی بگردید</span>`;
				}
			});
		});

		if (oledBtn) {
			oledBtn.addEventListener('click', () => {
				if (cleanInterval) {
					clearInterval(cleanInterval);
					cleanInterval = null;
					canvas.innerHTML = '<span style="color:#94a3b8;">چرخه احیا متوقف شد.</span>';
					return;
				}
				const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffffff', '#000000'];
				let idx = 0;
				cleanInterval = setInterval(() => {
					if (canvas) {
						canvas.style.background = colors[idx % colors.length];
						canvas.innerHTML = '<span style="padding:10px 18px;background:rgba(0,0,0,0.7);border-radius:999px;color:#10B981;font-weight:800;">⚡ در حال اجرای پترن‌های فرکانس متغیر ضد سوختگی OLED...</span>';
						idx += 1;
					}
				}, 400);
			});
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		initAIBot();
		initTVSimulator();
		initRGBTester();
	});
})();
