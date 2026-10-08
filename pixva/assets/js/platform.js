/**
 * ویزارد تشخیص، درخواست تعمیر و استعلام گارانتی.
 */
(function () {
	'use strict';

	const cfg = window.pixvaTheme || { ajaxUrl: '', nonce: {}, i18n: {} };

	async function postAjax(action, nonce, fields, file) {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', nonce);
		Object.keys(fields).forEach((key) => {
			body.append(key, fields[key] == null ? '' : fields[key]);
		});
		if (file) {
			body.append('photo', file);
		}
		const response = await fetch(cfg.ajaxUrl, {
			method: 'POST',
			body,
			credentials: 'same-origin',
		});
		const json = await response.json();
		if (!json || json.success !== true) {
			const message = json && json.data && json.data.message ? json.data.message : (cfg.i18n.error || 'خطا');
			throw new Error(message);
		}
		return json.data;
	}

	function stepsOf(root) {
		return Array.from(root.querySelectorAll(':scope > [data-step]'));
	}

	function showStep(root, index) {
		const steps = stepsOf(root);
		steps.forEach((step, i) => {
			step.hidden = i !== index;
		});
		const progress = root.querySelector('[data-progress]');
		if (progress) {
			progress.textContent = `${index + 1} / ${steps.length}`;
		}
		root.dataset.index = String(index);
		const next = root.querySelector('[data-next]');
		if (next) {
			next.hidden = index === steps.length - 1;
		}
	}

	function choiceState(root) {
		const state = {};
		root.querySelectorAll('[data-choice].is-on').forEach((button) => {
			state[button.dataset.key] = button.dataset.value;
		});
		return state;
	}

	function initDiagnosis(root) {
		let index = 0;
		showStep(root, 0);
		const preset = root.dataset.presetProblem;
		if (preset) {
			const button = root.querySelector(`[data-key="problem"][data-value="${preset}"]`);
			if (button) {
				button.classList.add('is-on');
			}
		}
		root.addEventListener('click', async (event) => {
			const choice = event.target.closest('[data-choice]');
			if (choice && root.contains(choice)) {
				root.querySelectorAll(`[data-choice][data-key="${choice.dataset.key}"]`).forEach((peer) => {
					peer.classList.toggle('is-on', peer === choice);
				});
			}
			if (event.target.closest('[data-next]')) {
				index = Math.min(index + 1, stepsOf(root).length - 1);
				showStep(root, index);
			}
			if (event.target.closest('[data-back]')) {
				index = Math.max(index - 1, 0);
				showStep(root, index);
			}
			const estimate = event.target.closest('[data-estimate]');
			if (!estimate) {
				return;
			}
			const state = choiceState(root);
			const model = root.querySelector('[data-model]');
			const box = root.querySelector('[data-result]');
			estimate.disabled = true;
			try {
				const data = await postAjax('pixva_get_estimate', cfg.nonce.calculator, {
					brand: state.brand || '',
					problem: state.problem || preset || '',
					size: state.size || '55',
					tech: state.tech || 'led',
				});
				const range = data.panelReplacement
					? (data.warning || data.disclaimer)
					: `${data.minFormatted} تا ${data.maxFormatted}`;
				const params = new URLSearchParams({
					brand: state.brand || '',
					problem: state.problem || preset || '',
					size: state.size || '55',
					tech: state.tech || 'led',
					model: model ? model.value : '',
				});
				box.hidden = false;
				box.innerHTML = `<p><strong>${range}</strong></p><p>${data.disclaimer || ''}</p><a class="pixva-btn pixva-btn--cta" href="${cfg.repairUrl || '/repair/'}?${params.toString()}">درخواست تعمیر</a>`;
			} catch (error) {
				box.hidden = false;
				box.innerHTML = `<p>${error.message}</p><a href="tel:${cfg.phone || ''}">تماس با متخصص</a>`;
			}
			estimate.disabled = false;
		});
	}

	function initBooking(form) {
		let index = 0;
		showStep(form, 0);
		form.addEventListener('click', (event) => {
			if (event.target.closest('[data-next]')) {
				index = Math.min(index + 1, stepsOf(form).length - 1);
				showStep(form, index);
			}
			if (event.target.closest('[data-back]')) {
				index = Math.max(index - 1, 0);
				showStep(form, index);
			}
		});
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const msg = form.querySelector('[data-book-msg]');
			const button = form.querySelector('[type="submit"]');
			const data = Object.fromEntries(new FormData(form).entries());
			if (!data.brand || !data.problem || !data.phone || String(data.customer_name || '').trim().length < 2) {
				msg.hidden = false;
				msg.className = 'pixva-notice pixva-notice--error';
				msg.textContent = 'برند، مشکل، نام و شماره همراه را کامل کنید. شماره را به‌صورت 09xxxxxxxxx بنویسید.';
				return;
			}
			button.disabled = true;
			try {
				const result = await postAjax('pixva_submit_order', cfg.nonce.order, data);
				const file = form.querySelector('[name="photo"]');
				if (file && file.files && file.files[0] && result.code) {
					await postAjax('pixva_order_photo', cfg.nonce.order, { code: result.code, phone: data.phone }, file.files[0]);
				}
				msg.hidden = false;
				msg.className = 'pixva-notice pixva-notice--success';
				msg.textContent = result.message || 'ثبت شد';
				form.querySelector('[data-next]').hidden = true;
			} catch (error) {
				msg.hidden = false;
				msg.className = 'pixva-notice pixva-notice--error';
				msg.textContent = error.message;
			}
			button.disabled = false;
		});
	}

	function initWarranty(form) {
		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			const msg = form.querySelector('[data-warranty-msg]');
			const data = Object.fromEntries(new FormData(form).entries());
			try {
				const result = await postAjax('pixva_warranty_lookup', cfg.nonce.tracking, data);
				msg.hidden = false;
				msg.className = result.valid ? 'pixva-notice pixva-notice--success' : 'pixva-notice';
				msg.textContent = `${result.message}${result.until ? ' — ' + result.until : ''}`;
			} catch (error) {
				msg.hidden = false;
				msg.className = 'pixva-notice pixva-notice--error';
				msg.textContent = error.message;
			}
		});
	}

	document.querySelectorAll('[data-px-diagnosis]').forEach(initDiagnosis);
	document.querySelectorAll('[data-px-booking]').forEach(initBooking);
	document.querySelectorAll('[data-px-warranty]').forEach(initWarranty);

	const header = document.querySelector('.pixva-header');
	if (header) {
		const onScroll = () => header.classList.toggle('is-compact', window.scrollY > 24);
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}
}());
