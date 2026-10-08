/**
 * PIXVA before/after comparison (progressive enhancement).
 * Without JS both images are shown side by side. With JS the second image
 * overlays the first and a native, labelled range input controls the split
 * (keyboard, touch and screen-reader operable; no pointer-only gestures).
 */
(function () {
	'use strict';

	function init(fig) {
		var items = fig.querySelectorAll('.compare__item');
		if (items.length < 2 || fig.classList.contains('is-slider')) {
			return;
		}
		var range = document.createElement('input');
		range.type = 'range';
		range.min = '0';
		range.max = '100';
		range.step = '1';
		range.value = '50';
		range.dir = 'ltr';
		range.className = 'compare__range';
		range.setAttribute('aria-label', fig.getAttribute('data-compare-label') || 'مقایسه قبل و بعد');
		var set = function (v) {
			fig.style.setProperty('--pos', v + '%');
			range.setAttribute('aria-valuetext', v + '%');
		};
		range.addEventListener('input', function () {
			set(range.value);
		});
		fig.classList.add('is-slider');
		fig.appendChild(range);
		set(50);
	}

	document.querySelectorAll('[data-compare]').forEach(init);
}());
