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
		const nodes = qsa('.pixva-reveal');
		const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		if (!nodes.length || reduce || !('IntersectionObserver' in window)) {
			nodes.forEach((node) => node.classList.add('is-visible'));
			return;
		}

		// تأخیر پلکانی: هر گروه هم‌سطح با فاصله ۹۰ms و سقف ۶۳۰ms ظاهر می‌شود.
		const groups = new Map();
		nodes.forEach((node) => {
			const parent = node.parentElement || document.body;
			const index = groups.get(parent) || 0;
			groups.set(parent, index + 1);
			node.style.setProperty('--reveal-delay', `${Math.min(index * 90, 630)}ms`);
			node.style.setProperty('--reveal-duration', `${500 + (index % 4) * 100}ms`);
		});

		const observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
		nodes.forEach((node) => observer.observe(node));
	}

	function initFaq(root) {
		qsa('[data-pixva-faq]', root).forEach((wrap) => {
			qsa('.pixva-faq__q', wrap).forEach((button) => {
				button.addEventListener('click', () => {
					const item = button.closest('.pixva-faq__item');
					const open = button.getAttribute('aria-expanded') === 'true';

					qsa('.pixva-faq__q', wrap).forEach((other) => {
						if (other === button) {
							return;
						}
						other.setAttribute('aria-expanded', 'false');
						const otherItem = other.closest('.pixva-faq__item');
						if (otherItem) {
							otherItem.classList.remove('is-open');
						}
					});

					button.setAttribute('aria-expanded', open ? 'false' : 'true');
					if (item) {
						item.classList.toggle('is-open', !open);
					}
				});
			});
		});
	}

	function initAccordions(root) {
		qsa('[data-pixva-accordion]', root).forEach((button) => {
			button.addEventListener('click', () => {
				const panel = document.getElementById(button.getAttribute('aria-controls'));
				const open = button.getAttribute('aria-expanded') === 'true';
				button.setAttribute('aria-expanded', open ? 'false' : 'true');
				const holder = button.closest('[data-pixva-accordion-item]');
				if (holder) {
					holder.classList.toggle('is-open', !open);
				}
				if (panel) {
					panel.hidden = open;
				}
			});
		});
	}

	/**
	 * آکاردئون آبشاری منوی اصلی داخل دروئر موبایل (چندسطحی).
	 */
	function initDrawerNav() {
		const drawer = qs('[data-pixva-drawer]');
		if (!drawer) {
			return;
		}

		const closeItem = (item) => {
			item.classList.remove('is-open');
			const toggle = qs('[data-drawer-toggle]', item);
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'false');
			}
		};

		const closeAll = () => {
			qsa('.pixva-drawer-nav__item.is-open', drawer).forEach(closeItem);
		};

		qsa('[data-drawer-toggle]', drawer).forEach((toggle) => {
			toggle.addEventListener('click', () => {
				const item = toggle.closest('.pixva-drawer-nav__item');
				if (!item) {
					return;
				}
				const open = !item.classList.contains('is-open');

				// رفتار آکاردئونی: هم‌سطح‌های باز جمع می‌شوند.
				const siblings = item.parentElement ? qsa(':scope > .pixva-drawer-nav__item.is-open', item.parentElement) : [];
				siblings.forEach((sibling) => {
					if (sibling !== item) {
						closeItem(sibling);
					}
				});

				item.classList.toggle('is-open', open);
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		});

		const closeBtn = qs('[data-pixva-drawer-close]', drawer);
		const overlay = qs('[data-pixva-overlay]');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeAll);
		}
		if (overlay) {
			overlay.addEventListener('click', closeAll);
		}
		document.addEventListener('keydown', (event) => {
			if ('Escape' === event.key) {
				closeAll();
			}
		});
	}

	function initMegaMenu() {
		const nav = qs('[data-pixva-mega]');
		if (!nav) {
			return;
		}
		const items = qsa('[data-mega-item]', nav);
		let hoverTimer = null;
		const isTouch = Boolean(window.matchMedia && window.matchMedia('(hover: none)').matches);

		const closeAll = (except) => {
			items.forEach((item) => {
				if (item === except) {
					return;
				}
				const trigger = qs('[data-mega-trigger]', item);
				item.classList.remove('is-open');
				if (trigger) {
					trigger.setAttribute('aria-expanded', 'false');
				}
			});
		};

		const setOpen = (item, open) => {
			const trigger = qs('[data-mega-trigger]', item);
			item.classList.toggle('is-open', open);
			if (trigger) {
				trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
			}
		};

		items.forEach((item) => {
			const trigger = qs('[data-mega-trigger]', item);
			if (!trigger) {
				return;
			}

			trigger.addEventListener('click', () => {
				const open = !item.classList.contains('is-open');
				closeAll(item);
				setOpen(item, open);
			});

			if (!isTouch) {
				item.addEventListener('mouseenter', () => {
					window.clearTimeout(hoverTimer);
					closeAll(item);
					setOpen(item, true);
				});
				item.addEventListener('mouseleave', () => {
					hoverTimer = window.setTimeout(() => setOpen(item, false), 180);
				});
			}

			trigger.addEventListener('keydown', (event) => {
				if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
					closeAll(item);
					setOpen(item, true);
					const firstLink = qs('.pixva-mega__panel a', item);
					if (event.key === 'ArrowDown' && firstLink) {
						event.preventDefault();
						firstLink.focus();
					}
				}
			});

			item.addEventListener('keydown', (event) => {
				if (event.key === 'Escape') {
					setOpen(item, false);
					trigger.focus();
				}
			});
		});

		document.addEventListener('click', (event) => {
			if (!nav.contains(event.target)) {
				closeAll(null);
			}
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				closeAll(null);
			}
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
		initDrawerNav();
		initHeader();
		initMegaMenu();
		initReveal();
		initFaq(document);
		initAccordions(document);
		initContact();
		initErrorFilter();
	});

	window.pixvaPostAjax = postAjax;
	window.pixvaSetBusy = setBusy;
}());
