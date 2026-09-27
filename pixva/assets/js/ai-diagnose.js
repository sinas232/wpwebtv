/**
 * عیب‌یاب هوشمند (AI Quick Diagnose) — لایه ۱٫۶٫۰.
 *
 * گوی نوری با انیمیشن Pulse، آپلود ویدیوی خرابی، ضبط صدای دستگاه با MediaRecorder،
 * حالت تحلیل سایبرپانک و ارسال multipart به wp-json/pixva/v1/ai-diagnose.
 *
 * @package Pixva
 * @since   1.6.0
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaAiDiagnose || {};
	var i18n = cfg.i18n || {};
	var maxMb = parseInt(cfg.maxSize, 10) > 0 ? parseInt(cfg.maxSize, 10) : 64;
	// متن مرحله‌های تحلیل از لوکالایز پوسته می‌آید (قابل ترجمه/ویرایش).
	var logs = cfg.logs && cfg.logs.length ? cfg.logs : [i18n.analyzing || ''].filter(Boolean);
	var toFa = function (value) {
		var fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
		return String(value).replace(/[0-9]/g, function (d) { return fa[parseInt(d, 10)]; });
	};

	function qs(selector, root) { return (root || document).querySelector(selector); }
	function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

	function humanSize(bytes) {
		if (bytes >= 1048576) { return toFa((Math.round((bytes / 1048576) * 10) / 10)) + ' MB'; }
		return toFa(Math.max(1, Math.round(bytes / 1024))) + ' KB';
	}

	function setState(section, state) {
		section.dataset.aiState = state;
		var orb = qs('[data-ai-orb]', section);
		if (orb) { orb.dataset.aiOrbState = state; }
	}

	function showError(section, message) {
		var box = qs('[data-ai-error]', section);
		if (!box) { return; }
		box.hidden = !message;
		box.textContent = message || '';
	}

	/* ---------------------------- گوی نوری ---------------------------- */
	function initOrb(section) {
		var orb = qs('[data-ai-orb]', section);
		if (!orb || '1' === orb.dataset.orbBound) { return; }
		orb.dataset.orbBound = '1';

		section.addEventListener('pointermove', function (event) {
			var rect = section.getBoundingClientRect();
			if (!rect.width || !rect.height) { return; }
			var x = ((event.clientX - rect.left) / rect.width) * 100;
			var y = ((event.clientY - rect.top) / rect.height) * 100;
			orb.style.setProperty('--orb-x', x.toFixed(2) + '%');
			orb.style.setProperty('--orb-y', y.toFixed(2) + '%');
		}, { passive: true });

		section.addEventListener('pointerleave', function () {
			orb.style.setProperty('--orb-x', '50%');
			orb.style.setProperty('--orb-y', '50%');
		}, { passive: true });
	}

	/* --------------------------- رسانه ورودی --------------------------- */
	function initMedia(section) {
		var fileInput = qs('[data-ai-file]', section);
		var micButton = qs('[data-ai-mic]', section);
		var mediaBox = qs('[data-ai-media]', section);
		var nameEl = qs('[data-ai-media-name]', section);
		var metaEl = qs('[data-ai-media-meta]', section);
		var iconEl = qs('[data-ai-media-icon]', section);
		var removeButton = qs('[data-ai-remove]', section);
		var accept = (section.dataset.aiAccept || 'video/*,audio/*').split(',');

		var current = null; // { file, url, kind, source }
		var recorder = null;
		var chunks = [];
		var startedAt = 0;
		var timer = null;

		function isAllowed(file) {
			if (!file) { return false; }
			var type = file.type || '';
			if (!type) { return true; } // برخی مرورگرها نوع ضبط را خالی می‌گذارند.
			return accept.some(function (rule) {
				rule = rule.trim();
				if (rule === type) { return true; }
				if (rule.slice(-2) === '/*') { return type.indexOf(rule.slice(0, -1)) === 0; }
				return false;
			});
		}

		function clear() {
			if (current && current.url) { window.URL.revokeObjectURL(current.url); }
			current = null;
			if (mediaBox) { mediaBox.hidden = true; mediaBox.replaceChildren(mediaBox.firstChild, qs('.pixva-ai__media-body', mediaBox), removeButton); }
			if (fileInput) { fileInput.value = ''; }
			section.dataset.aiHasMedia = '0';
		}

		function show(file, source) {
			if (!file) { return; }
			if (!isAllowed(file)) {
				showError(section, i18n.badType || '');
				return;
			}
			if (file.size > maxMb * 1048576) {
				showError(section, i18n.tooLarge || '');
				return;
			}
			showError(section, '');

			if (current && current.url) { window.URL.revokeObjectURL(current.url); }
			var url = window.URL.createObjectURL(file);
			var kind = (file.type || '').indexOf('audio') === 0 ? 'audio' : 'video';
			current = { file: file, url: url, kind: kind, source: source || 'file' };
			section.dataset.aiHasMedia = '1';

			if (mediaBox) {
				mediaBox.hidden = false;
				var old = qs('[data-ai-preview]', mediaBox);
				if (old) { old.remove(); }

				var preview = document.createElement(kind === 'audio' ? 'audio' : 'video');
				preview.setAttribute('data-ai-preview', '1');
				preview.src = url;
				preview.controls = true;
				preview.muted = kind === 'video';
				preview.playsInline = true;
				preview.className = 'pixva-ai__preview';
				mediaBox.appendChild(preview);

				if (nameEl) { nameEl.textContent = file.name || (kind === 'audio' ? (i18n.audioName || '') : (i18n.videoName || '')); }
				if (metaEl) { metaEl.textContent = humanSize(file.size) + ' · ' + (kind === 'audio' ? (i18n.audioKind || '') : (i18n.videoKind || '')); }
				if (iconEl) { iconEl.dataset.aiKind = kind; }
			}
		}

		if (fileInput) {
			fileInput.addEventListener('change', function () {
				var file = fileInput.files && fileInput.files[0];
				if (file) { show(file, 'file'); }
			});
		}

		if (removeButton) { removeButton.addEventListener('click', clear); }

		function stopRecording(save) {
			if (timer) { window.clearInterval(timer); timer = null; }
			if (recorder && 'inactive' !== recorder.state) { recorder.stop(); }
			if (micButton) {
				micButton.classList.remove('is-recording');
				var label = qs('span', micButton);
				if (label && label.dataset.aiMicLabel) { label.textContent = label.dataset.aiMicLabel; }
			}
			setState(section, 'idle');
			void save;
		}

		function startRecording() {
			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
				showError(section, i18n.noMic || '');
				if (micButton) { micButton.disabled = true; }
				return;
			}

			navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
				chunks = [];
				recorder = new window.MediaRecorder(stream);
				recorder.addEventListener('dataavailable', function (event) {
					if (event.data && event.data.size) { chunks.push(event.data); }
				});
				recorder.addEventListener('stop', function () {
					stream.getTracks().forEach(function (track) { track.stop(); });
					var blob = new window.Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
					var name = 'pixva-voice-' + Date.now() + '.webm';
					show(new window.File([blob], name, { type: blob.type }), 'mic');
				});
				recorder.start();
				startedAt = Date.now();
				setState(section, 'recording');

				if (micButton) {
					micButton.classList.add('is-recording');
					var label = qs('span', micButton);
					if (label) {
						if (!label.dataset.aiMicLabel) { label.dataset.aiMicLabel = label.textContent; }
						timer = window.setInterval(function () {
							var seconds = Math.round((Date.now() - startedAt) / 1000);
							label.textContent = (i18n.recording || '') + ' ' + toFa(seconds) + 's';
							if (seconds >= 60) { stopRecording(true); }
						}, 500);
					}
				}
			}).catch(function () {
				showError(section, i18n.noMic || '');
			});
		}

		if (micButton) {
			if (!window.MediaRecorder) { micButton.disabled = true; }
			micButton.addEventListener('click', function () {
				if (recorder && 'recording' === recorder.state) { stopRecording(true); } else { startRecording(); }
			});
		}

		section.pixvaAiMedia = {
			get: function () { return current ? current.file : null; },
			clear: clear,
			show: show
		};
	}

	/* ------------------------- حالت تحلیل سایبرپانک ------------------------- */
	function startLoading(section) {
		var box = qs('[data-ai-loading]', section);
		var logEl = qs('[data-ai-log]', section);
		var pctEl = qs('[data-ai-pct]', section);
		if (!box) { return function () {}; }

		box.hidden = false;
		box.setAttribute('aria-hidden', 'false');
		setState(section, 'analyzing');

		var index = 0;
		var percent = 0;
		if (logEl) { logEl.textContent = '> ' + logs[0]; }
		if (pctEl) { pctEl.textContent = toFa(0) + '٪'; }

		var tick = window.setInterval(function () {
			percent = Math.min(96, percent + Math.random() * 7 + 2);
			if (pctEl) { pctEl.textContent = toFa(Math.round(percent)) + '٪'; }
			if (percent > (index + 1) * (100 / logs.length) && index < logs.length - 1) {
				index += 1;
				if (logEl) { logEl.textContent = '> ' + logs[index]; }
			}
		}, 240);

		return function (finalPercent) {
			window.clearInterval(tick);
			if (pctEl) { pctEl.textContent = toFa(undefined === finalPercent ? 100 : finalPercent) + '٪'; }
			box.hidden = true;
			box.setAttribute('aria-hidden', 'true');
		};
	}

	/* ------------------------------ ارسال ------------------------------ */
	function submit(section, stopLoading) {
		var form = qs('[data-ai-form]', section);
		var result = qs('[data-ai-result]', section);
		var submitButton = qs('[data-ai-submit]', section);
		var media = section.pixvaAiMedia ? section.pixvaAiMedia.get() : null;

		if (!media) {
			stopLoading(0);
			setState(section, 'idle');
			showError(section, i18n.needMedia || '');
			if (submitButton) { submitButton.disabled = false; }
			return;
		}

		var body = new window.FormData(form || undefined);
		body.append('media', media, media.name || 'media.webm');
		if (!body.has('pixva_hp')) { body.append('pixva_hp', ''); }

		var endpoint = (cfg.restUrl || '/wp-json/pixva/v1') + '/ai-diagnose';

		window.fetch(endpoint, {
			method: 'POST',
			body: body,
			headers: { 'X-Pixva-Nonce': cfg.nonce || '' },
			credentials: 'same-origin'
		}).then(function (response) {
			return response.json().then(function (data) {
				return { ok: response.ok, status: response.status, data: data };
			});
		}).then(function (payload) {
			stopLoading(100);
			if (submitButton) { submitButton.disabled = false; }

			if (!payload.ok) {
				setState(section, 'error');
				showError(section, (payload.data && payload.data.message) || i18n.error || '');
				if (result) { result.hidden = true; }
				return;
			}

			var data = payload.data || {};

			// کلید تنظیم شده ولی جمینای خطا داد (HTTP 200 با state='error').
			if ('error' === data.state) {
				setState(section, 'error');
				showError(section, data.message || i18n.error || '');
				if (result) { result.hidden = true; }
				if (section.pixvaAiMedia) { section.pixvaAiMedia.clear(); }
				return;
			}

			setState(section, 'done');
			showError(section, '');

			if (result) {
				result.hidden = false;
				result.classList.add('is-open');
				var badge = qs('[data-ai-result-badge]', result);
				var title = qs('[data-ai-result-title]', result);
				var text = qs('[data-ai-result-text]', result);
				var code = qs('[data-ai-result-code]', result);
				var date = qs('[data-ai-result-date]', result);
				var cta = qs('[data-ai-result-cta]', result);
				var symptoms = qs('[data-ai-result-symptoms]', result);
				var confRow = qs('[data-ai-result-confidence-row]', result);
				var confEl = qs('[data-ai-result-confidence]', result);
				var costRow = qs('[data-ai-result-cost-row]', result);
				var costEl = qs('[data-ai-result-cost]', result);
				var timeRow = qs('[data-ai-result-time-row]', result);
				var timeEl = qs('[data-ai-result-time]', result);

				var fault = data.fault_type || data.verdict || '';
				var conf = parseInt(data.confidence, 10) || 0;
				var noKey = 'no_key' === data.state;

				if (title) { title.textContent = fault || (i18n.done || ''); }
				if (badge) { badge.textContent = (!noKey && conf > 0) ? ((i18n.confidence || '') + ' ' + toFa(conf) + '٪') : (fault ? (i18n.smart || i18n.done || '') : (i18n.done || '')); }
				if (text) {
					var noteText = '';
					if (noKey) { noteText = i18n.noKey || data.message || data.queueNote || ''; }
					else { noteText = data.technical_note || (data.analysis && data.analysis.summary) || ('string' === typeof data.analysis ? data.analysis : '') || data.message || data.queueNote || ''; }
					text.textContent = noteText;
				}

				// فهرست علائم تشخیص‌داده‌شده در ویدیو/صدا.
				if (symptoms) {
					symptoms.replaceChildren();
					var list = data.symptoms_detected || (data.analysis && data.analysis.symptoms_detected) || [];
					if (!noKey && list && list.length) {
						var head = document.createElement('li');
						head.className = 'pixva-ai__symptoms-head';
						head.textContent = i18n.symptoms || '';
						symptoms.appendChild(head);
						list.forEach(function (item) {
							var li = document.createElement('li');
							li.textContent = item;
							symptoms.appendChild(li);
						});
						symptoms.hidden = false;
					} else {
						symptoms.hidden = true;
					}
				}

				if (confRow) { confRow.hidden = noKey || conf <= 0; }
				if (confEl && !noKey && conf > 0) { confEl.textContent = toFa(conf) + '٪'; }
				if (costRow) { costRow.hidden = noKey || !data.estimated_cost_range; }
				if (costEl && !noKey && data.estimated_cost_range) { costEl.textContent = data.estimated_cost_range; }
				if (timeRow) { timeRow.hidden = noKey || !data.repair_time; }
				if (timeEl && !noKey && data.repair_time) { timeEl.textContent = data.repair_time; }

				if (code) { code.textContent = data.ticket || ''; }
				if (date) { date.textContent = data.createdAt || ''; }
				if (cta) {
					var problem = data.part || fault || '';
					if (problem) {
						var href = cta.getAttribute('href') || '';
						cta.setAttribute('href', href + (href.indexOf('?') > -1 ? '&' : '?') + 'problem=' + encodeURIComponent(problem));
					}
				}
				result.setAttribute('tabindex', '-1');
				result.focus({ preventScroll: true });
			}

			if (section.pixvaAiMedia) { section.pixvaAiMedia.clear(); }

			document.dispatchEvent(new window.CustomEvent('pixva:ai-diagnosed', { detail: data }));
		}).catch(function () {
			stopLoading(0);
			setState(section, 'error');
			if (submitButton) { submitButton.disabled = false; }
			showError(section, i18n.error || '');
		});
	}

	function initSection(section) {
		if ('1' === section.dataset.aiBound) { return; }
		section.dataset.aiBound = '1';
		section.dataset.aiState = 'idle';

		initOrb(section);
		initMedia(section);

		var form = qs('[data-ai-form]', section);
		if (!form) { return; }

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var submitButton = qs('[data-ai-submit]', section);
			if (submitButton) { submitButton.disabled = true; }
			var result = qs('[data-ai-result]', section);
			if (result) { result.hidden = true; result.classList.remove('is-open'); }
			var stop = startLoading(section);
			submit(section, stop);
		});
	}

	function scan(root) {
		qsa('[data-pixva-ai-diagnose]', root || document).forEach(initSection);
	}

	function boot() {
		document.documentElement.classList.add('pixva-ai-js');
		scan(document);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaAiDiagnoseScan = scan;
	document.addEventListener('elementor/frontend/init', function () { scan(document); });
}(window, document));
