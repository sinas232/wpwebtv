/**
 * محاسبه‌گر چندمرحله‌ای هزینه تعمیر پیکسوا
 * قیمت فقط از سرور می‌آید.
 */
(function () {
	'use strict';

	const cfg = window.pixvaTheme || { nonce: {} };

	function init(form) {
		const steps = Array.from(form.querySelectorAll('[data-step]'));
		const progress = Array.from(form.querySelectorAll('[data-progress]'));
		const errorBox = form.querySelector('[data-calc-error]');
		let current = 1;

		const field = (name) => form.querySelector(`input[name="${name}"]`);

		const showError = (message) => {
			if (!errorBox) {
				return;
			}
			errorBox.hidden = !message;
			errorBox.textContent = message || '';
		};

		const groupReady = (stepEl) => {
			const groups = new Set(Array.from(stepEl.querySelectorAll('[data-group]')).map((btn) => btn.dataset.group));
			if (!groups.size) {
				return true;
			}
			return Array.from(groups).every((group) => field(group) && field(group).value !== '');
		};

		const syncNext = () => {
			const stepEl = form.querySelector(`[data-step="${current}"]`);
			if (!stepEl) {
				return;
			}
			stepEl.querySelectorAll('[data-next], [data-estimate]').forEach((btn) => {
				btn.disabled = !groupReady(stepEl);
			});
		};

		const showStep = (index) => {
			current = index;
			steps.forEach((step) => {
				const active = Number(step.dataset.step) === index;
				step.hidden = !active;
				step.classList.toggle('is-active', active);
			});
			progress.forEach((item) => {
				const n = Number(item.dataset.progress);
				item.classList.toggle('is-current', n === index);
				item.classList.toggle('is-done', n < index);
			});
			showError('');
			syncNext();
		};

		form.querySelectorAll('.pixva-choice').forEach((button) => {
			button.addEventListener('click', () => {
				const group = button.dataset.group;
				const input = field(group);
				if (!input) {
					return;
				}
				input.value = button.dataset.value || '';
				form.querySelectorAll(`[data-group="${group}"]`).forEach((peer) => {
					peer.classList.toggle('is-selected', peer === button);
					peer.setAttribute('aria-pressed', peer === button ? 'true' : 'false');
				});
				syncNext();
			});
		});

		const brandSearch = form.querySelector('[data-brand-search]');
		if (brandSearch) {
			brandSearch.addEventListener('input', () => {
				const q = brandSearch.value.trim();
				form.querySelectorAll('[data-group="brand"]').forEach((button) => {
					const text = button.textContent || '';
					button.hidden = q !== '' && !text.includes(q);
				});
			});
		}

		form.querySelectorAll('[data-next]').forEach((button) => {
			button.addEventListener('click', () => showStep(current + 1));
		});
		form.querySelectorAll('[data-prev]').forEach((button) => {
			button.addEventListener('click', () => showStep(Math.max(1, current - 1)));
		});

		const estimateButton = form.querySelector('[data-estimate]');
		if (estimateButton) {
			estimateButton.addEventListener('click', () => requestEstimate());
		}

		async function requestEstimate() {
			showError('');
			form.classList.add('is-busy');
			if (window.pixvaSetBusy) {
				window.pixvaSetBusy(estimateButton, true);
			}
			try {
				const data = await window.pixvaPostAjax('pixva_get_estimate', cfg.nonce.calculator, {
					brand: field('brand').value,
					tech: field('tech').value,
					size: field('size').value,
					problem: field('problem').value,
					pixva_hp: form.querySelector('[name="pixva_hp"]') ? form.querySelector('[name="pixva_hp"]').value : '',
				});
				const summary = form.querySelector('[data-summary]');
				const price = form.querySelector('[data-price]');
				const days = form.querySelector('[data-days]');
				const note = form.querySelector('[data-disclaimer]');
				if (summary) {
					summary.textContent = `${data.brand} · ${data.size} · ${data.tech} · ${data.problem}`;
				}
				if (price) {
					price.textContent = `${data.minFormatted} تا ${data.maxFormatted}`;
				}
				if (days) {
					days.textContent = data.days;
				}
				if (note) {
					note.textContent = data.disclaimer;
				}
				showStep(4);
			} catch (error) {
				showError(error.message);
			} finally {
				form.classList.remove('is-busy');
				if (window.pixvaSetBusy) {
					window.pixvaSetBusy(estimateButton, false);
				}
			}
		}

		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const button = form.querySelector('[type="submit"]');
			const msg = form.querySelector('[data-order-msg]');
			showError('');
			if (msg) {
				msg.hidden = true;
			}
			if (window.pixvaSetBusy) {
				window.pixvaSetBusy(button, true);
			}
			try {
				const data = await window.pixvaPostAjax('pixva_submit_order', cfg.nonce.order, {
					brand: field('brand').value,
					tech: field('tech').value,
					size: field('size').value,
					problem: field('problem').value,
					customer_name: form.querySelector('[name="customer_name"]').value.trim(),
					phone: form.querySelector('[name="phone"]').value.trim(),
					model: form.querySelector('[name="model"]').value.trim(),
					pixva_hp: form.querySelector('[name="pixva_hp"]') ? form.querySelector('[name="pixva_hp"]').value : '',
				});
				if (msg) {
					msg.hidden = false;
					msg.className = 'pixva-notice pixva-notice--success';
					msg.textContent = data.message;
				}
				const orderBox = form.querySelector('[data-calc-order]');
				if (orderBox && data.trackUrl) {
					const link = document.createElement('a');
					link.className = 'pixva-btn pixva-btn--primary pixva-btn--sm';
					link.href = data.trackUrl;
					link.textContent = data.code;
					msg.appendChild(document.createTextNode(' '));
					msg.appendChild(link);
				}
			} catch (error) {
				showError(error.message);
			} finally {
				if (window.pixvaSetBusy) {
					window.pixvaSetBusy(button, false);
				}
			}
		});

		const preset = (group, value) => {
			if (!value) {
				return;
			}
			const button = form.querySelector(`[data-group="${group}"][data-value="${value}"]`);
			if (button) {
				button.click();
			}
		};
		preset('brand', form.dataset.presetBrand || '');
		preset('problem', form.dataset.presetProblem || '');
		showStep(1);
	}

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('[data-pixva-calc]').forEach(init);
	});
}());
