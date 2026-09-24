/**
 * اسلایدر لمسی قبل و بعد، بدون کتابخانه
 */
(function () {
	'use strict';

	function syncWidth(root) {
		const image = root.querySelector('.pixva-ba__before img');
		if (!image) {
			return;
		}
		image.style.width = `${root.clientWidth}px`;
	}

	function setValue(root, value) {
		const next = Math.min(100, Math.max(0, Number(value)));
		root.style.setProperty('--pixva-ba', `${next}%`);
		const range = root.querySelector('.pixva-ba__range');
		if (range && Number(range.value) !== next) {
			range.value = String(next);
		}
	}

	function init(root) {
		const range = root.querySelector('.pixva-ba__range');
		syncWidth(root);
		if (range) {
			setValue(root, range.value);
			range.addEventListener('input', () => setValue(root, range.value));
		}

		const move = (clientX) => {
			const rect = root.getBoundingClientRect();
			if (!rect.width) {
				return;
			}
			const ratio = (clientX - rect.left) / rect.width;
			setValue(root, Math.round(ratio * 100));
		};

		root.addEventListener('pointerdown', (event) => {
			if (event.target === range) {
				return;
			}
			root.setPointerCapture(event.pointerId);
			move(event.clientX);
		});
		root.addEventListener('pointermove', (event) => {
			if (!root.hasPointerCapture(event.pointerId)) {
				return;
			}
			move(event.clientX);
		});
		window.addEventListener('resize', () => syncWidth(root));
	}

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('[data-pixva-ba]').forEach(init);
	});
}());
