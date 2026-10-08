/**
 * جابه‌جایی زبانه‌های ابزار صفحه اصلی. بدون وابستگی.
 */
(function () {
	'use strict';

	const shell = document.querySelector('[data-px-tools]');
	if (!shell) {
		return;
	}
	const tabs = Array.from(shell.querySelectorAll('[data-tool]'));
	const panes = Array.from(shell.querySelectorAll('[data-pane]'));

	function open(id) {
		tabs.forEach(function (tab) {
			const on = tab.getAttribute('data-tool') === id;
			tab.classList.toggle('is-on', on);
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		panes.forEach(function (pane) {
			const on = pane.getAttribute('data-pane') === id;
			pane.classList.toggle('is-on', on);
			pane.hidden = !on;
		});
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			open(tab.getAttribute('data-tool'));
		});
	});
}());
