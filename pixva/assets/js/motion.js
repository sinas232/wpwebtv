/**
 * موتور حرکت پیکسوا (لایه ۲۸ — نسخه ۱٫۵٫۰)
 *
 * چهار رفتار کاملاً GPU-پسند و بدون وابستگی:
 *  ۱) ظهور قطعات هنگام اسکرول (IntersectionObserver، Fade-Up / Scale-In پلکانی)
 *  ۲) موج نوری کلیک (Ripple) در موضع کلیک روی دکمه‌ها و کارت‌های علامت‌گذاری‌شده
 *  ۳) هاور مغناطیسی + بازتاب نور محیطی OLED در لبه کارت‌ها
 *  ۴) اسکرول نرم برای لینک‌های لنگری با احتساب ارتفاع هدر چسبان
 *
 * همه انیمیشن‌ها فقط transform/opacity هستند؛ در حالت prefers-reduced-motion
 * تمام رفتارها غیرفعال می‌شوند و محتوا بدون انیمیشن دیده می‌شود.
 * بدون JS هم هیچ چیزی پنهان نمی‌ماند (کلاس pixva-motion-js روی <html> شرط است).
 */
(function () {
	'use strict';

	var reduce = Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

	function qs(selector, root) {
		return (root || document).querySelector(selector);
	}

	function qsa(selector, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(selector));
	}

	/* ==================================================================
	   ۱) ظهور قطعات هنگام اسکرول
	   ================================================================== */

	// قطعه‌هایی که هنگام اسکرول ظاهر می‌شوند (بدون تداخل با .pixva-reveal قدیمی).
	var REVEAL_TARGETS = [
		'.pixva-section-head',
		'.pixva-card',
		'.pixva-post-card',
		'.pixva-widget',
		'.pixva-service-card',
		'.pixva-brand-tile',
		'.pixva-tool-shell',
		'.pixva-crm-order',
		'.pixva-warranty',
		'.pixva-invoice',
		'.pixva-advantage',
		'.pixva-work-item',
		'.pixva-stat',
		'[data-fx]'
	].join(', ');

	var observer = null;

	function markVisible(node) {
		node.classList.add('is-in');
		if (observer) {
			observer.unobserve(node);
		}
	}

	function collect(root) {
		return qsa(REVEAL_TARGETS, root).filter(function (node) {
			if (node.dataset.fxBound === '1' || node.hasAttribute('data-fx-off')) {
				return false;
			}
			// .pixva-reveal توسط main.js مدیریت می‌شود؛ دوباره انیمیت نمی‌کنیم.
			return !node.classList.contains('pixva-reveal');
		});
	}

	function stagger(nodes) {
		var groups = {};
		nodes.forEach(function (node) {
			var parent = node.parentElement || document.body;
			if (!parent.dataset.fxGroup) {
				parent.dataset.fxGroup = 'g' + Math.random().toString(36).slice(2, 8);
			}
			var key = parent.dataset.fxGroup;
			var index = groups[key] || 0;
			groups[key] = index + 1;
			node.style.setProperty('--fx-delay', Math.min(index * 70, 560) + 'ms');
		});
	}

	function initReveal(root) {
		var nodes = collect(root || document);
		if (!nodes.length) {
			return;
		}

		nodes.forEach(function (node) {
			node.dataset.fxBound = '1';
			node.classList.add('pixva-fx');
		});

		if (reduce || !('IntersectionObserver' in window)) {
			nodes.forEach(function (node) {
				node.classList.add('is-in');
			});
			return;
		}

		stagger(nodes);

		if (!observer) {
			observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						markVisible(entry.target);
					}
				});
			}, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });
		}

		nodes.forEach(function (node) {
			observer.observe(node);
		});
	}

	/* ==================================================================
	   ۲) موج نوری کلیک (Ripple)
	   ================================================================== */
	var RIPPLE_TARGETS = '.pixva-btn, button, .pixva-choice, .pixva-crm__chip, .pixva-mega__link, .pixva-mega__trigger, [data-ripple]';

	function initRipple() {
		document.addEventListener('pointerdown', function (event) {
			if (reduce || event.button !== 0) {
				return;
			}

			var host = event.target.closest ? event.target.closest(RIPPLE_TARGETS) : null;
			if (!host || host.disabled || host.getAttribute('aria-disabled') === 'true') {
				return;
			}

			var rect = host.getBoundingClientRect();
			if (!rect.width || !rect.height) {
				return;
			}

			var size = Math.max(rect.width, rect.height) * 2.1;
			var ripple = document.createElement('span');
			ripple.className = 'pixva-ripple';
			ripple.setAttribute('aria-hidden', 'true');
			ripple.style.width = size + 'px';
			ripple.style.height = size + 'px';
			ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
			ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';

			host.classList.add('pixva-ripple-host');
			host.appendChild(ripple);

			var cleanup = function () {
				if (ripple.parentNode) {
					ripple.parentNode.removeChild(ripple);
				}
			};
			ripple.addEventListener('animationend', cleanup);
			window.setTimeout(cleanup, 900);
		}, { passive: true });
	}

	/* ==================================================================
	   ۳) هاور مغناطیسی و بازتاب نور محیطی (OLED Ambient)
	   ================================================================== */
	var MAGNETIC_TARGETS = '[data-magnetic], .pixva-card, .pixva-post-card, .pixva-advantage, .pixva-work-item';
	var MAX_PULL = 5; // حداکثر جابه‌جایی مغناطیسی به پیکسل

	function initMagnetic(root) {
		if (reduce) {
			return;
		}

		qsa(MAGNETIC_TARGETS, root || document).forEach(function (card) {
			if (card.dataset.magneticBound === '1') {
				return;
			}
			card.dataset.magneticBound = '1';
			card.classList.add('pixva-magnetic');

			var frame = null;
			var point = null;

			var apply = function () {
				if (frame) {
					return;
				}
				frame = window.requestAnimationFrame(function () {
					frame = null;
					if (!point) {
						return;
					}
					card.style.setProperty('--mx', point.x + '%');
					card.style.setProperty('--my', point.y + '%');
					card.style.setProperty('--pull-x', point.pullX + 'px');
					card.style.setProperty('--pull-y', point.pullY + 'px');
					card.style.setProperty('--edge-angle', point.angle + 'deg');
				});
			};

			card.addEventListener('pointermove', function (event) {
				if (event.pointerType === 'touch') {
					return;
				}
				var rect = card.getBoundingClientRect();
				if (!rect.width || !rect.height) {
					return;
				}
				var dx = event.clientX - rect.left;
				var dy = event.clientY - rect.top;
				var x = (dx / rect.width) * 100;
				var y = (dy / rect.height) * 100;

				point = {
					x: x.toFixed(2),
					y: y.toFixed(2),
					pullX: (((x - 50) / 50) * MAX_PULL).toFixed(2),
					pullY: (((y - 50) / 50) * MAX_PULL - 3).toFixed(2),
					angle: (Math.atan2(dy - rect.height / 2, dx - rect.width / 2) * 180 / Math.PI + 90).toFixed(1)
				};
				card.classList.add('is-magnetic');
				apply();
			}, { passive: true });

			card.addEventListener('pointerleave', function () {
				point = null;
				card.classList.remove('is-magnetic');
				card.style.setProperty('--mx', '50%');
				card.style.setProperty('--my', '50%');
				card.style.setProperty('--pull-x', '0px');
				card.style.setProperty('--pull-y', '0px');
			}, { passive: true });
		});
	}

	/* ==================================================================
	   ۴) اسکرول نرم لنگری با احتساب هدر چسبان
	   ================================================================== */
	function headerOffset() {
		var header = qs('.pixva-header') || qs('[data-pixva-header]');
		return header ? header.getBoundingClientRect().height + 14 : 16;
	}

	function smoothScrollTo(target) {
		var end = target.getBoundingClientRect().top + window.pageYOffset - headerOffset();
		var start = window.pageYOffset;
		var distance = end - start;
		var duration = Math.min(900, Math.max(320, Math.abs(distance) * 0.42));
		var began = null;

		if (Math.abs(distance) < 2) {
			return;
		}

		var ease = function (t) {
			return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
		};

		var step = function (stamp) {
			if (began === null) {
				began = stamp;
			}
			var progress = Math.min(1, (stamp - began) / duration);
			window.scrollTo(0, start + distance * ease(progress));
			if (progress < 1) {
				window.requestAnimationFrame(step);
			}
		};

		window.requestAnimationFrame(step);
	}

	function initSmoothScroll() {
		document.addEventListener('click', function (event) {
			var link = event.target.closest ? event.target.closest('a[href^="#"]') : null;
			if (!link || link.getAttribute('data-no-smooth') !== null) {
				return;
			}

			var hash = link.getAttribute('href') || '';
			if (hash.length < 2) {
				return;
			}

			var id;
			try {
				id = decodeURIComponent(hash.slice(1));
			} catch (error) {
				return;
			}

			var target = document.getElementById(id) || qs('a[name="' + id + '"]');
			if (!target) {
				return;
			}

			event.preventDefault();

			if (reduce) {
				target.scrollIntoView();
			} else {
				smoothScrollTo(target);
			}

			if (window.history && window.history.replaceState) {
				try {
					window.history.replaceState(null, '', hash);
				} catch (error) {
					// در iframeهای محدود یا فایل محلی، به‌روزرسانی تاریخ مجاز نیست.
				}
			}

			// دسترسی‌پذیری: فوکوس بدون اسکرول اضافی.
			target.setAttribute('tabindex', '-1');
			window.setTimeout(function () {
				try {
					target.focus({ preventScroll: true });
				} catch (error) {
					target.focus();
				}
			}, reduce ? 0 : 360);
		});
	}

	/* ==================================================================
	   راه‌اندازی
	   ================================================================== */
	function boot() {
		document.documentElement.classList.add('pixva-motion-js');
		initReveal(document);
		initRipple();
		initMagnetic(document);
		initSmoothScroll();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// برای قطعه‌هایی که بعداً با AJAX/Elementor اضافه می‌شوند.
	window.pixvaMotionScan = function (root) {
		initReveal(root || document);
		initMagnetic(root || document);
	};

	// پس از تغییر اندازه پنجره، ارتفاع هدر تازه می‌شود.
	window.pixvaMotionHeaderOffset = headerOffset;
}());
