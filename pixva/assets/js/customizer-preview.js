/**
 * پیش‌نمایش زنده سفارشی‌ساز پیکسوا
 */
(function (api) {
	'use strict';

	if (!api) {
		return;
	}

	function bindText(setting, selector) {
		api(setting, (value) => {
			value.bind((to) => {
				document.querySelectorAll(selector).forEach((node) => {
					node.textContent = to;
				});
			});
		});
	}

	function bindHref(setting, selector) {
		api(setting, (value) => {
			value.bind((to) => {
				const clean = String(to).replace(/[^\d+]/g, '');
				document.querySelectorAll(selector).forEach((node) => {
					node.setAttribute('href', `tel:${clean}`);
					const label = node.querySelector('[data-phone-text]');
					if (label) {
						label.textContent = to;
					}
				});
			});
		});
	}

	bindText('pixva_topbar_text', '.pixva-topbar__text span');
	bindText('pixva_header_cta_text', '.pixva-header-cta');
	bindText('pixva_copyright', '.pixva-copyright');
	bindHref('pixva_support_phone', '.pixva-header-phone');
}(window.wp && window.wp.customize));
