/**
 * استعلام وضعیت تعمیر و رسم تایم‌لاین
 */
(function () {
	'use strict';

	const cfg = window.pixvaTheme || { nonce: {} };

	function el(tag, className, text) {
		const node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (text != null) {
			node.textContent = text;
		}
		return node;
	}

	function render(root, data) {
		root.replaceChildren();
		const card = el('article', 'pixva-card pixva-track-card');
		const head = el('p', 'pixva-badge', data.statusLabel || '');
		card.appendChild(head);

		const dl = document.createElement('dl');
		const rows = [
			['کد پیگیری', data.code],
			['برند', data.brand],
			['مدل', data.model],
			['شرح', data.problem],
			['برآورد', data.estimate],
			['شماره', data.phoneMask],
			['آخرین به‌روزرسانی', data.updated],
		];
		rows.forEach((row) => {
			if (!row[1]) {
				return;
			}
			dl.appendChild(el('dt', '', row[0]));
			dl.appendChild(el('dd', '', row[1]));
		});
		card.appendChild(dl);

		const list = el('ol', 'pixva-timeline');
		(data.timeline || []).forEach((step, index) => {
			const item = el('li', `is-${step.state}`);
			const dot = el('span', 'pixva-timeline__dot', String(index + 1));
			const body = el('div', '');
			body.appendChild(el('h3', '', step.label));
			if (step.date) {
				body.appendChild(el('time', '', step.date));
			} else if (step.state === 'upcoming') {
				body.appendChild(el('p', '', 'در انتظار'));
			} else if (step.state === 'current') {
				body.appendChild(el('p', '', 'مرحله فعلی'));
			}
			item.appendChild(dot);
			item.appendChild(body);
			list.appendChild(item);
		});
		card.appendChild(list);
		root.appendChild(card);
	}

	document.addEventListener('DOMContentLoaded', () => {
		const form = document.querySelector('[data-pixva-track]');
		if (!form) {
			return;
		}
		const result = document.querySelector('[data-track-result]');
		const msg = form.querySelector('[data-track-msg]');

		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = form.querySelector('[type="submit"]');
			if (msg) {
				msg.hidden = true;
			}
			if (result) {
				result.replaceChildren();
			}
			if (window.pixvaSetBusy) {
				window.pixvaSetBusy(button, true);
			}
			const codeVal = form.querySelector('[name="code"]').value.trim().toUpperCase();
			const phoneVal = form.querySelector('[name="phone"]').value.trim();
			if (!codeVal || !phoneVal) {
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--error';
					msg.textContent = 'کد پیگیری و شماره همراه، هر دو لازم است.';
				}
				if (window.pixvaSetBusy) {
					window.pixvaSetBusy(button, false);
				}
				return;
			}
			if (!/^PXV-[A-Z0-9-]+$/.test(codeVal)) {
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--error';
					msg.textContent = 'قالب کد پیگیری معتبر نیست. نمونه: PXV-DEMO-2401';
				}
				if (window.pixvaSetBusy) {
					window.pixvaSetBusy(button, false);
				}
				return;
			}
			try {
				const data = await window.pixvaPostAjax('pixva_track_order', cfg.nonce.tracking, {
					code: codeVal,
					phone: phoneVal,
					pixva_hp: form.querySelector('[name="pixva_hp"]') ? form.querySelector('[name="pixva_hp"]').value : '',
				});
				if (result) {
					render(result, data);
				}
			} catch (error) {
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--error';
					msg.textContent = error.message;
				}
			} finally {
				if (window.pixvaSetBusy) {
					window.pixvaSetBusy(button, false);
				}
			}
		});
	});
}());
