/**
 * رندر سه‌بعدی تعاملی (Spline WebGL) — لایه ۱٫۶٫۰.
 *
 * - ماژول spline-viewer فقط وقتی لازم شود (نزدیک شدن بخش به viewport) و فقط یک‌بار
 *   از آدرس تنظیم‌شده بارگذاری می‌شود؛ هیچ باری روی LCP صفحه نمی‌افتد.
 * - اگر مدل لود نشد یا آدرسی تنظیم نشده بود، نمای لایه‌ای داخلی (CSS 3D) با
 *   چرخش کشیدنی فعال می‌ماند تا بخش هیچ‌وقت خالی نباشد.
 * - نقطه‌های قرمز (هات‌اسپیت) با کلیک، کارت استعلام قیمت همان قطعه را باز می‌کنند.
 *
 * @package Pixva
 * @since   1.6.0
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaSpline || {};
	var i18n = cfg.i18n || {};
	var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
	var viewerPromise = null;

	function qs(selector, root) { return (root || document).querySelector(selector); }
	function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

	function rtlSign() {
		return 'rtl' === (document.documentElement.getAttribute('dir') || '') ? -1 : 1;
	}

	function setStatus(section, message, kind) {
		var status = qs('[data-spline-status]', section);
		if (!status) { return; }
		status.hidden = !message;
		status.textContent = message || '';
		status.className = 'pixva-3d__status' + (kind ? ' is-' + kind : '');
	}

	/**
	 * بارگذاری یک‌باره ماژول رسمی Spline (type=module).
	 *
	 * @return {Promise}
	 */
	function loadViewer() {
		if (viewerPromise) { return viewerPromise; }

		var url = cfg.viewerUrl || '';
		if (!url) {
			viewerPromise = Promise.reject(new Error('no-viewer-url'));
			return viewerPromise;
		}

		viewerPromise = new Promise(function (resolve, reject) {
			if (window.customElements && window.customElements.get('spline-viewer')) {
				resolve();
				return;
			}

			var script = document.createElement('script');
			script.type = 'module';
			script.src = url;
			script.async = true;
			script.onload = function () { resolve(); };
			script.onerror = function () { reject(new Error('viewer-load-failed')); };
			document.head.appendChild(script);

			window.setTimeout(function () {
				if (!(window.customElements && window.customElements.get('spline-viewer'))) {
					reject(new Error('viewer-timeout'));
				}
			}, 15000);
		});

		return viewerPromise;
	}

	function mountViewer(section) {
		var url = section.dataset.splineUrl || '';
		var host = qs('[data-spline-host]', section);
		if (!url || !host || '1' === host.dataset.splineMounted) { return Promise.resolve(false); }

		host.dataset.splineMounted = '1';
		setStatus(section, i18n.loading || '', 'loading');

		return loadViewer().then(function () {
			var viewer = document.createElement('spline-viewer');
			viewer.setAttribute('url', url);
			viewer.setAttribute('loading-anim-type', 'none');
			viewer.className = 'pixva-3d__viewer';

			viewer.addEventListener('load', function () {
				section.classList.add('is-3d');
				section.classList.remove('is-fallback');
				setStatus(section, '', '');
			});
			viewer.addEventListener('error', function () {
				section.classList.remove('is-3d');
				section.classList.add('is-fallback');
				setStatus(section, i18n.failed || '', 'error');
			});

			host.appendChild(viewer);

			// در برخی نسخه‌ها رویداد load شلیک نمی‌شود؛ پس از مهلت بررسی می‌کنیم.
			window.setTimeout(function () {
				if (!section.classList.contains('is-3d') && viewer.isConnected) {
					section.classList.add('is-3d');
					setStatus(section, '', '');
				}
			}, 6000);

			return true;
		}).catch(function () {
			host.dataset.splineMounted = '0';
			section.classList.add('is-fallback');
			setStatus(section, i18n.failed || '', 'error');
			return false;
		});
	}

	/**
	 * نمای لایه‌ای داخلی با چرخش کشیدنی (جایگزین مدل Spline).
	 *
	 * @param {HTMLElement} section بخش ویجت.
	 */
	function initFallback(section) {
		var view = qs('[data-spline-fallback-view]', section);
		if (!view || '1' === view.dataset.fallbackBound) { return; }
		view.dataset.fallbackBound = '1';

		var tv = qs('.pixva-3d__tv', view);
		if (!tv) { return; }

		var rotX = -8;
		var rotY = rtlSign() * 22;
		var dragging = false;
		var lastX = 0;
		var lastY = 0;

		function apply() {
			tv.style.setProperty('--rot-x', rotX.toFixed(2) + 'deg');
			tv.style.setProperty('--rot-y', rotY.toFixed(2) + 'deg');
		}

		apply();

		view.addEventListener('pointerdown', function (event) {
			dragging = true;
			lastX = event.clientX;
			lastY = event.clientY;
			view.classList.add('is-dragging');
			try { view.setPointerCapture(event.pointerId); } catch (error) { /* مرورگر قدیمی */ }
		});

		view.addEventListener('pointermove', function (event) {
			if (!dragging) { return; }
			var dx = event.clientX - lastX;
			var dy = event.clientY - lastY;
			lastX = event.clientX;
			lastY = event.clientY;
			rotY += dx * 0.32;
			rotX = Math.max(-42, Math.min(42, rotX - dy * 0.24));
			apply();
		});

		['pointerup', 'pointercancel', 'pointerleave'].forEach(function (type) {
			view.addEventListener(type, function () {
				dragging = false;
				view.classList.remove('is-dragging');
			});
		});

		if (!media.matches) {
			var auto = window.setInterval(function () {
				if (dragging || document.hidden || section.classList.contains('is-3d')) { return; }
				rotY += 0.18 * rtlSign();
				apply();
			}, 60);
			section.dataset.fallbackAuto = String(auto);
		}
	}

	function initHotspots(section) {
		var panel = qs('[data-spline-panel]', section);
		if (!panel) { return; }

		var close = qs('[data-spline-close]', section);
		var titleEl = qs('[data-spline-panel-title]', panel);
		var textEl = qs('[data-spline-panel-text]', panel);
		var partEl = qs('[data-spline-panel-part]', panel);
		var ctaEl = qs('[data-spline-panel-cta]', panel);
		var lastFocus = null;

		function open(hot) {
			lastFocus = document.activeElement;
			var label = qs('.pixva-3d__hot-label', hot);
			if (titleEl) { titleEl.textContent = label ? label.textContent : ''; }
			if (textEl) { textEl.textContent = hot.dataset.splineText || ''; }
			if (partEl) { partEl.textContent = hot.dataset.splinePart || hot.dataset.splineHot || ''; }
			if (ctaEl && hot.dataset.splineHref) { ctaEl.setAttribute('href', hot.dataset.splineHref); }

			panel.hidden = false;
			panel.classList.add('is-open');
			qsa('[data-spline-hot]', section).forEach(function (item) {
				item.classList.toggle('is-active', item === hot);
				item.setAttribute('aria-expanded', item === hot ? 'true' : 'false');
			});
			if (ctaEl) { ctaEl.focus({ preventScroll: true }); }
		}

		function shut() {
			panel.hidden = true;
			panel.classList.remove('is-open');
			qsa('[data-spline-hot]', section).forEach(function (item) {
				item.classList.remove('is-active');
				item.setAttribute('aria-expanded', 'false');
			});
			if (lastFocus && lastFocus.focus) { lastFocus.focus({ preventScroll: true }); }
		}

		qsa('[data-spline-hot]', section).forEach(function (hot) {
			hot.setAttribute('aria-expanded', 'false');
			hot.addEventListener('click', function (event) {
				event.preventDefault();
				if (panel.hidden) { open(hot); } else { shut(); }
			});
		});

		if (close) { close.addEventListener('click', shut); }
		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && !panel.hidden && section.contains(document.activeElement)) { shut(); }
		});
	}

	function initSection(section) {
		if ('1' === section.dataset.splineBound) { return; }
		section.dataset.splineBound = '1';

		var url = section.dataset.splineUrl || '';
		var lazy = '1' === section.dataset.splineLazy;

		if (!url) { section.classList.add('is-fallback'); }

		initFallback(section);
		initHotspots(section);

		if (!url) { return; }

		if (!lazy || !('IntersectionObserver' in window)) {
			mountViewer(section);
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					observer.disconnect();
					mountViewer(section);
				}
			});
		}, { rootMargin: '420px 0px' });

		observer.observe(section);
	}

	function scan(root) {
		qsa('[data-pixva-spline]', root || document).forEach(initSection);
	}

	function boot() {
		document.documentElement.classList.add('pixva-3d-js');
		scan(document);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaSplineScan = scan;
	window.pixvaSplineLoadViewer = loadViewer;
	document.addEventListener('elementor/frontend/init', function () { scan(document); });
}(window, document));
