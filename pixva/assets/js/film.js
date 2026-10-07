/**
 * کج‌شدن آرام قاب هیرو با نشانگر. خودِ تصاویر با CSS عوض می‌شوند.
 */
(function () {
	'use strict';

	var film = document.querySelector('[data-pixva-film]');
	if (!film) {
		return;
	}
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}
	if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
		return;
	}

	film.addEventListener('pointermove', function (event) {
		var box = film.getBoundingClientRect();
		if (!box.width || !box.height) {
			return;
		}
		var x = (event.clientX - box.left) / box.width - 0.5;
		var y = (event.clientY - box.top) / box.height - 0.5;
		film.style.transform = 'perspective(1100px) rotateY(' + (x * -5).toFixed(2) + 'deg) rotateX(' + (y * 3.2).toFixed(2) + 'deg)';
	});

	film.addEventListener('pointerleave', function () {
		film.style.transform = '';
	});
}());
