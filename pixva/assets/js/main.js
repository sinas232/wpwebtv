/**
 * اسکریپت اصلی قالب پیکسوا
 * منوی موبایل، آشکارسازی، آکاردئون، فرم تماس و فیلتر کد خطا.
 */
(function () {
	'use strict';

	const cfg = window.pixvaTheme || { ajaxUrl: '', nonce: {}, i18n: {} };

	const qs = (sel, root = document) => root.querySelector(sel);
	const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

	function setBusy(button, busy) {
		if (!button) {
			return;
		}
		button.disabled = busy;
		button.classList.toggle('is-loading', busy);
		if (busy) {
			button.dataset.prevLabel = button.textContent;
			button.textContent = cfg.i18n.loading || '…';
		} else if (button.dataset.prevLabel) {
			button.textContent = button.dataset.prevLabel;
		}
	}

	async function postAjax(action, nonce, fields) {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', nonce);
		Object.keys(fields).forEach((key) => {
			body.append(key, fields[key] == null ? '' : fields[key]);
		});
		const response = await fetch(cfg.ajaxUrl, {
			method: 'POST',
			body,
			credentials: 'same-origin',
		});
		const json = await response.json();
		if (!json || json.success !== true) {
			const message = json && json.data && json.data.message ? json.data.message : (cfg.i18n.error || 'Error');
			throw new Error(message);
		}
		return json.data;
	}

	function initNav() {
		const burger = qs('[data-pixva-burger]');
		const drawer = qs('[data-pixva-drawer]');
		const overlay = qs('[data-pixva-overlay]');
		if (!burger || !drawer || !overlay) {
			return;
		}
		const closeBtn = qs('[data-pixva-drawer-close]', drawer);

		const setOpen = (open) => {
			drawer.classList.toggle('is-open', open);
			overlay.classList.toggle('is-open', open);
			drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
			document.body.classList.toggle('pixva-nav-open', open);
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			burger.setAttribute('aria-label', open ? (cfg.i18n.menuClose || '') : (cfg.i18n.menuOpen || ''));
			if (open) {
				const focusable = qs('a, button', drawer);
				if (focusable) {
					focusable.focus();
				}
			}
		};

		burger.addEventListener('click', () => setOpen(!drawer.classList.contains('is-open')));
		overlay.addEventListener('click', () => setOpen(false));
		if (closeBtn) {
			closeBtn.addEventListener('click', () => setOpen(false));
		}
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				setOpen(false);
			}
		});
	}

	function initHeader() {
		const header = qs('.pixva-header');
		if (!header) {
			return;
		}
		const onScroll = () => {
			header.classList.toggle('is-scrolled', window.scrollY > 8);
		};
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	function initReveal() {
		qsa('.pixva-section, .pixva-card, .pixva-hero__visual, .pixva-page-hero').forEach((node) => {
			if (!node.classList.contains('pixva-reveal')) {
				node.classList.add('pixva-reveal');
			}
		});
		const nodes = qsa('.pixva-reveal');
		if (!nodes.length || !('IntersectionObserver' in window)) {
			nodes.forEach((node) => node.classList.add('is-visible'));
			return;
		}
		const observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.14 });
		nodes.forEach((node) => observer.observe(node));
	}

	function initFaq(root) {
		qsa('[data-pixva-faq]', root).forEach((wrap) => {
			qsa('.pixva-faq__q', wrap).forEach((button) => {
				button.addEventListener('click', () => {
					const panel = document.getElementById(button.getAttribute('aria-controls'));
					const open = button.getAttribute('aria-expanded') === 'true';
					qsa('.pixva-faq__q', wrap).forEach((other) => {
						if (other !== button) {
							other.setAttribute('aria-expanded', 'false');
							const otherPanel = document.getElementById(other.getAttribute('aria-controls'));
							if (otherPanel) {
								otherPanel.hidden = true;
							}
						}
					});
					button.setAttribute('aria-expanded', open ? 'false' : 'true');
					if (panel) {
						panel.hidden = open;
					}
				});
			});
		});
	}

	function initContact() {
		const form = qs('[data-pixva-contact]');
		if (!form) {
			return;
		}
		const msg = qs('[data-contact-msg]', form);
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = qs('[type="submit"]', form);
			if (msg) {
				msg.hidden = true;
			}
			setBusy(button, true);
			try {
				const data = await postAjax('pixva_contact_form', cfg.nonce.contact, {
					customer_name: qs('[name="customer_name"]', form).value.trim(),
					phone: qs('[name="phone"]', form).value.trim(),
					email: qs('[name="email"]', form).value.trim(),
					message: qs('[name="message"]', form).value.trim(),
					pixva_hp: qs('[name="pixva_hp"]', form) ? qs('[name="pixva_hp"]', form).value : '',
				});
				form.reset();
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--success';
					msg.textContent = data.message;
				}
			} catch (error) {
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--error';
					msg.textContent = error.message;
				}
			} finally {
				setBusy(button, false);
			}
		});
	}

	function initErrorFilter() {
		const root = qs('[data-pixva-errors]');
		if (!root) {
			return;
		}
		const cards = qsa('[data-brand]', root);
		const brand = qs('[data-filter-brand]', root);
		const blink = qs('[data-filter-blink]', root);
		const query = qs('[data-filter-q]', root);
		const empty = qs('[data-empty]', root);
		const count = qs('[data-count]', root);
		const faDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
		const toFa = (value) => String(value).replace(/\d/g, (d) => faDigits[d]);

		const apply = () => {
			const brandVal = brand ? brand.value : '';
			const blinkVal = blink ? blink.value : '';
			const q = query ? query.value.trim() : '';
			let visible = 0;
			cards.forEach((card) => {
				const okBrand = !brandVal || card.dataset.brand === brandVal;
				const okBlink = blinkVal === '' || card.dataset.blinks === blinkVal;
				const okQuery = !q || (card.textContent || '').includes(q);
				const show = okBrand && okBlink && okQuery;
				card.hidden = !show;
				if (show) {
					visible += 1;
				}
			});
			if (empty) {
				empty.hidden = visible !== 0;
			}
			if (count) {
				count.textContent = toFa(visible);
			}
		};

		[brand, blink, query].forEach((el) => {
			if (el) {
				el.addEventListener('input', apply);
				el.addEventListener('change', apply);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		initNav();
		initHeader();
		initReveal();
		initFaq(document);
		initContact();
		initErrorFilter();
	});

	window.pixvaPostAjax = postAjax;
	window.pixvaSetBusy = setBusy;
}());
