/**
 * PIXVA pixel test (§10). Without JS the swatches link to :target panels.
 * With JS: a fullscreen viewer (Fullscreen API when available, fixed overlay
 * otherwise). Click / → / ← / Space = next or previous colour, Esc = exit.
 * Manual only — no flashing or automatic cycling; the hint fades only when
 * reduced motion is not requested. Focus returns to the trigger on exit.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-pixel-test]');
	var pixva = window.pixva || {};
	if (!root) {
		return;
	}
	var swatches = Array.prototype.slice.call(root.querySelectorAll('[data-color]'));
	var colors = swatches.map(function (a) { return a.getAttribute('data-color'); });
	var labels = swatches.map(function (a) { return a.textContent.trim(); });
	var jsBox = root.querySelector('[data-pixel-js]');
	var startBtn = root.querySelector('[data-pixel-fullscreen]');
	var hintText = jsBox ? (jsBox.querySelector('.field__help') || {}).textContent : '';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var overlay = null;
	var index = 0;
	var opener = null;
	var tracked = false;

	if (!colors.length) {
		return;
	}
	if (jsBox) {
		jsBox.hidden = false;
	}

	function paint() {
		overlay.className = 'px-overlay px--' + colors[index];
		overlay.setAttribute('aria-label', labels[index]);
	}

	function onKey(ev) {
		if (!overlay) {
			return;
		}
		if (ev.key === 'Escape') {
			close();
		} else if (ev.key === 'ArrowLeft' || ev.key === ' ' || ev.key === 'Enter') {
			ev.preventDefault();
			index = (index + 1) % colors.length; // RTL: left = forward
			paint();
		} else if (ev.key === 'ArrowRight') {
			ev.preventDefault();
			index = (index - 1 + colors.length) % colors.length;
			paint();
		}
	}

	function close() {
		if (!overlay) {
			return;
		}
		document.removeEventListener('keydown', onKey);
		if (document.fullscreenElement && document.exitFullscreen) {
			document.exitFullscreen().catch(function () {});
		}
		overlay.remove();
		overlay = null;
		document.documentElement.style.overflow = '';
		if (opener) {
			opener.focus();
		}
	}

	function open(i, trigger) {
		index = i;
		opener = trigger || startBtn;
		overlay = document.createElement('div');
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		overlay.tabIndex = -1;
		var hint = document.createElement('p');
		hint.className = 'px-overlay__hint';
		hint.textContent = hintText;
		overlay.appendChild(hint);
		paint();
		overlay.addEventListener('click', function () {
			index = (index + 1) % colors.length;
			paint();
		});
		document.body.appendChild(overlay);
		document.documentElement.style.overflow = 'hidden';
		document.addEventListener('keydown', onKey);
		overlay.focus();
		if (overlay.requestFullscreen) {
			overlay.requestFullscreen().catch(function () { /* overlay still covers the viewport */ });
		}
		if (!reduce) {
			setTimeout(function () { hint.classList.add('is-hidden'); }, 3000);
		}
		if (!tracked && pixva.track) {
			tracked = true;
			pixva.track('pixel_test_started', { label: colors[index] });
		}
	}

	document.addEventListener('fullscreenchange', function () {
		if (!document.fullscreenElement && overlay) {
			close();
		}
	});

	if (startBtn) {
		startBtn.addEventListener('click', function () {
			var current = window.location.hash.replace('#px-', '');
			var i = colors.indexOf(current);
			open(i >= 0 ? i : 0, startBtn);
		});
	}
	swatches.forEach(function (a, i) {
		a.addEventListener('click', function (ev) {
			ev.preventDefault();
			history.replaceState(null, '', '#px-' + colors[i]);
			open(i, a);
		});
	});
}());
