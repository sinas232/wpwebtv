/**
 * PIXVA pixel test (§10). Without JS the swatches link to :target panels.
 * With JS: a fullscreen viewer (Fullscreen API when available, fixed overlay
 * otherwise). Controls: click/tap the screen, Space or Enter = next colour;
 * ← = next colour, → = previous colour (RTL reading direction); Esc = exit.
 * On-screen buttons (previous / next / exit) work for touch, pointer and
 * keyboard users. Tab is trapped inside the viewer. Manual only: no flashing,
 * no automatic cycling. The hint fades only when reduced motion is not
 * requested. Focus returns to the trigger on exit.
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
	var labels_ui = {
		prev: root.getAttribute('data-label-prev') || 'Previous',
		next: root.getAttribute('data-label-next') || 'Next',
		exit: root.getAttribute('data-label-exit') || 'Exit'
	};
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

	function step(delta) {
		index = (index + delta + colors.length) % colors.length;
		paint();
	}

	function onKey(ev) {
		if (!overlay) {
			return;
		}
		var focusables = overlay.controlButtons || [];
		if (ev.key === 'Tab') {
			// Focus containment: the viewer is modal. Tab cycles only through its own buttons.
			ev.preventDefault();
			var at = focusables.indexOf(document.activeElement);
			var to;
			if (ev.shiftKey) {
				to = at <= 0 ? focusables.length - 1 : at - 1;
			} else {
				to = at < 0 || at === focusables.length - 1 ? 0 : at + 1;
			}
			focusables[to].focus();
			return;
		}
		if (ev.key === 'Escape') {
			close();
			return;
		}
		if (ev.key === 'ArrowLeft') {
			ev.preventDefault();
			step(1);
		} else if (ev.key === 'ArrowRight') {
			ev.preventDefault();
			step(-1);
		} else if (ev.key === ' ' || ev.key === 'Enter') {
			// A focused on-screen button keeps its native activation (Enter/Space = press it).
			if (ev.target && ev.target.tagName === 'BUTTON') {
				return;
			}
			ev.preventDefault();
			step(1);
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

	function makeButton(label, arrow, action) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = 'px-overlay__btn';
		b.setAttribute('aria-label', label);
		b.textContent = arrow;
		b.title = label;
		b.addEventListener('click', function (ev) {
			ev.stopPropagation();
			action();
		});
		return b;
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
		var bar = document.createElement('div');
		bar.className = 'px-overlay__controls';
		var prevBtn = makeButton(labels_ui.prev, '\u2192', function () { step(-1); });
		var nextBtn = makeButton(labels_ui.next, '\u2190', function () { step(1); });
		var exitBtn = makeButton(labels_ui.exit, '\u00d7', function () { close(); });
		[prevBtn, nextBtn, exitBtn].forEach(function (b) { bar.appendChild(b); });
		overlay.appendChild(bar);
		overlay.controlButtons = [prevBtn, nextBtn, exitBtn];
		overlay.addEventListener('click', function (ev) {
			if (ev.target.closest && ev.target.closest('.px-overlay__controls')) {
				return;
			}
			step(1);
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

	// If focus reaches the page behind the open viewer (e.g. a pointer or assistive tech), bring it back.
	document.addEventListener('focusin', function (ev) {
		if (overlay && !overlay.contains(ev.target)) {
			overlay.focus();
		}
	});

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
