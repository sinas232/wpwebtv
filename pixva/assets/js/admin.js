/**
 * PIXVA admin: media picker for image meta fields
 * ([data-pixva-image] wrapper with a numeric attachment-id input (usable
 * without JS), preview and
 * [data-pixva-image-pick] / [data-pixva-image-clear] buttons).
 */
(function () {
	'use strict';

	if (!window.wp || !wp.media) {
		return;
	}
	document.addEventListener('click', function (ev) {
		var pick = ev.target.closest('[data-pixva-image-pick]');
		var clear = ev.target.closest('[data-pixva-image-clear]');
		var box = (pick || clear) ? (pick || clear).closest('[data-pixva-image]') : null;
		if (!box) {
			return;
		}
		ev.preventDefault();
		var input = box.querySelector('input');
		var preview = box.querySelector('.pixva-image-preview');
		if (clear) {
			input.value = '';
			if (preview) { preview.textContent = ''; }
			return;
		}
		var frame = wp.media({ title: pick.textContent, multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			input.value = String(a.id);
			if (preview) {
				preview.textContent = '';
				var img = document.createElement('img');
				img.src = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
				img.alt = '';
				preview.appendChild(img);
			}
		});
		frame.open();
	});
}());
