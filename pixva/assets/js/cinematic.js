/**
 * موتور اسکرول سینمایی پیکسوا — لایه ۱٫۶٫۰ (Master Prompt v7).
 *
 * ویجت «Cinematic TV Unboxing»: المان در مرکز صفحه قفل (Pin) می‌شود و با اسکرول،
 * لایه‌های دستگاه (پنل، بک‌لایت، برد) به‌صورت نمای انفجاری سه‌بعدی از هم باز
 * می‌شوند؛ کنار هر قطعه هم توضیح خدمت تعمیر آن با تأخیر نرم ظاهر می‌شود.
 *
 * این فایل فقط از زیرمجموعه مشترک GSAP/ScrollTrigger استفاده می‌کند تا هم با
 * موتور سبک داخلی (pixva-gsap-lite) و هم با GSAP رسمی کار کند.
 *
 * @package Pixva
 * @since   1.6.0
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaCine || {};
	var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var reduce = !!media.matches;
	var rtl = 'rtl' === (document.documentElement.getAttribute('dir') || '');
	var instances = [];

	function qsa(selector, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(selector));
	}

	function qs(selector, root) {
		return (root || document).querySelector(selector);
	}

	function toFa(value) {
		var fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
		return String(value).replace(/[0-9]/g, function (d) { return fa[parseInt(d, 10)]; });
	}

	function number(value, fallback) {
		var parsed = parseFloat(value);
		return isFinite(parsed) ? parsed : fallback;
	}

	function cssVarLength(section) {
		var raw = window.getComputedStyle(section).getPropertyValue('--cine-length') || '';
		var parsed = parseFloat(raw);
		return isFinite(parsed) && parsed > 0 ? parsed : 320;
	}

	function revealAll(section) {
		section.classList.add('is-static');
		qsa('[data-cine-layer], [data-cine-note]', section).forEach(function (node) {
			node.style.opacity = '';
			node.style.transform = '';
			node.classList.add('is-in');
		});
		var bar = qs('[data-cine-bar]', section);
		if (bar) { bar.style.transform = 'scaleX(1)'; }
	}

	/**
	 * ساخت تایم‌لاین نمای انفجاری برای یک بخش.
	 *
	 * @param {HTMLElement} section بخش ویجت.
	 * @return {object|null} تایم‌لاین ساخته‌شده.
	 */
	function buildTimeline(section) {
		var gsap = window.gsap;
		var scene = qs('[data-cine-scene]', section);
		var layers = qsa('[data-cine-layer]', section);
		var notes = qsa('[data-cine-note]', section);

		if (!gsap || !layers.length) { return null; }

		var speed = Math.max(0.2, number(section.dataset.cineSpeed, 1));
		var spread = Math.max(20, number(section.dataset.cineSpread, 120));
		var rotate = Math.max(0, number(section.dataset.cineRotate, 16));
		var count = layers.length;
		var middle = (count - 1) / 2;
		var slide = rtl ? -46 : 46;

		var tl = gsap.timeline({ paused: true });

		layers.forEach(function (layer, index) {
			var depth = number(layer.dataset.cineDepth, index + 1);
			var centerDepth = (count + 1) / 2;
			var pushZ = (depth - centerDepth) * spread;
			var pushY = (index - middle) * (spread * 0.26);
			var pushX = (index - middle) * (spread * 0.12) * (rtl ? -1 : 1);
			var tiltX = (index - middle) * (rotate * 0.42);
			var tiltY = (depth - centerDepth) * (rotate * 0.5);

			tl.fromTo(
				layer,
				{ x: 0, y: 0, z: 0, rotationX: 0, rotationY: 0, opacity: 1, duration: 0.9 / speed },
				{
					x: pushX,
					y: pushY,
					z: pushZ,
					rotationX: tiltX,
					rotationY: tiltY,
					opacity: 0.98,
					ease: 'power1.inOut',
					duration: 0.9 / speed
				},
				(index / count) * 0.5
			);
		});

		notes.forEach(function (note, index) {
			tl.fromTo(
				note,
				{ opacity: 0, x: slide, y: 12, duration: 0.34 },
				{ opacity: 1, x: 0, y: 0, ease: 'power2.out', duration: 0.34 },
				0.1 + (index / count) * 0.66
			);
		});

		if (scene && rotate > 0) {
			tl.fromTo(
				scene,
				{ rotationY: rtl ? rotate * -0.22 : rotate * 0.22, duration: 1.2 },
				{ rotationY: rtl ? rotate * 0.22 : rotate * -0.22, ease: 'none', duration: 1.2 },
				0
			);
		}

		return tl;
	}

	/**
	 * راه‌اندازی یک بخش سینمایی (Pin + Scrub).
	 *
	 * @param {HTMLElement} section بخش.
	 */
	function initSection(section) {
		if ('1' === section.dataset.cineBound) { return; }
		section.dataset.cineBound = '1';

		var notes = qsa('[data-cine-note]', section);
		var bar = qs('[data-cine-bar]', section);
		var stepEl = qs('[data-cine-step]', section);
		var stage = qs('[data-cine-stage]', section);
		var usePin = '1' === section.dataset.cinePin && !!stage;
		var scrub = '1' !== section.dataset.cineScrub ? false : true;

		if (reduce || !window.gsap || !window.ScrollTrigger) {
			revealAll(section);
			return;
		}

		var timeline = buildTimeline(section);
		if (!timeline) {
			revealAll(section);
			return;
		}

		var speed = Math.max(0.2, number(section.dataset.cineSpeed, 1));
		var lengthVh = cssVarLength(section) / speed;
		var length = Math.max(window.innerHeight, Math.round(window.innerHeight * (lengthVh / 100)));

		var lastStep = -1;
		var trigger = window.ScrollTrigger.create({
			trigger: section,
			start: 'top top',
			end: '+=' + length,
			pin: usePin ? stage : null,
			scrub: scrub,
			animation: timeline,
			invalidateOnRefresh: true,
			onUpdate: function (self) {
				var progress = 'number' === typeof self.progress ? self.progress : 0;
				if (bar) { bar.style.transform = 'scaleX(' + (Math.round(progress * 1000) / 1000) + ')'; }

				var step = notes.length ? Math.max(1, Math.min(notes.length, Math.ceil(progress * notes.length))) : 0;
				if (step !== lastStep) {
					lastStep = step;
					section.dataset.cineStep = String(step);
					if (stepEl) { stepEl.textContent = toFa(step) + ' / ' + toFa(notes.length); }
					notes.forEach(function (note, index) {
						note.classList.toggle('is-active', index === step - 1);
					});
				}
			},
			onEnter: function () { section.classList.add('is-live'); },
			onEnterBack: function () { section.classList.add('is-live'); },
			onLeave: function () { section.classList.remove('is-live'); },
			onLeaveBack: function () { section.classList.remove('is-live'); }
		});

		instances.push({ section: section, trigger: trigger, timeline: timeline });
	}

	function scan(root) {
		qsa('[data-pixva-cine]', root || document).forEach(initSection);
		if (window.ScrollTrigger && 'function' === typeof window.ScrollTrigger.refresh) {
			window.ScrollTrigger.refresh();
		}
	}

	function boot() {
		document.documentElement.classList.add('pixva-cine-js');
		scan(document);

		if (media.addEventListener) {
			media.addEventListener('change', function (event) {
				reduce = event.matches;
				if (reduce) {
					instances.forEach(function (item) {
						if (item.trigger && item.trigger.kill) { item.trigger.kill(); }
						revealAll(item.section);
					});
				}
			});
		}

		window.addEventListener('load', function () {
			if (window.ScrollTrigger && 'function' === typeof window.ScrollTrigger.refresh) {
				window.ScrollTrigger.refresh();
			}
		}, { passive: true });
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// برای Elementor/AJAX: اسکن دوباره قطعه‌های تازه اضافه‌شده.
	window.pixvaCinematicScan = scan;
	window.pixvaCinematicInstances = function () { return instances.slice(); };

	// المنتور رویداد بومی frontend/init را روی document منتشر می‌کند (بدون jQuery).
	document.addEventListener('elementor/frontend/init', function () { scan(document); });

	void cfg;
}(window, document));
