/**
 * موتور ۶۰ ابزار تعاملی پیکسوا (assets/js/interactive-tools.js)
 * Master Specification v25.0 — Real AI Edition
 *
 * اصول:
 * - هیچ پاسخ یا قیمت ساختگی در کلاینت تولید نمی‌شود؛ قیمت‌ها از REST/AJAX سرور
 *   و داده‌های کارگاهی از attribute های data-* که سمت سرور رندر شده‌اند می‌آید.
 * - خطای شبکه به‌صورت شفاف نمایش داده می‌شود (بدون جایگزینی با متن تصادفی).
 * - محاسبه‌های محلی (ایاب‌وذهاب، انرژی، فاصله تماشا، VA استبیلایزر) با فرمول‌های
 *   مستند و داده واقعی جدول‌های کارگاه انجام می‌شود.
 * - بدون jQuery و بدون وابستگی خارجی.
 */
(function () {
	'use strict';

	var cfg = window.pixvaVars || { ajaxUrl: '/wp-admin/admin-ajax.php', restUrl: '/wp-json/pixva/v1', nonce: '' };
	var i18n = cfg.i18n || {};
	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function toFa(value) {
		return String(value).replace(/\d/g, function (d) { return FA[Number(d)]; });
	}

	function group(value) {
		return String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '٬');
	}

	function money(value) {
		return toFa(group(value)) + ' ' + (i18n.toman || 'تومان');
	}

	function qs(sel, root) { return (root || document).querySelector(sel); }
	function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	function shellOf(node) {
		var shell = node.closest ? node.closest('[data-pixva-tool]') : null;
		return shell || document;
	}

	function setOutput(node, html, isError) {
		var out = qs('[data-tool-output]', shellOf(node));
		if (!out) { return; }
		out.hidden = false;
		out.classList.toggle('is-error', !!isError);
		out.innerHTML = '';
		if (isError) {
			out.textContent = html;
		} else {
			out.innerHTML = html;
		}
	}

	function setBusy(button, busy, label) {
		if (!button) { return; }
		button.disabled = !!busy;
		button.classList.toggle('is-loading', !!busy);
		if (busy) {
			button.dataset.prevLabel = button.textContent;
			button.textContent = label || i18n.loading || 'در حال پردازش…';
		} else if (button.dataset.prevLabel) {
			button.textContent = button.dataset.prevLabel;
		}
	}

	function nonceFor(action) {
		var map = {
			pixva_track_device: 'tracking',
			pixva_warranty_card: 'tracking',
			pixva_client_hub: 'tracking',
			pixva_dispatch_request: 'order',
			pixva_booking_request: 'order',
			pixva_quick_quote: 'order',
			pixva_get_estimate: 'calculator',
			pixva_contact_form: 'contact'
		};
		var key = map[action] || 'tool';
		return (cfg.nonces && cfg.nonces[key]) || cfg.nonce || '';
	}

	function post(action, nonce, payload) {
		var body = new FormData();
		Object.keys(payload || {}).forEach(function (key) {
			var value = payload[key];
			if (value === null || typeof value === 'undefined') { return; }
			if (Array.isArray(value)) { value.forEach(function (item) { body.append(key + '[]', item); }); } else { body.append(key, value); }
		});
		body.append('action', action);
		body.append('nonce', nonce || nonceFor(action) || '');
		return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || json.success !== true) {
					throw new Error(json && json.data && json.data.message ? json.data.message : (i18n.error || 'خطا در پردازش درخواست.'));
				}
				return json.data;
			});
	}

	function postForm(action, nonce, form, extra) {
		var body = new FormData(form);
		body.append('action', action);
		body.append('nonce', nonce || nonceFor(action) || '');
		Object.keys(extra || {}).forEach(function (key) { body.append(key, extra[key]); });
		return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || json.success !== true) {
					throw new Error(json && json.data && json.data.message ? json.data.message : (i18n.error || 'خطا در پردازش درخواست.'));
				}
				return json.data;
			});
	}

	function rest(path, params) {
		var url = (cfg.restUrl || '/wp-json/pixva/v1') + path;
		if (params) {
			var query = Object.keys(params)
				.filter(function (key) { return params[key] !== '' && params[key] !== null && typeof params[key] !== 'undefined'; })
				.map(function (key) { return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]); })
				.join('&');
			if (query) { url += (url.indexOf('?') === -1 ? '?' : '&') + query; }
		}
		return fetch(url, { credentials: 'same-origin' }).then(function (res) {
			return res.json().then(function (json) {
				if (!res.ok) { throw new Error(json && json.message ? json.message : 'HTTP ' + res.status); }
				return json;
			});
		});
	}

	function fieldValue(root, name) {
		var field = qs('[name="' + name + '"]', root);
		return field ? field.value : '';
	}

	function reduceMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	/* ==================================================================
	   ۱) دستیار هوش مصنوعی (ابزار ۱ تا ۴ و ویجت شناور)
	   ================================================================== */
	function renderAIPayload(payload, result) {
		if (!result) { return; }
		result.hidden = false;
		var res = payload.result || {};
		var source = qs('[data-ai-source]', result);
		var urgency = qs('[data-ai-urgency]', result);
		var reply = qs('[data-ai-reply]', result);
		var facts = qs('[data-ai-facts]', result);
		var notice = qs('[data-ai-notice]', result);
		var quote = qs('[data-ai-quote]', result);

		if (source) {
			source.textContent = payload.source === 'gemini'
				? 'تحلیل واقعی Gemini' + (payload.model ? ' · ' + payload.model : '') + (payload.images ? ' · ' + toFa(payload.images) + ' تصویر' : '')
				: 'موتور قانون‌محور کارگاه (کلید Gemini تنظیم نشده یا خطا داشت)';
		}
		if (urgency) {
			urgency.hidden = !payload.urgencyLabel;
			urgency.textContent = payload.urgencyLabel || '';
		}
		if (reply) { reply.textContent = payload.reply || ''; }
		if (facts) {
			facts.innerHTML = '';
			[
				['عیب‌یابی', res.diagnosis],
				['قطعه درگیر', res.part],
				['اقدام فوری', res.action],
				['هشدار ایمنی', res.safety]
			].forEach(function (row) {
				if (!row[1]) { return; }
				var li = document.createElement('li');
				var strong = document.createElement('strong');
				strong.textContent = row[0] + ': ';
				li.appendChild(strong);
				li.appendChild(document.createTextNode(row[1]));
				facts.appendChild(li);
			});
			if (payload.estimate && payload.estimate.text) {
				var li2 = document.createElement('li');
				var strong2 = document.createElement('strong');
				strong2.textContent = 'برآورد نرخ‌نامه: ';
				li2.appendChild(strong2);
				li2.appendChild(document.createTextNode(payload.estimate.text));
				facts.appendChild(li2);
			}
		}
		if (notice) {
			notice.hidden = !payload.notice;
			notice.textContent = payload.notice || '';
		}
		if (quote) {
			if (res.service) {
				quote.href = quote.href.split('?')[0] + '?problem=' + encodeURIComponent(res.service);
				quote.hidden = false;
			} else {
				quote.hidden = true;
			}
		}
	}

	function showAIError(result, error) {
		if (!result) { return; }
		result.hidden = false;
		var reply = qs('[data-ai-reply]', result);
		var notice = qs('[data-ai-notice]', result);
		if (reply) { reply.textContent = ''; }
		if (notice) { notice.hidden = false; notice.textContent = error.message; }
	}

	function initAIStudio() {
		qsa('[data-pixva-ai]').forEach(function (studio) {
			var form = qs('[data-ai-form]', studio);
			var result = qs('[data-ai-result]', studio);
			var mode = studio.getAttribute('data-ai-mode') || 'text';
			if (!form) { return; }

			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var input = qs('[data-ai-input]', form);
				if (input && input.value.trim().length < 4 && !qs('input[type="file"]', form).files.length) {
					input.focus();
					return;
				}
				var button = qs('[data-ai-submit]', form);
				setBusy(button, true, 'در حال تحلیل…');
				postForm('pixva_ai_diagnose', cfg.nonce, form)
					.then(function (payload) { setBusy(button, false); renderAIPayload(payload, result); })
					.catch(function (error) { setBusy(button, false); showAIError(result, error); });
			});

			var copyBtn = qs('[data-ai-copy]', studio);
			if (copyBtn) {
				copyBtn.addEventListener('click', function () {
					var reply = qs('[data-ai-reply]', studio);
					var lines = qsa('[data-ai-facts] li', studio).map(function (li) { return li.textContent; });
					var text = (reply ? reply.textContent : '') + (lines.length ? '\n' + lines.join('\n') : '');
					if (navigator.clipboard && text) {
						navigator.clipboard.writeText(text).then(function () {
							copyBtn.textContent = 'کپی شد';
							window.setTimeout(function () { copyBtn.textContent = 'کپی پاسخ'; }, 1600);
						});
					}
				});
			}

			var voiceBtn = qs('[data-ai-voice]', studio);
			if (voiceBtn && 'voice' === mode) {
				initVoiceInput(voiceBtn, qs('[data-ai-input]', studio), qs('[data-ai-voice-hint]', studio));
			}
		});
	}

	function initVoiceInput(button, target, hint) {
		var Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
		if (!Recognition) {
			button.disabled = true;
			if (hint) { hint.textContent = 'مرورگر شما از Web Speech API پشتیبانی نمی‌کند؛ متن را دستی وارد کنید.'; }
			return;
		}
		var recognition = new Recognition();
		recognition.lang = 'fa-IR';
		recognition.continuous = false;
		recognition.interimResults = true;
		var listening = false;

		button.addEventListener('click', function () {
			if (listening) { recognition.stop(); return; }
			try { recognition.start(); } catch (error) { /* already started */ }
		});
		recognition.onstart = function () {
			listening = true;
			button.classList.add('is-loading');
			if (hint) { hint.textContent = 'در حال شنیدن… برای توقف دوباره کلیک کنید.'; }
		};
		recognition.onresult = function (event) {
			var text = '';
			for (var i = 0; i < event.results.length; i += 1) { text += event.results[i][0].transcript; }
			if (target) { target.value = text; }
		};
		recognition.onerror = function (event) {
			if (hint) { hint.textContent = 'خطای تشخیص گفتار: ' + event.error + '. دسترسی میکروفون را بررسی کنید.'; }
		};
		recognition.onend = function () {
			listening = false;
			button.classList.remove('is-loading');
			if (hint) { hint.textContent = 'گفتار به متن انجام شد؛ در صورت نیاز متن را ویرایش کنید.'; }
		};
	}

	function initAIFloating() {
		var widget = qs('[data-pixva-ai-bot]');
		if (!widget) { return; }
		var toggle = qs('[data-ai-toggle]', widget);
		var panel = qs('[data-ai-panel]', widget);
		var closeBtn = qs('[data-ai-close]', widget);
		var messages = qs('[data-ai-messages]', widget);
		var form = qs('[data-ai-chat-form]', widget);
		var input = qs('[data-ai-chat-input]', widget);

		function open(show) {
			if (!panel) { return; }
			panel.hidden = !show;
			if (toggle) { toggle.setAttribute('aria-expanded', show ? 'true' : 'false'); }
			if (show && input) { input.focus(); }
		}

		if (toggle) { toggle.addEventListener('click', function () { open(panel.hidden); }); }
		if (closeBtn) { closeBtn.addEventListener('click', function () { open(false); }); }
		document.addEventListener('keydown', function (event) {
			if ('Escape' === event.key && panel && !panel.hidden) { open(false); }
		});

		function append(text, sender, isError) {
			if (!messages || !text) { return null; }
			var wrap = document.createElement('div');
			wrap.className = 'pixva-ai-msg pixva-ai-msg--' + sender + (isError ? ' pixva-ai-msg--error' : '');
			var p = document.createElement('p');
			p.textContent = text;
			wrap.appendChild(p);
			messages.appendChild(wrap);
			messages.scrollTop = messages.scrollHeight;
			return wrap;
		}

		function typing() {
			if (!messages) { return null; }
			var wrap = document.createElement('div');
			wrap.className = 'pixva-ai-msg pixva-ai-msg--bot';
			var dots = document.createElement('span');
			dots.className = 'pixva-ai-typing';
			for (var i = 0; i < 3; i += 1) { dots.appendChild(document.createElement('i')); }
			wrap.appendChild(dots);
			messages.appendChild(wrap);
			messages.scrollTop = messages.scrollHeight;
			return wrap;
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var message = input ? input.value.trim() : '';
				var fileInput = qs('[data-ai-chat-file]', form);
				var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
				if (message.length < 4 && !hasFile) { if (input) { input.focus(); } return; }
				append(message, 'user');
				if (input) { input.value = ''; }
				var loader = typing();

				postForm('pixva_ai_diagnose', cfg.nonce, form).then(function (payload) {
					if (loader) { loader.remove(); }
					append(payload.reply, 'bot');
					if (payload.notice) { append(payload.notice, 'bot', true); }
					if (payload.estimate && payload.estimate.text) { append(payload.estimate.text, 'bot'); }
					if (fileInput) { fileInput.value = ''; }
				}).catch(function (error) {
					if (loader) { loader.remove(); }
					append(error.message, 'bot', true);
				});
			});
		}
	}

	/* ==================================================================
	   ۲) شبیه‌ساز لمسی تلویزیون (ابزار ۵)
	   ================================================================== */
	function initTVSimulator() {
		qsa('[data-tv-simulator]').forEach(function (root) {
			var parts = {};
			try { parts = JSON.parse(root.getAttribute('data-simulator-data') || '{}'); } catch (error) { parts = {}; }

			var tag = qs('[data-sim-tag]', root);
			var title = qs('[data-sim-title]', root);
			var desc = qs('[data-sim-desc]', root);
			var priceBox = qs('[data-sim-price-box]', root);
			var price = qs('[data-sim-price]', root);
			var days = qs('[data-sim-days]', root);
			var screen = qs('[data-sim-screen-msg]', root);
			var quote = qs('[data-sim-quote]', root);

			qsa('[data-part]', root).forEach(function (spot) {
				spot.addEventListener('click', function () {
					var info = parts[spot.getAttribute('data-part')];
					if (!info) { return; }
					qsa('[data-part]', root).forEach(function (other) { other.classList.remove('is-active'); });
					spot.classList.add('is-active');
					if (tag) { tag.textContent = info.title; }
					if (title) { title.textContent = info.title; }
					if (desc) { desc.textContent = info.desc; }
					if (price) { price.textContent = money(info.floor) + ' تا ' + toFa(group(info.max)) + ' تومان'; }
					if (days && info.days) { days.textContent = info.days; }
					if (priceBox) { priceBox.hidden = false; }
					if (screen) {
						screen.innerHTML = '';
						var p = document.createElement('p');
						p.textContent = info.title;
						screen.appendChild(p);
					}
					if (quote && info.service) {
						quote.href = quote.href.split('?')[0] + '?problem=' + encodeURIComponent(info.service);
						quote.hidden = false;
					}
				});
			});
		});
	}

	/* ==================================================================
	   ۳) تستر RGB و احیای OLED (ابزار ۶ و ۷)
	   ================================================================== */
	function initRGBTester() {
		qsa('[data-rgb-tester]').forEach(function (tester) {
			var canvas = qs('[data-rgb-canvas]', tester);
			var timer = null;

			function stopCycle() { if (timer) { window.clearInterval(timer); timer = null; } }

			qsa('[data-color]', tester).forEach(function (button) {
				button.addEventListener('click', function () {
					stopCycle();
					if (!canvas) { return; }
					canvas.style.background = button.getAttribute('data-color');
					canvas.innerHTML = '';
					var span = document.createElement('span');
					span.className = 'pixva-rgb-tag';
					span.textContent = button.textContent;
					canvas.appendChild(span);
				});
			});

			var full = qs('[data-fullscreen]', tester);
			if (full) {
				full.addEventListener('click', function () {
					if (!canvas) { return; }
					if (document.fullscreenElement) {
						document.exitFullscreen();
					} else if (canvas.requestFullscreen) {
						canvas.requestFullscreen();
					}
				});
			}

			var oled = qs('[data-oled-cleaner]', tester);
			if (oled) {
				oled.addEventListener('click', function () {
					if (!canvas) { return; }
					if (timer) {
						stopCycle();
						canvas.style.background = '#0E2C38';
						canvas.innerHTML = '<span class="pixva-rgb-tag">چرخه احیا متوقف شد.</span>';
						return;
					}
					var cycle = ['#FF0000', '#00FF00', '#0000FF', '#FFFFFF', '#808080', '#000000'];
					var index = 0;
					canvas.innerHTML = '<span class="pixva-rgb-tag">چرخه احیای OLED در حال اجرا — حداکثر ۱۰ دقیقه</span>';
					timer = window.setInterval(function () {
						canvas.style.background = cycle[index % cycle.length];
						index += 1;
						if (index >= cycle.length * 40) { stopCycle(); }
					}, reduceMotion() ? 900 : 450);
				});
			}
		});
	}

	/* ==================================================================
	   ۴) تستر چشمک چراغ استندبای (ابزار ۸)
	   ================================================================== */
	function initBlinkTester() {
		qsa('[data-blink-tester]').forEach(function (root) {
			var shell = shellOf(root);
			var led = qs('[data-blink-led]', shell);
			var label = qs('[data-blink-label]', shell);
			var matches = qs('[data-blink-matches]', shell);
			var run = qs('[data-blink-run]', root);
			var timer = null;

			if (!run) { return; }
			run.addEventListener('click', function () {
				var brand = fieldValue(root, 'brand');
				var count = parseInt(fieldValue(root, 'blinks'), 10) || 0;
				var speed = parseInt(fieldValue(root, 'speed'), 10) || 600;

				if (timer) { window.clearInterval(timer); timer = null; }
				if (led) { led.classList.remove('is-on'); }
				if (count < 1 || count > 14) {
					if (label) { label.textContent = 'تعداد چشمک باید بین ۱ تا ۱۴ باشد.'; }
					return;
				}

				var step = 0;
				var tick = window.setInterval(function () {
					step += 1;
					if (step > count * 2) { step = 0; }
					if (led) { led.classList.toggle('is-on', step % 2 === 1); }
				}, Math.max(120, speed / 2));
				timer = tick;
				if (label) { label.textContent = 'در حال پخش الگوی ' + toFa(count) + ' چشمک…'; }

				if (matches) {
					matches.hidden = false;
					var found = 0;
					qsa('[data-blink-match]', matches).forEach(function (item) {
						var sameBlink = item.getAttribute('data-blink-match') === String(count);
						var sameBrand = !brand || item.getAttribute('data-match-brand') === brand;
						var show = sameBlink && sameBrand;
						item.hidden = !show;
						if (show) { found += 1; }
					});
					var empty = qs('[data-blink-empty]', matches);
					if (!empty) {
						empty = document.createElement('p');
						empty.className = 'pixva-notice pixva-notice--info';
						empty.setAttribute('data-blink-empty', '');
						matches.appendChild(empty);
					}
					empty.hidden = found > 0;
					empty.textContent = 'برای این الگو رکوردی در پایگاه کارگاه ثبت نشده است. تعداد چشمک و برند را برای کارشناس ارسال کنید.';
				}
			});
		});
	}

	/* ==================================================================
	   ۵) تستر صدا (ابزار ۱۰) و تحلیل‌گر نویز (ابزار ۱۸)
	   ================================================================== */
	var audioContext = null;
	var oscillator = null;
	var gainNode = null;

	function ensureAudio() {
		if (!audioContext) {
			var Ctx = window.AudioContext || window.webkitAudioContext;
			if (!Ctx) { return null; }
			audioContext = new Ctx();
		}
		if ('suspended' === audioContext.state) { audioContext.resume(); }
		return audioContext;
	}

	function stopTone() {
		if (oscillator) {
			try { oscillator.stop(); } catch (error) { /* already stopped */ }
			oscillator.disconnect();
			oscillator = null;
		}
		if (gainNode) { gainNode.disconnect(); gainNode = null; }
	}

	function startTone(freq, volume, type) {
		var ctx = ensureAudio();
		if (!ctx) { return false; }
		stopTone();
		oscillator = ctx.createOscillator();
		gainNode = ctx.createGain();
		oscillator.type = type || 'sine';
		oscillator.frequency.value = freq;
		gainNode.gain.value = Math.max(0, Math.min(100, volume || 45)) / 100 * 0.3;
		oscillator.connect(gainNode);
		gainNode.connect(ctx.destination);
		oscillator.start();
		return true;
	}

	function initAudioTester() {
		qsa('[data-audio-tester]').forEach(function (root) {
			var play = qs('[data-audio-play]', root);
			var stop = qs('[data-audio-stop]', root);
			var sweep = qs('[data-audio-sweep]', root);

			if (play) {
				play.addEventListener('click', function () {
					var freq = parseFloat(fieldValue(root, 'frequency')) || 440;
					var vol = parseFloat(fieldValue(root, 'volume'));
					if (startTone(freq, isNaN(vol) ? 45 : vol)) {
						setOutput(root, '<strong>سیگنال ' + toFa(group(freq)) + ' هرتز پخش می‌شود.</strong> برای یافتن اعوجاج، دامنه را کم و زیاد کنید و در همان فرکانسی که صدا خشن می‌شود یادداشت بردارید.');
					} else {
						setOutput(root, 'مرورگر شما از Web Audio API پشتیبانی نمی‌کند.', true);
					}
				});
			}

			if (sweep) {
				sweep.addEventListener('click', function () {
					var ctx = ensureAudio();
					if (!ctx) { setOutput(root, 'مرورگر شما از Web Audio API پشتیبانی نمی‌کند.', true); return; }
					if (!startTone(100, parseFloat(fieldValue(root, 'volume')) || 40)) { return; }
					var now = ctx.currentTime;
					oscillator.frequency.setValueAtTime(100, now);
					oscillator.frequency.exponentialRampToValueAtTime(8000, now + 12);
					setOutput(root, '<strong>جاروب فرکانسی ۱۰۰ تا ۸۰۰۰ هرتز</strong> به مدت ۱۲ ثانیه پخش می‌شود. در بازه‌ای که صدا قطع یا خشن می‌شود، همان طبقه آمپلی‌فایر یا بلندگو مشکل دارد.');
					window.setTimeout(stopTone, 12200);
				});
			}

			if (stop) {
				stop.addEventListener('click', function () {
					stopTone();
					setOutput(root, 'پخش سیگنال متوقف شد.');
				});
			}
		});
	}

	function initAudioAnalyzer() {
		qsa('[data-audio-analyzer]').forEach(function (root) {
			var startBtn = qs('[data-analyzer-start]', root);
			var stopBtn = qs('[data-analyzer-stop]', root);
			var canvas = qs('[data-analyzer-canvas]', root);
			var peak = qs('[data-analyzer-peak]', root);
			var level = qs('[data-analyzer-level]', root);
			var note = qs('[data-analyzer-note]', root);
			var stream = null;
			var raf = null;
			var analyser = null;

			function interpret(freq) {
				if (freq < 120) { return 'نویز فرکانس پایین: احتمال وزوز ترانس یا خازن برد تغذیه.'; }
				if (freq < 400) { return 'نویز میانه: احتمال لرزش بلندگو، پایه یا فن خنک‌کننده.'; }
				if (freq < 2000) { return 'باند صوتی: احتمال اعوجاج طبقه آمپلی‌فایر یا فلت اسپیکر.'; }
				if (freq < 6000) { return 'نویز بالا: احتمال سوت کوئل‌های برد پاور (Coil Whine).'; }
				return 'بسیار بالا: تداخل EMI یا خرابی فیلتر ورودی برق.';
			}

			function draw() {
				if (!analyser || !canvas) { return; }
				var ctx = canvas.getContext('2d');
				var buffer = new Uint8Array(analyser.frequencyBinCount);
				analyser.getByteFrequencyData(buffer);
				var width = canvas.width;
				var height = canvas.height;
				ctx.clearRect(0, 0, width, height);
				var bars = 64;
				var step = Math.floor(buffer.length / bars);
				var maxValue = 0;
				var maxIndex = 0;
				var sum = 0;
				for (var i = 0; i < bars; i += 1) {
					var value = 0;
					for (var j = 0; j < step; j += 1) { value += buffer[(i * step) + j]; }
					value = Math.round(value / step);
					sum += value;
					if (value > maxValue) { maxValue = value; maxIndex = i; }
					var barHeight = (value / 255) * height;
					ctx.fillStyle = value > 200 ? 'rgba(194, 73, 61, 0.9)' : 'rgba(23, 107, 135, 0.85)';
					ctx.fillRect(i * (width / bars) + 1, height - barHeight, (width / bars) - 2, barHeight);
				}
				var nyquist = audioContext ? audioContext.sampleRate / 2 : 22050;
				var dominant = Math.round(maxIndex * step * (nyquist / buffer.length));
				if (peak) { peak.textContent = dominant > 0 ? toFa(group(dominant)) + ' هرتز' : '—'; }
				if (level) { level.textContent = toFa(Math.round(sum / bars)) + ' از ۲۵۵'; }
				if (note) { note.textContent = maxValue > 12 ? interpret(dominant) : 'نویز محسوسی ثبت نشد؛ میکروفون را به بدنه نزدیک‌تر کنید.'; }
				raf = window.requestAnimationFrame(draw);
			}

			if (startBtn) {
				startBtn.addEventListener('click', function () {
					if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
						setOutput(root, 'مرورگر شما دسترسی به میکروفون را پشتیبانی نمی‌کند.', true);
						return;
					}
					navigator.mediaDevices.getUserMedia({ audio: true }).then(function (media) {
						stream = media;
						var ctx = ensureAudio();
						if (!ctx) { return; }
						var source = ctx.createMediaStreamSource(stream);
						analyser = ctx.createAnalyser();
						analyser.fftSize = 1024;
						analyser.smoothingTimeConstant = 0.75;
						source.connect(analyser);
						draw();
						setOutput(root, 'تحلیل زنده نویز شروع شد. میکروفون را ۱۰ تا ۲۰ سانتی‌متر از بدنه تلویزیون نگه دارید.');
					}).catch(function (error) {
						setOutput(root, 'دسترسی به میکروفون داده نشد: ' + error.name, true);
					});
				});
			}

			if (stopBtn) {
				stopBtn.addEventListener('click', function () {
					if (raf) { window.cancelAnimationFrame(raf); raf = null; }
					if (stream) { stream.getTracks().forEach(function (track) { track.stop(); }); stream = null; }
					analyser = null;
					if (peak) { peak.textContent = '—'; }
					if (level) { level.textContent = '—'; }
					if (note) { note.textContent = '—'; }
					setOutput(root, 'تحلیل متوقف شد.');
				});
			}
		});
	}

	/* ==================================================================
	   ۶) ابزارهای نرخ‌نامه: تعمیر یا خرید، اسلایدر سایز، تفکیک هزینه
	   ================================================================== */
	function breakdownHTML(breakdown, bound) {
		if (!breakdown || !breakdown.values || !breakdown.values[bound]) { return ''; }
		var values = breakdown.values[bound];
		var labels = breakdown.labels;
		var html = '<ul class="pixva-breakdown__list">';
		['part', 'labor', 'warranty', 'diagnostic'].forEach(function (key) {
			html += '<li><span>' + labels[key] + '</span><b>' + money(values[key]) + '</b></li>';
		});
		html += '</ul>';
		return html;
	}

	function initRepairVsBuy() {
		qsa('[data-repair-vs-buy]').forEach(function (root) {
			var run = qs('[data-rvb-run]', root);
			var box = qs('[data-rvb-breakdown]', shellOf(root));
			if (!run || !box) { return; }

			run.addEventListener('click', function () {
				var params = {
					brand: fieldValue(root, 'brand'),
					tech: 'led',
					size: fieldValue(root, 'size'),
					problem: fieldValue(root, 'problem')
				};
				var age = parseFloat(fieldValue(root, 'age')) || 0;
				var newPrice = parseFloat(fieldValue(root, 'new_price')) || 0;
				setBusy(run, true);
				rest('/pricing', params).then(function (data) {
					setBusy(run, false);
					box.hidden = false;
					var repairMid = Math.round((data.price.min + data.price.max) / 2);
					var ratio = newPrice > 0 ? (repairMid / newPrice) * 100 : 0;
					var verdict;
					if (newPrice <= 0) {
						verdict = 'قیمت دستگاه نو را وارد کنید تا نسبت اقتصادی محاسبه شود.';
					} else if (data.price.panel_replacement) {
						verdict = 'پنل این دستگاه شکسته یا تعویضی است؛ طبق قاعده کارگاه تعمیر توجیه اقتصادی ندارد و تعویض دستگاه پیشنهاد می‌شود.';
					} else if (ratio > 55 && age > 8) {
						verdict = 'هزینه تعمیر ' + toFa(Math.round(ratio)) + '٪ قیمت دستگاه نو و سن دستگاه ' + toFa(age) + ' سال است؛ تعویض منطقی‌تر است.';
					} else if (ratio > 55) {
						verdict = 'هزینه تعمیر ' + toFa(Math.round(ratio)) + '٪ قیمت دستگاه نو است؛ اگر پنل سالم است و قصد نگهداری بلندمدت دارید، تعمیر با گارانتی ۱۸۰ روزه همچنان به‌صرفه است.';
					} else {
						verdict = 'هزینه تعمیر ' + toFa(Math.round(ratio)) + '٪ قیمت دستگاه نو است؛ تعمیر با قطعه فابریک و گارانتی کتبی ۱۸۰ روزه توصیه می‌شود.';
					}
					box.innerHTML =
						'<div class="pixva-breakdown__head"><strong>برآورد تعمیر</strong><span>' + money(data.price.min) + ' تا ' + toFa(group(data.price.max)) + ' تومان</span></div>' +
						'<div class="pixva-breakdown__head"><strong>قیمت دستگاه نو</strong><span>' + (newPrice > 0 ? money(newPrice) : '—') + '</span></div>' +
						'<div class="pixva-breakdown__head"><strong>نسبت هزینه تعمیر به دستگاه نو</strong><span>' + (ratio > 0 ? toFa(Math.round(ratio)) + '٪' : '—') + '</span></div>' +
						'<div class="pixva-breakdown__head"><strong>زمان تعمیر</strong><span>' + toFa(data.price.days || '—') + '</span></div>' +
						'<p class="pixva-notice pixva-notice--info">' + verdict + '</p>';
				}).catch(function (error) {
					setBusy(run, false);
					box.hidden = false;
					box.innerHTML = '<p class="pixva-notice pixva-notice--danger">' + error.message + '</p>';
				});
			});
		});
	}

	function initSizeSlider() {
		qsa('[data-size-slider]').forEach(function (root) {
			var range = qs('[data-size-range]', root);
			var value = qs('[data-size-value]', root);
			var factor = qs('[data-size-factor]', root);
			var bar = qs('[data-size-bar]', root);
			var output = qs('[data-tool-output]', shellOf(root));
			if (!range) { return; }
			var pending = null;

			function update(live) {
				var size = parseInt(range.value, 10);
				if (value) { value.textContent = toFa(size); }
				if (factor) {
					var sf = size <= 32 ? 1 : 1 + Math.pow((size - 32) / 32, 1.35);
					factor.textContent = toFa(sf.toFixed(3));
				}
				if (bar) { bar.style.width = (((size - 32) / (98 - 32)) * 100) + '%'; }
				if (!live) { return; }
				if (pending) { window.clearTimeout(pending); }
				pending = window.setTimeout(function () {
					rest('/pricing', {
						brand: fieldValue(root, 'brand'),
						tech: fieldValue(root, 'tech') || 'led',
						size: String(size),
						problem: fieldValue(root, 'problem')
					}).then(function (data) {
						setOutput(root,
							'<strong>برآورد ' + toFa(size) + ' اینچ:</strong> ' + money(data.price.min) + ' تا ' + toFa(group(data.price.max)) + ' تومان' +
							(data.price.days ? ' · زمان تعمیر: ' + toFa(data.price.days) : '') +
							(data.price.warning ? '<span class="pixva-notice pixva-notice--warning">' + data.price.warning + '</span>' : ''));
					}).catch(function (error) { setOutput(root, error.message, true); });
				}, 320);
			}

			range.addEventListener('input', function () { update(true); });
			['problem', 'brand', 'tech'].forEach(function (name) {
				var field = qs('[name="' + name + '"]', root);
				if (field) { field.addEventListener('change', function () { update(true); }); }
			});
			update(false);
		});
	}

	function initCostBreakdown() {
		qsa('[data-cost-breakdown]').forEach(function (root) {
			var run = qs('[data-breakdown-run]', root);
			var box = qs('[data-breakdown-rows]', shellOf(root));
			if (!run || !box) { return; }

			function load() {
				setBusy(run, true);
				rest('/pricing', {
					brand: fieldValue(root, 'brand'),
					tech: fieldValue(root, 'tech') || 'led',
					size: fieldValue(root, 'size'),
					problem: fieldValue(root, 'problem')
				}).then(function (data) {
					setBusy(run, false);
					box.hidden = false;
					var html = '<div class="pixva-breakdown__head"><strong>بازه برآورد</strong><span>' + money(data.price.min) + ' تا ' + toFa(group(data.price.max)) + ' تومان</span></div>';
					html += '<h4>حداقل برآورد</h4>' + breakdownHTML(data.breakdown, 'min');
					html += '<h4>حداکثر برآورد</h4>' + breakdownHTML(data.breakdown, 'max');
					box.innerHTML = html;
				}).catch(function (error) {
					setBusy(run, false);
					box.hidden = false;
					box.innerHTML = '<p class="pixva-notice pixva-notice--danger">' + error.message + '</p>';
				});
			}

			run.addEventListener('click', load);
			qsa('select', root).forEach(function (field) { field.addEventListener('change', load); });
			load();
		});
	}

	/* ==================================================================
	   ۷) انبار قطعات، تایم‌لاین، کارت گارانتی، گزارش (ابزار ۱۴/۱۵/۱۶/۶۰)
	   ================================================================== */
	function initStockChecker() {
		qsa('[data-stock-checker]').forEach(function (root) {
			var run = qs('[data-stock-run]', root);
			var list = qs('[data-stock-list]', shellOf(root));
			if (!run || !list) { return; }

			function load() {
				setBusy(run, true);
				rest('/stock', { q: fieldValue(root, 'q'), brand: fieldValue(root, 'brand') }).then(function (data) {
					setBusy(run, false);
					list.hidden = false;
					if (!data.items || !data.items.length) {
						list.innerHTML = '<p class="pixva-notice pixva-notice--info">' + data.message + '</p>';
						return;
					}
					var html = '<table class="pixva-table"><thead><tr><th>قطعه</th><th>کد فنی</th><th>موجودی</th><th>قیمت (تومان)</th><th>گارانتی</th></tr></thead><tbody>';
					data.items.forEach(function (item) {
						html += '<tr><td>' + item.name + '</td><td>' + item.sku + '</td><td>' + toFa(item.qty) + '</td><td>' + toFa(group(item.price)) + '</td><td>' + toFa(item.warranty) + ' روز</td></tr>';
					});
					list.innerHTML = html + '</tbody></table><p class="pixva-tool-note">' + data.message + '</p>';
				}).catch(function (error) {
					setBusy(run, false);
					list.hidden = false;
					list.innerHTML = '<p class="pixva-notice pixva-notice--danger">' + error.message + '</p>';
				});
			}

			run.addEventListener('click', load);
			qsa('input,select', root).forEach(function (field) {
				field.addEventListener('keydown', function (event) { if ('Enter' === event.key) { event.preventDefault(); load(); } });
			});
		});
	}

	function timelineHTML(order) {
		var stages = order.timeline || [];
		var html = '';
		stages.forEach(function (stage) {
			html += '<li class="pixva-timeline__step is-' + stage.state + '">' +
				'<span class="pixva-timeline__dot"></span>' +
				'<span><strong>' + stage.label + '</strong>' +
				(stage.date ? '<em>' + stage.date + '</em>' : '') +
				'</span></li>';
		});
		return html;
	}

	function initTimeline() {
		qsa('[data-timeline-form]').forEach(function (form) {
			var shell = shellOf(form);
			var list = qs('[data-timeline]', shell);
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_track_device', nonceFor('pixva_track_device'), form).then(function (data) {
					setBusy(button, false);
					setOutput(form, '<strong>وضعیت پرونده ' + data.code + ':</strong> ' + data.statusLabel +
						(data.updated ? ' · آخرین به‌روزرسانی: ' + data.updated : ''));
					if (list) {
						list.hidden = false;
						list.innerHTML = timelineHTML(data);
					}
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
					if (list) { list.hidden = true; }
				});
			});
		});
	}

	function initWarrantyCard() {
		qsa('[data-warranty-form]').forEach(function (form) {
			var shell = shellOf(form);
			var card = qs('[data-warranty-card]', shell);
			var print = qs('[data-warranty-print]', shell);
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_warranty_card', nonceFor('pixva_warranty_card'), form).then(function (data) {
					setBusy(button, false);
					var cardData = data.card;
					qs('[data-warranty-code]', card).textContent = cardData.code;
					qs('[data-warranty-device]', card).textContent = cardData.device;
					qs('[data-warranty-service]', card).textContent = cardData.service;
					qs('[data-warranty-delivered]', card).textContent = cardData.delivered;
					qs('[data-warranty-expires]', card).textContent = cardData.expires;
					qs('[data-warranty-hash]', card).textContent = cardData.hash;
					card.hidden = false;
					setOutput(form, data.message);
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
					if (card) { card.hidden = true; }
				});
			});
			if (print) { print.addEventListener('click', function () { window.print(); }); }
		});
	}

	function initReportExport() {
		qsa('[data-report-form]').forEach(function (form) {
			var shell = shellOf(form);
			var report = qs('[data-report]', shell);
			var print = qs('[data-report-print]', shell);
			var button = qs('button[type="submit"]', form);

			form.addEventListener('submit', function (event) {
				event.preventDefault();
				setBusy(button, true);
				postForm('pixva_track_device', nonceFor('pixva_track_device'), form).then(function (data) {
					setBusy(button, false);
					report.hidden = false;
					if (print) { print.hidden = false; }
					qs('[data-report-code]', report).textContent = data.code || '—';
					qs('[data-report-device]', report).textContent = data.device || '—';
					qs('[data-report-problem]', report).textContent = data.problemLabel || data.problem || '—';
					qs('[data-report-estimate]', report).textContent = data.estimate || '—';
					qs('[data-report-status]', report).textContent = data.statusLabel || '—';
					qs('[data-report-warranty]', report).textContent = data.warranty || '—';
					qs('[data-report-date]', report).textContent = data.updated || '—';
					var list = qs('[data-report-timeline]', report);
					if (list) { list.innerHTML = timelineHTML(data); }
					setOutput(form, '<strong>گزارش کارشناسی ساخته شد.</strong> برای ذخیره PDF از گزینه چاپ مرورگر استفاده کنید.');
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
					if (report) { report.hidden = true; }
				});
			});

			if (print) { print.addEventListener('click', function () { window.print(); }); }
		});
	}

	/* ==================================================================
	   ۸) فرم‌های درخواست (اعزام، رزرو، استعلام سریع، B2B، بیمه، اشتراک…)
	   ================================================================== */
	function initRequestForms() {
		qsa('[data-request-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var kind = form.getAttribute('data-request-form');
				var button = qs('button[type="submit"]', form);
				var values = formValues(form);
				values.kind = kind;
				setBusy(button, true);
				post('pixva_tool_request', nonceFor('pixva_tool_request'), values).then(function (data) {
					setBusy(button, false);
					setOutput(form, '<strong>' + data.message + '</strong>' + (data.code ? '<span>کد پیگیری: ' + toFa(data.code) + '</span>' : ''));
					form.reset();
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});

		qsa('[data-dispatch-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_dispatch_request', nonceFor('pixva_dispatch_request'), form).then(function (data) {
					setBusy(button, false);
					setOutput(form, '<strong>' + data.message + '</strong>' + (data.code ? '<span>کد پیگیری: ' + toFa(data.code) + '</span>' : ''));
					form.reset();
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});

		qsa('[data-booking-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_booking_request', nonceFor('pixva_booking_request'), form).then(function (data) {
					setBusy(button, false);
					var html = '<strong>' + data.message + '</strong>';
					if (data.code) { html += '<span>کد پیگیری: ' + toFa(data.code) + '</span>'; }
					if (data.estimate) { html += '<span>برآورد: ' + data.estimate + '</span>'; }
					setOutput(form, html);
					form.reset();
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});

		qsa('[data-quickquote-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_quick_quote', nonceFor('pixva_quick_quote'), form).then(function (data) {
					setBusy(button, false);
					var html = '<strong>' + data.message + '</strong>';
					if (data.code) { html += '<span>کد پیگیری: ' + toFa(data.code) + '</span>'; }
					if (data.estimate) { html += '<span>برآورد نرخ‌نامه: ' + data.estimate + '</span>'; }
					if (data.analysis) { html += '<p class="pixva-notice pixva-notice--info">' + data.analysis + '</p>'; }
					setOutput(form, html);
					form.reset();
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});

		qsa('[data-serial-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				rest('/verify-part', { serial: fieldValue(form, 'serial') }).then(function (data) {
					setBusy(button, false);
					setOutput(form,
						'<strong>' + data.message + '</strong>' +
						'<ul class="pixva-breakdown__list">' +
						'<li><span>قطعه</span><b>' + data.part.name + '</b></li>' +
						'<li><span>کد فنی</span><b>' + (data.part.sku || '—') + '</b></li>' +
						'<li><span>گارانتی</span><b>' + toFa(data.part.warranty) + ' روز</b></li>' +
						'<li><span>وضعیت اصالت</span><b>' + data.part.status + '</b></li>' +
						'</ul>');
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});

		qsa('[data-clienthub-form]').forEach(function (form) {
			var shell = shellOf(form);
			var hub = qs('[data-clienthub]', shell);
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var button = qs('button[type="submit"]', form);
				setBusy(button, true);
				postForm('pixva_client_hub', nonceFor('pixva_client_hub'), form).then(function (data) {
					setBusy(button, false);
					hub.hidden = false;
					if (!data.records || !data.records.length) {
						hub.innerHTML = '<p class="pixva-notice pixva-notice--info">' + data.message + '</p>';
						return;
					}
					var html = '<p class="pixva-muted">' + data.message + '</p><div class="pixva-grid pixva-grid--2">';
					data.records.forEach(function (record) {
						html += '<article class="pixva-card pixva-card--tight"><h4>' + record.code + '</h4>' +
							'<p class="pixva-muted">' + record.device + '</p>' +
							'<p>' + record.status + '</p>' +
							(record.warranty ? '<span class="pixva-badge pixva-badge--success">گارانتی تا ' + toFa(record.warranty) + '</span>' : '') +
							'</article>';
					});
					hub.innerHTML = html + '</div>';
				}).catch(function (error) {
					setBusy(button, false);
					setOutput(form, error.message, true);
				});
			});
		});
	}

	/* ==================================================================
	   ۹) محاسبه‌های محلی با داده واقعی جدول‌های کارگاه
	   ================================================================== */
	function readJSON(node, attr) {
		try { return JSON.parse(node.getAttribute(attr) || 'null'); } catch (error) { return null; }
	}

	function initEtaFinder() {
		qsa('[data-eta-finder]').forEach(function (root) {
			var zones = readJSON(root, 'data-zones') || {};
			var result = qs('[data-eta-result]', shellOf(root));
			if (!result) { return; }

			function update() {
				var zone = zones[fieldValue(root, 'zone')];
				if (!zone) { result.innerHTML = ''; return; }
				var kind = fieldValue(root, 'kind') || 'standard';
				var size = parseInt(fieldValue(root, 'size'), 10) || 55;
				var factor = 'express' === kind ? 0.6 : 1;
				var prep = 'pickup' === kind ? 15 : 0;
				var boxTime = size >= 75 ? 25 : (size >= 65 ? 15 : 0);
				var distance = zone.distance || 10;
				var driveMin = Math.round(distance * 3.2); // میانگین ۱۸٫۷ کیلومتر بر ساعت در ترافیک تهران.
				var totalMin = Math.round(driveMin * factor) + boxTime + prep;
				var minutes = totalMin % 60;
				var hours = Math.floor(totalMin / 60);
				result.innerHTML =
					'<div class="pixva-breakdown__head"><strong>بازه اعزام</strong><span>' + zone.eta + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>برآورد دقیق این درخواست</strong><span>' +
					(hours ? toFa(hours) + ' ساعت و ' : '') + toFa(minutes) + ' دقیقه</span></div>' +
					'<div class="pixva-breakdown__head"><strong>فاصله از شعبه مرکزی</strong><span>' + toFa(distance) + ' کیلومتر</span></div>' +
					'<div class="pixva-breakdown__head"><strong>هزینه ایاب‌وذهاب</strong><span>' + money(zone.fee) + '</span></div>' +
					(size >= 75 ? '<p class="pixva-notice pixva-notice--warning">برای سایز ۷۵ اینچ به بالا، اعزام دو نفره با جعبه پددار انجام می‌شود.</p>' : '');
			}

			qsa('select', root).forEach(function (field) { field.addEventListener('change', update); });
			update();
		});
	}

	function initTransportFee() {
		qsa('[data-transport-fee]').forEach(function (root) {
			var zones = readJSON(root, 'data-zones') || {};
			var shell = shellOf(root);

			function update() {
				var zone = zones[fieldValue(root, 'zone')];
				if (!zone) { return; }
				var size = parseInt(fieldValue(root, 'size'), 10) || 55;
				var floor = parseInt(fieldValue(root, 'floor'), 10) || 0;
				var elevator = fieldValue(root, 'elevator');
				var sizeFactor = size >= 85 ? 1.6 : (size >= 75 ? 1.4 : (size >= 65 ? 1.2 : (size >= 55 ? 1.05 : 1)));
				var stair = 'no' === elevator ? floor * 120000 : 0;
				var total = Math.round((zone.fee * sizeFactor + stair) / 10000) * 10000;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>هزینه پایه منطقه</strong><span>' + money(zone.fee) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب سایز ' + toFa(size) + ' اینچ</strong><span>×' + toFa(sizeFactor) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>حمل پله‌ای</strong><span>' + (stair ? money(stair) : 'بدون هزینه (آسانسور)') + '</span></div>' +
					'<div class="pixva-breakdown__head pixva-breakdown__head--total"><strong>مجموع ایاب‌وذهاب</strong><span>' + money(total) + '</span></div>' +
					(size >= 75 ? '<p class="pixva-notice pixva-notice--warning">حمل تلویزیون ۷۵ اینچ به بالا دو نفره است و در زمان اعزام لحاظ شده.</p>' : ''),
					false);
			}

			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initPowerSaver() {
		qsa('[data-power-saver]').forEach(function (root) {
			var tariffs = readJSON(root, 'data-tariffs') || {};

			function update() {
				var watt = parseFloat(fieldValue(root, 'watt')) || 0;
				var hours = parseFloat(fieldValue(root, 'hours')) || 0;
				var tariff = tariffs[fieldValue(root, 'tariff')];
				var eco = 'on' === fieldValue(root, 'eco');
				if (!watt || !hours || !tariff) { return; }
				var usedWatt = eco ? watt * 0.78 : watt;
				var kwh = (usedWatt * hours * 30) / 1000;
				var costRial = kwh * tariff.rate;
				var costToman = costRial / 10;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>مصرف ماهانه</strong><span>' + toFa(kwh.toFixed(1)) + ' کیلووات‌ساعت</span></div>' +
					'<div class="pixva-breakdown__head"><strong>هزینه ماهانه</strong><span>' + money(costToman) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>هزینه سالانه</strong><span>' + money(costToman * 12) + '</span></div>' +
					(eco ? '<div class="pixva-breakdown__head"><strong>صرفه‌جویی سالانه با حالت Eco</strong><span>' + money((kwh / 0.78 - kwh) * tariff.rate / 10 * 12) + '</span></div>' : '') +
					'<p class="pixva-tool-note">تعرفه انتخابی: ' + tariff.label + ' (' + toFa(group(tariff.rate)) + ' ریال بر کیلووات‌ساعت)</p>');
			}

			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initViewingDistance() {
		qsa('[data-viewing-distance]').forEach(function (root) {
			function update() {
				var size = parseFloat(fieldValue(root, 'size')) || 0;
				var res = fieldValue(root, 'resolution');
				if (size < 24) { return; }
				var angle = 'fhd' === res ? 32 : 30;
				var widthCm = size * 2.54 * (16 / Math.sqrt(16 * 16 + 9 * 9));
				var distance = widthCm / (2 * Math.tan((angle / 2) * Math.PI / 180));
				var min = distance * 0.85;
				var max = distance * 1.3;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>عرض قاب</strong><span>' + toFa(widthCm.toFixed(0)) + ' سانتی‌متر</span></div>' +
					'<div class="pixva-breakdown__head"><strong>فاصله ایده‌آل (زاویه ' + toFa(angle) + ' درجه)</strong><span>' + toFa((distance / 100).toFixed(2)) + ' متر</span></div>' +
					'<div class="pixva-breakdown__head"><strong>بازه قابل قبول</strong><span>' + toFa((min / 100).toFixed(2)) + ' تا ' + toFa((max / 100).toFixed(2)) + ' متر</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ارتفاع مرکز صفحه از کف</strong><span>۱۰۵ تا ۱۱۵ سانتی‌متر</span></div>');
			}
			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initBacklightLife() {
		qsa('[data-backlight-life]').forEach(function (root) {
			function update() {
				var hours = parseFloat(fieldValue(root, 'hours')) || 0;
				var brightness = fieldValue(root, 'brightness');
				var vent = fieldValue(root, 'vent');
				if (!hours) { return; }
				var nominal = 40000;
				var brightnessFactor = 'high' === brightness ? 1.4 : ('low' === brightness ? 0.85 : 1);
				var ventFactor = 'closed' === vent ? 1.2 : 1;
				var effective = nominal / (brightnessFactor * ventFactor);
				var remaining = Math.max(0, effective - hours);
				var daily = 6;
				var years = remaining / (daily * 365);
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>عمر مفید با شرایط فعلی</strong><span>' + toFa(group(Math.round(effective))) + ' ساعت</span></div>' +
					'<div class="pixva-breakdown__head"><strong>کارکرد تا امروز</strong><span>' + toFa(group(Math.round(hours))) + ' ساعت</span></div>' +
					'<div class="pixva-breakdown__head"><strong>باقی‌مانده تخمینی</strong><span>' + toFa(group(Math.round(remaining))) + ' ساعت (≈ ' + toFa(years.toFixed(1)) + ' سال با ۶ ساعت تماشا در روز)</span></div>' +
					(years < 1.5 ? '<p class="pixva-notice pixva-notice--warning">بک‌لایت در بازه فرسودگی است؛ افت روشنایی و لکه‌های تیره نشانه نزدیک بودن سوختن شاخه‌هاست.</p>' : '<p class="pixva-notice pixva-notice--info">با کاهش روشنایی به زیر ۷۰٪ و تهویه مناسب، عمر بک‌لایت تا ۴۰٪ افزایش می‌یابد.</p>'));
			}
			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initTradeIn() {
		qsa('[data-trade-in]').forEach(function (root) {
			var run = qs('[data-trade-run]', root);
			var box = qs('[data-trade-result]', shellOf(root));
			if (!run || !box) { return; }

			run.addEventListener('click', function () {
				var size = parseInt(fieldValue(root, 'size'), 10) || 55;
				var age = parseFloat(fieldValue(root, 'age')) || 0;
				var panel = fieldValue(root, 'panel');
				var boards = fieldValue(root, 'boards');
				var base = size >= 75 ? 4200000 : (size >= 65 ? 3200000 : (size >= 55 ? 2400000 : (size >= 43 ? 1600000 : 900000)));
				var panelFactor = 'intact' === panel ? 1 : ('lines' === panel ? 0.55 : 0.05);
				var boardFactor = 'ok' === boards ? 1 : ('partial' === boards ? 0.65 : 0.3);
				var ageFactor = age <= 2 ? 1 : (age <= 5 ? 0.8 : (age <= 8 ? 0.55 : (age <= 12 ? 0.35 : 0.2)));
				var value = Math.round((base * panelFactor * boardFactor * ageFactor) / 50000) * 50000;
				box.hidden = false;
				box.innerHTML =
					'<div class="pixva-breakdown__head"><strong>پایه داغی ' + toFa(size) + ' اینچ</strong><span>' + money(base) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب پنل</strong><span>×' + toFa(panelFactor) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب بردها</strong><span>×' + toFa(boardFactor) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب سن (' + toFa(age) + ' سال)</strong><span>×' + toFa(ageFactor) + '</span></div>' +
					'<div class="pixva-breakdown__head pixva-breakdown__head--total"><strong>ارزش داغی قابل کسر از فاکتور</strong><span>' + money(value) + '</span></div>' +
					'<p class="pixva-tool-note">ارزیابی نهایی پس از بازدید حضوری قطعی می‌شود؛ پنل شکسته عملاً ارزش داغی ندارد.</p>';
			});
		});
	}

	function initProtector() {
		qsa('[data-protector]').forEach(function (root) {
			function update() {
				var size = parseInt(fieldValue(root, 'size'), 10) || 55;
				var watt = parseFloat(fieldValue(root, 'watt')) || 130;
				var risk = fieldValue(root, 'risk');
				var grid = fieldValue(root, 'grid');
				var score = 0;
				score += 'child' === risk ? 35 : ('pet' === risk ? 25 : ('sun' === risk ? 15 : 5));
				score += 'unstable' === grid ? 30 : ('industrial' === grid ? 40 : 5);
				score += size >= 75 ? 25 : (size >= 65 ? 18 : (size >= 55 ? 12 : 6));
				score = Math.min(100, score);
				var level = score >= 65 ? 'بحرانی' : (score >= 40 ? 'متوسط' : 'کم');
				var glass = size >= 75 ? 'پلکسی ۴ میلی‌متر با پایه فلزی' : 'پلکسی ۳ میلی‌متر';
				var va = Math.round((watt * 1.6) / 100) * 100;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>شاخص ریسک</strong><span>' + toFa(score) + ' از ۱۰۰ (' + level + ')</span></div>' +
					'<div class="pixva-breakdown__head"><strong>محافظ پیشنهادی</strong><span>' + glass + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>استبیلایزر پیشنهادی</strong><span>' + toFa(group(va)) + ' VA</span></div>' +
					(score >= 40 ? '<p class="pixva-notice pixva-notice--warning">با این سطح ریسک، نصب هم‌زمان محافظ ضربه و استبیلایزر توصیه می‌شود؛ بیشترین پرونده‌های آب‌خوردگی و سوختن برد پاور در همین دسته است.</p>' : '<p class="pixva-notice pixva-notice--info">ریسک پایین است؛ یک محافظ استاندارد و تهویه مناسب کافی است.</p>'));
			}
			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initStabilizer() {
		qsa('[data-stabilizer]').forEach(function (root) {
			function update() {
				var watt = parseFloat(fieldValue(root, 'watt')) || 0;
				var extra = fieldValue(root, 'extra');
				var margin = parseFloat(fieldValue(root, 'margin')) || 1.6;
				var extraWatt = 'sound' === extra ? 150 : ('console' === extra ? 200 : ('all' === extra ? 350 : 0));
				var total = watt + extraWatt;
				var va = Math.ceil((total * margin * 1.2) / 100) * 100; // ضریب ۱٫۲ برای جریان هجومی روشن شدن.
				var standard = va <= 1000 ? '۱۰۰۰ VA' : (va <= 2000 ? '۲۰۰۰ VA' : (va <= 3000 ? '۳۰۰۰ VA' : (va <= 6000 ? '۶۰۰۰ VA' : '۸۰۰۰ VA و بالاتر')));
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>توان مصرفی کل</strong><span>' + toFa(group(total)) + ' وات</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ظرفیت محاسبه‌شده</strong><span>' + toFa(group(va)) + ' VA</span></div>' +
					'<div class="pixva-breakdown__head"><strong>کلاس استاندارد بازار</strong><span>' + standard + '</span></div>' +
					'<p class="pixva-tool-note">فرمول: (توان × ضریب اطمینان ' + toFa(margin) + ' × ۱٫۲ جریان هجومی) ÷ ضریب توان ۰٫۸. استبیلایزر سروو موتوری برای نوسان‌های شدید و رله‌ای برای نوسان ملایم مناسب است.</p>');
			}
			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initShippingCalc() {
		qsa('[data-shipping-calc]').forEach(function (root) {
			var zones = readJSON(root, 'data-zones') || {};

			function update() {
				var zone = zones[fieldValue(root, 'zone')];
				if (!zone) { return; }
				var size = parseInt(fieldValue(root, 'size'), 10) || 55;
				var box = fieldValue(root, 'box');
				var base = zone.fee * (zone.ship || 1);
				var sizeFactor = size >= 85 ? 2.4 : (size >= 75 ? 1.9 : (size >= 65 ? 1.5 : (size >= 55 ? 1.2 : 1)));
				var boxFactor = 'factory' === box ? 1 : ('padded' === box ? 1.15 : 1.35);
				var weight = Math.round(size * 0.42);
				var total = Math.round((base * sizeFactor * boxFactor) / 10000) * 10000;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>وزن تقریبی با بسته</strong><span>' + toFa(weight) + ' کیلوگرم</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب مسافت منطقه</strong><span>×' + toFa(zone.ship || 1) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>ضریب سایز و بسته‌بندی</strong><span>×' + toFa((sizeFactor * boxFactor).toFixed(2)) + '</span></div>' +
					'<div class="pixva-breakdown__head pixva-breakdown__head--total"><strong>هزینه حمل</strong><span>' + money(total) + '</span></div>' +
					('none' === box ? '<p class="pixva-notice pixva-notice--danger">حمل بدون بسته‌بندی استاندارد انجام نمی‌شود؛ مسئولیت شکستگی پنل در این حالت با مشتری است.</p>' : ''));
			}

			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initReminderCalc() {
		qsa('[data-reminder-calc]').forEach(function (root) {
			function update() {
				var dateValue = fieldValue(root, 'service_date');
				var hours = parseInt(fieldValue(root, 'hours'), 10) || 0;
				if (!dateValue) { return; }
				var service = new Date(dateValue);
				if (isNaN(service.getTime())) { return; }
				var next = new Date(service.getTime() + hours * 3600 * 1000);
				var days = Math.max(0, Math.round((next.getTime() - Date.now()) / 86400000));
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>سرویس دوره‌ای بعدی</strong><span>' + toFa(next.toLocaleDateString('fa-IR')) + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>مانده تا سررسید</strong><span>' + toFa(days) + ' روز</span></div>' +
					(days <= 14 ? '<p class="pixva-notice pixva-notice--warning">سررسید نزدیک است؛ برای ثبت یادآوری پیامکی شماره همراه را در فرم زیر وارد کنید.</p>' : '<p class="pixva-notice pixva-notice--info">یادآوری پیامکی ۳ روز قبل از سررسید ارسال می‌شود.</p>'));
			}
			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	function initInterference() {
		qsa('[data-interference]').forEach(function (root) {
			var rows = readJSON(root, 'data-rows') || [];
			var result = qs('[data-interference-result]', shellOf(root));
			if (!result) { return; }
			var select = qs('[name="symptom"]', root);
			if (!select) { return; }

			function update() {
				var index = parseInt(select.value, 10);
				if (isNaN(index) || !rows[index]) { result.hidden = true; return; }
				var row = rows[index];
				result.hidden = false;
				result.innerHTML =
					'<div class="pixva-breakdown__head"><strong>علت ریشه‌ای</strong><span>' + row.cause + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>راه‌حل کارگاهی</strong><span>' + row.fix + '</span></div>';
			}
			select.addEventListener('change', update);
		});
	}

	function initCableCheck() {
		qsa('[data-cable-check]').forEach(function (root) {
			var standards = readJSON(root, 'data-standards') || {};

			function update() {
				var key = fieldValue(root, 'mode');
				var length = parseFloat(fieldValue(root, 'length')) || 0;
				var std = standards[key];
				if (!std) { return; }
				var maxLen = parseFloat(String(std.max_len).replace(/[^\d.]/g, '')) || 0;
				var ok = length <= maxLen;
				setOutput(root,
					'<div class="pixva-breakdown__head"><strong>استاندارد لازم</strong><span>' + std.spec + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>پهنای باند</strong><span>' + std.bandwidth + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>حداکثر طول مجاز</strong><span>' + std.max_len + '</span></div>' +
					'<div class="pixva-breakdown__head"><strong>نتیجه ' + toFa(length) + ' متر</strong><span>' + (ok ? 'مناسب است' : 'بیش از حد مجاز') + '</span></div>' +
					'<p class="pixva-tool-note">' + std.note + '</p>' +
					(ok ? '' : '<p class="pixva-notice pixva-notice--danger">برای این طول، کابل فیبر نوری HDMI یا انتقال‌دهنده HDBaseT لازم است؛ کابل مسی در این طول باعث برفک دیجیتال و قطع‌شدن تصویر می‌شود.</p>'));
			}

			qsa('select,input', root).forEach(function (field) {
				field.addEventListener('change', update);
				field.addEventListener('input', update);
			});
			update();
		});
	}

	/* ==================================================================
	   ۱۰) چک‌لیست‌ها، تب‌ها، نمای انفجاری و لایه‌ها
	   ================================================================== */
	function initChecklists() {
		[
			{ root: '[data-packing-guide]', item: '[data-pack-item]', bar: '[data-pack-progress]' },
			{ root: '[data-seal-checklist]', item: '[data-seal-item]', bar: '[data-seal-progress]' },
			{ root: '[data-calibration]', item: '[data-cal-item]', bar: '[data-cal-progress]' }
		].forEach(function (config) {
			qsa(config.root).forEach(function (root) {
				var bar = qs(config.bar, shellOf(root));
				function update() {
					var items = qsa(config.item, root);
					var done = items.filter(function (item) { return item.checked; }).length;
					var percent = items.length ? Math.round((done / items.length) * 100) : 0;
					if (bar) { bar.style.width = percent + '%'; }
					items.forEach(function (item) {
						var li = item.closest ? item.closest('li') : null;
						if (li) { li.classList.toggle('is-done', item.checked); }
					});
				}
				qsa(config.item, root).forEach(function (item) { item.addEventListener('change', update); });
				update();
			});
		});
	}

	function initTabs() {
		qsa('[data-os-guide]').forEach(function (root) {
			var tabs = qsa('[data-os-tab]', root);
			tabs.forEach(function (tab) {
				tab.addEventListener('click', function () {
					var key = tab.getAttribute('data-os-tab');
					tabs.forEach(function (other) {
						var active = other === tab;
						other.classList.toggle('is-active', active);
						other.setAttribute('aria-selected', active ? 'true' : 'false');
					});
					qsa('[data-os-panel]', root).forEach(function (panel) {
						panel.classList.toggle('is-active', panel.getAttribute('data-os-panel') === key);
					});
				});
			});
		});
	}

	function initHighlightStacks() {
		qsa('[data-layers3d]').forEach(function (root) {
			qsa('[data-layer-target]', root).forEach(function (button) {
				button.addEventListener('click', function () {
					var index = button.getAttribute('data-layer-target');
					qsa('[data-layer-index]', root).forEach(function (layer) {
						layer.classList.toggle('is-active', layer.getAttribute('data-layer-index') === index);
					});
					qsa('[data-layer-target]', root).forEach(function (other) {
						var active = other === button;
						other.classList.toggle('is-active', active);
						other.setAttribute('aria-expanded', active ? 'true' : 'false');
					});
				});
			});
		});

		qsa('[data-exploded]').forEach(function (root) {
			qsa('[data-part-target]', root).forEach(function (button) {
				button.addEventListener('click', function () {
					var index = button.getAttribute('data-part-target');
					qsa('[data-part-index]', root).forEach(function (part) {
						part.classList.toggle('is-active', part.getAttribute('data-part-index') === index);
					});
					qsa('[data-part-target]', root).forEach(function (other) {
						var active = other === button;
						other.classList.toggle('is-active', active);
						other.setAttribute('aria-expanded', active ? 'true' : 'false');
					});
				});
			});
		});
	}

	function initVirtualTour() {
		qsa('[data-virtual-tour]').forEach(function (root) {
			var viewport = qs('[data-tour-viewport]', root);
			var image = qs('[data-tour-image]', root);
			if (!viewport || !image) { return; }
			var dragging = false;
			var startX = 0;
			var startOffset = 0;
			var offset = 0;

			function apply() {
				var max = Math.max(0, image.scrollWidth - viewport.clientWidth);
				offset = Math.max(-max, Math.min(0, offset));
				image.style.transform = 'translateX(' + offset + 'px)';
			}

			viewport.addEventListener('pointerdown', function (event) {
				dragging = true;
				startX = event.clientX;
				startOffset = offset;
				viewport.setPointerCapture(event.pointerId);
				viewport.classList.add('is-dragging');
			});
			viewport.addEventListener('pointermove', function (event) {
				if (!dragging) { return; }
				offset = startOffset + (event.clientX - startX);
				apply();
			});
			['pointerup', 'pointercancel'].forEach(function (type) {
				viewport.addEventListener(type, function () { dragging = false; viewport.classList.remove('is-dragging'); });
			});
			viewport.addEventListener('wheel', function (event) {
				event.preventDefault();
				offset -= event.deltaX || event.deltaY;
				apply();
			}, { passive: false });

			qsa('[data-tour-station]', root).forEach(function (button) {
				button.addEventListener('click', function () {
					var index = parseInt(button.getAttribute('data-tour-station'), 10) || 0;
					offset = -(index * viewport.clientWidth * 0.8);
					apply();
					qsa('[data-tour-station]', root).forEach(function (other) {
						var active = other === button;
						other.classList.toggle('is-active', active);
						other.setAttribute('aria-expanded', active ? 'true' : 'false');
					});
				});
			});
		});
	}

	/* ==================================================================
	   ۱۱) الگوهای تصویری روی بوم: تست پورت HDMI و کالیبراسیون
	   ================================================================== */
	function drawPattern(canvas, kind) {
		if (!canvas) { return; }
		var ctx = canvas.getContext('2d');
		var width = canvas.width;
		var height = canvas.height;
		ctx.clearRect(0, 0, width, height);

		if ('grid' === kind) {
			ctx.fillStyle = '#0E2C38';
			ctx.fillRect(0, 0, width, height);
			ctx.strokeStyle = '#E8F5F8';
			ctx.lineWidth = 1;
			for (var x = 0; x <= width; x += 16) {
				ctx.beginPath(); ctx.moveTo(x + 0.5, 0); ctx.lineTo(x + 0.5, height); ctx.stroke();
			}
			for (var y = 0; y <= height; y += 16) {
				ctx.beginPath(); ctx.moveTo(0, y + 0.5); ctx.lineTo(width, y + 0.5); ctx.stroke();
			}
			ctx.strokeStyle = '#F59E6C';
			ctx.lineWidth = 2;
			ctx.strokeRect(20, 20, width - 40, height - 40);
			return;
		}

		if ('gradient' === kind) {
			var grad = ctx.createLinearGradient(0, 0, width, 0);
			for (var stop = 0; stop <= 10; stop += 1) {
				var value = Math.round((stop / 10) * 255);
				grad.addColorStop(stop / 10, 'rgb(' + value + ',' + value + ',' + value + ')');
			}
			ctx.fillStyle = grad;
			ctx.fillRect(0, 0, width, height);
			return;
		}

		if ('motion' === kind) {
			ctx.fillStyle = '#17313B';
			ctx.fillRect(0, 0, width, height);
			for (var row = 0; row < 6; row += 1) {
				var position = ((Date.now() / 8) + row * 90) % (width + 120) - 60;
				ctx.fillStyle = row % 2 ? '#55B8C8' : '#F7FAFC';
				ctx.fillRect(position, row * (height / 6) + 8, 60, (height / 6) - 16);
			}
			return;
		}

		if ('black' === kind) {
			ctx.fillStyle = '#000000';
			ctx.fillRect(0, 0, width, height);
			[1, 2, 3, 4, 5].forEach(function (step) {
				ctx.fillStyle = 'rgb(' + (step * 5) + ',' + (step * 5) + ',' + (step * 5) + ')';
				ctx.fillRect((step - 1) * (width / 5), height - 60, width / 5, 60);
			});
			return;
		}

		if ('white' === kind) {
			ctx.fillStyle = '#FFFFFF';
			ctx.fillRect(0, 0, width, height);
			[96, 97, 98, 99, 100].forEach(function (step, index) {
				var level = Math.round(step * 2.55);
				ctx.fillStyle = 'rgb(' + level + ',' + level + ',' + level + ')';
				ctx.fillRect(index * (width / 5), height - 60, width / 5, 60);
			});
			return;
		}

		if ('gray' === kind) {
			var ramp = ctx.createLinearGradient(0, 0, width, 0);
			for (var i = 0; i <= 20; i += 1) {
				var gray = Math.round((i / 20) * 255);
				ramp.addColorStop(i / 20, 'rgb(' + gray + ',' + gray + ',' + gray + ')');
			}
			ctx.fillStyle = ramp;
			ctx.fillRect(0, 0, width, height);
			return;
		}

		if ('color' === kind) {
			var colors = ['#FFFFFF', '#FFFF00', '#00FFFF', '#00FF00', '#FF00FF', '#FF0000', '#0000FF', '#000000'];
			colors.forEach(function (color, index) {
				ctx.fillStyle = color;
				ctx.fillRect(index * (width / colors.length), 0, width / colors.length, height);
			});
		}
	}

	function initPatternCanvases() {
		qsa('[data-port-tester]').forEach(function (root) {
			var host = qs('[data-port-canvas]', shellOf(root));
			var canvas = document.createElement('canvas');
			canvas.width = 640;
			canvas.height = 360;
			canvas.className = 'pixva-pattern-canvas';
			if (host) {
				host.innerHTML = '';
				host.appendChild(canvas);
			}
			var motionTimer = null;

			qsa('[data-port-pattern]', root).forEach(function (button) {
				button.addEventListener('click', function () {
					var kind = button.getAttribute('data-port-pattern');
					if (motionTimer) { window.clearInterval(motionTimer); motionTimer = null; }
					drawPattern(canvas, kind);
					if ('motion' === kind) {
						motionTimer = window.setInterval(function () { drawPattern(canvas, 'motion'); }, reduceMotion() ? 120 : 33);
					}
					qsa('[data-port-pattern]', root).forEach(function (other) { other.classList.toggle('is-active', other === button); });
				});
			});

			var hdcp = qs('[data-port-hdcp]', root);
			if (hdcp) {
				hdcp.addEventListener('click', function () {
					setOutput(root,
						'<strong>راهنمای عیب‌یابی HDCP</strong>' +
						'<ol class="pixva-steps">' +
						'<li>پیام «HDCP not supported» یعنی دست‌دادن (handshake) بین منبع و تلویزیون انجام نشده؛ ابتدا کابل را از دو طرف جدا و دوباره وصل کنید.</li>' +
						'<li>منبع را روی یک پورت دیگر تلویزیون امتحان کنید؛ خرابی پورت HDMI با سوختن آی‌سی بافر همان ورودی رخ می‌دهد.</li>' +
						'<li>نسخه HDCP گیرنده دیجیتال یا کنسول را پایین‌تر تنظیم کنید (مثلاً ۱٫۴) تا سازگاری بررسی شود.</li>' +
						'<li>اگر روی همه پورت‌ها پیام دیده می‌شود، آی‌سی HDCP روی مین‌برد یا حافظه EDID نیاز به پروگرام مجدد دارد.</li>' +
						'</ol>');
				});
			}
		});

		qsa('[data-calibration]').forEach(function (root) {
			var host = qs('[data-cal-canvas]', root);
			if (!host) { return; }
			var canvas = document.createElement('canvas');
			canvas.width = 640;
			canvas.height = 360;
			canvas.className = 'pixva-pattern-canvas';
			host.innerHTML = '';
			host.appendChild(canvas);
			drawPattern(canvas, 'black');

			qsa('[data-cal-pattern]', root).forEach(function (button) {
				button.addEventListener('click', function () {
					drawPattern(canvas, button.getAttribute('data-cal-pattern'));
					qsa('[data-cal-pattern]', root).forEach(function (other) { other.classList.toggle('is-active', other === button); });
				});
			});
		});
	}

	/* ==================================================================
	   ۱۲) دوربین: تست ریموت و سنجش دیوار
	   ================================================================== */
	function startCamera(video, root, onReady, onError) {
		if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
			onError('مرورگر شما دسترسی به دوربین را پشتیبانی نمی‌کند.');
			return;
		}
		navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
			video.srcObject = stream;
			video.play();
			root.dataset.cameraActive = '1';
			onReady(stream);
		}).catch(function (error) {
			onError('دسترسی به دوربین داده نشد: ' + error.name);
		});
	}

	function stopCamera(video, root) {
		if (video && video.srcObject) {
			video.srcObject.getTracks().forEach(function (track) { track.stop(); });
			video.srcObject = null;
		}
		if (root) { delete root.dataset.cameraActive; }
	}

	function initCameraTools() {
		qsa('[data-remote-tester]').forEach(function (root) {
			var video = qs('[data-remote-video]', root);
			var start = qs('[data-remote-start]', root);
			var stop = qs('[data-remote-stop]', root);
			if (start) {
				start.addEventListener('click', function () {
					startCamera(video, root, function () {
						setOutput(root, 'دوربین روشن است. فرستنده ریموت را ۵ تا ۱۰ سانتی‌متر از لنز نگه دارید و کلیدها را فشار دهید؛ نور IR به‌صورت نقطه روشن دیده می‌شود.');
					}, function (message) { setOutput(root, message, true); });
				});
			}
			if (stop) {
				stop.addEventListener('click', function () {
					stopCamera(video, root);
					setOutput(root, 'دوربین خاموش شد.');
				});
			}
		});

		qsa('[data-ar-wall]').forEach(function (root) {
			var video = qs('[data-ar-video]', root);
			var frame = qs('[data-ar-frame]', root);
			var dims = qs('[data-ar-dims]', root);
			var start = qs('[data-ar-start]', root);
			var stop = qs('[data-ar-stop]', root);

			function updateFrame() {
				var size = parseFloat(fieldValue(root, 'size')) || 65;
				var widthCm = size * 2.54 * (16 / Math.sqrt(16 * 16 + 9 * 9));
				var heightCm = size * 2.54 * (9 / Math.sqrt(16 * 16 + 9 * 9));
				// مقیاس مرجع: هر ۱۰۰ سانتی‌متر معادل ۲۴٪ عرض قاب در نمای دوربین گوشی.
				var percent = (widthCm / 100) * 24;
				if (frame) {
					frame.style.width = Math.min(96, percent) + '%';
					frame.style.aspectRatio = '16 / 9';
				}
				if (dims) {
					dims.innerHTML =
						'<div class="pixva-breakdown__head"><strong>ابعاد قاب ' + toFa(size) + ' اینچ</strong><span>' + toFa(widthCm.toFixed(1)) + ' × ' + toFa(heightCm.toFixed(1)) + ' سانتی‌متر</span></div>' +
						'<div class="pixva-breakdown__head"><strong>مساحت دیوار اشغالی</strong><span>' + toFa(((widthCm * heightCm) / 10000).toFixed(2)) + ' متر مربع</span></div>';
				}
			}

			if (start) {
				start.addEventListener('click', function () {
					startCamera(video, root, function () {
						updateFrame();
						setOutput(root, 'قاب مجازی روی تصویر دوربین قرار گرفت. برای دقت بیشتر، فاصله گوشی تا دیوار را حدود ۲ متر نگه دارید و قاب را با یک شیء شناخته‌شده (مثل کلید برق) مقایسه کنید.');
					}, function (message) { setOutput(root, message, true); });
				});
			}
			if (stop) {
				stop.addEventListener('click', function () {
					stopCamera(video, root);
					if (frame) { frame.style.width = '0%'; }
					setOutput(root, 'دوربین خاموش شد.');
				});
			}
			qsa('input,select', root).forEach(function (field) { field.addEventListener('input', updateFrame); });
			updateFrame();
		});
	}

	/* ==================================================================
	   ۱۳) جست‌وجوی صوتی، نصب PWA، انتخاب شعبه و اشتراک
	   ================================================================== */
	function initVoiceSearch() {
		qsa('[data-voice-search]').forEach(function (root) {
			var button = qs('[data-voice-start]', root);
			var input = qs('[name="q"]', root);
			var go = qs('[data-voice-go]', root);
			if (!button) { return; }
			var Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
			if (!Recognition) {
				button.disabled = true;
				setOutput(root, 'مرورگر شما از Web Speech API پشتیبانی نمی‌کند؛ متن را دستی وارد کنید.', true);
				return;
			}
			var recognition = new Recognition();
			recognition.lang = 'fa-IR';
			recognition.continuous = false;
			recognition.interimResults = true;
			var listening = false;

			button.addEventListener('click', function () {
				if (listening) { recognition.stop(); return; }
				try { recognition.start(); } catch (error) { /* already started */ }
			});
			recognition.onstart = function () { listening = true; button.classList.add('is-loading'); };
			recognition.onresult = function (event) {
				var text = '';
				for (var i = 0; i < event.results.length; i += 1) { text += event.results[i][0].transcript; }
				if (input) { input.value = text; }
				if (go && text) {
					go.href = (cfg.homeUrl || '/') + '?s=' + encodeURIComponent(text);
				}
			};
			recognition.onerror = function (event) { setOutput(root, 'خطای تشخیص گفتار: ' + event.error, true); };
			recognition.onend = function () {
				listening = false;
				button.classList.remove('is-loading');
				if (input && input.value) { setOutput(root, 'متن تشخیص داده شد: «' + input.value + '». برای جست‌وجو روی دکمه «جست‌وجو در سایت» بزنید.'); }
			};
		});
	}

	function initOfflineBanner() {
		var message = (cfg.pwa && cfg.pwa.offline) ? cfg.pwa.offline : '';
		if (!message) { return; }

		var bar = qs('[data-pixva-offline]');
		if (!bar) {
			bar = document.createElement('div');
			bar.className = 'pixva-offline-bar';
			bar.setAttribute('data-pixva-offline', '');
			bar.setAttribute('role', 'status');
			bar.setAttribute('aria-live', 'polite');
			bar.hidden = true;
			bar.innerHTML = '<span class="pixva-offline-bar__dot" aria-hidden="true"></span><span class="pixva-offline-bar__text"></span>';
			document.body.appendChild(bar);
		}

		var text = qs('.pixva-offline-bar__text', bar);
		if (text) { text.textContent = message; }

		function sync() {
			var offline = ('onLine' in navigator) ? !navigator.onLine : false;
			bar.hidden = !offline;
			document.documentElement.classList.toggle('is-offline', offline);
		}

		window.addEventListener('online', sync);
		window.addEventListener('offline', sync);
		sync();
	}

	function initPwaInstall() {
		var deferredPrompt = null;
		window.addEventListener('beforeinstallprompt', function (event) {
			event.preventDefault();
			deferredPrompt = event;
			qsa('[data-pwa-install]').forEach(function (root) {
				var hint = qs('[data-pwa-hint]', root);
				if (hint) { hint.textContent = 'وب‌اپ پیکسوا آماده نصب است.'; }
			});
		});

		qsa('[data-pwa-button]').forEach(function (button) {
			button.addEventListener('click', function () {
				var root = button.closest ? button.closest('[data-pwa-install]') : null;
				var hint = qs('[data-pwa-hint]', root || document);
				if (deferredPrompt) {
					deferredPrompt.prompt();
					deferredPrompt.userChoice.then(function (choice) {
						if (hint) { hint.textContent = 'accepted' === choice.outcome ? 'وب‌اپ نصب شد؛ از صفحه اصلی گوشی باز کنید.' : 'نصب لغو شد.'; }
						deferredPrompt = null;
					});
					return;
				}
				if (hint) {
					hint.textContent = window.matchMedia('(display-mode: standalone)').matches
						? 'وب‌اپ در حال حاضر نصب و فعال است.'
						: 'در iOS: دکمه Share و سپس Add to Home Screen. در اندروید/کروم: منوی سه‌نقطه و Install app.';
				}
			});
		});
	}

	function initBranchSwitcher() {
		qsa('[data-branch-switcher]').forEach(function (root) {
			var select = qs('[name="zone"]', root);
			var list = qs('[data-branch-list]', shellOf(root));
			if (!select || !list) { return; }

			function update() {
				var zone = select.value;
				var visible = 0;
				qsa('[data-branch]', list).forEach(function (card) {
					var zones = (card.getAttribute('data-zones') || '').split(',');
					var match = !zone || zones.indexOf(zone) !== -1;
					card.classList.toggle('is-dimmed', !match);
					card.classList.toggle('is-match', match && !!zone);
					if (match) { visible += 1; }
				});
				if (zone) {
					setOutput(root, visible > 1
						? toFa(visible) + ' شعبه این منطقه را پوشش می‌دهد؛ نزدیک‌ترین شعبه با حاشیه روشن مشخص شده است.'
						: 'پوشش مستقیم این منطقه با شعبه مرکزی انجام می‌شود و اعزام تکنسین سیار رایگان است.');
				}
			}

			select.addEventListener('change', update);
			update();
		});
	}

	function initSubscription() {
		qsa('[data-subscription]').forEach(function (root) {
			var shell = shellOf(root);
			var form = qs('[data-request-form="subscription"]', shell);
			qsa('[data-plan-select]', shell).forEach(function (button) {
				button.addEventListener('click', function () {
					if (!form) { return; }
					form.hidden = false;
					var planField = qs('[name="plan"]', form);
					if (planField) {
						planField.value = button.getAttribute('data-plan-select');
					}
					var priceField = qs('[name="plan_price"]', form);
					if (priceField) { priceField.value = button.getAttribute('data-plan-price'); }
					qsa('[data-plan]', shell).forEach(function (card) {
						card.classList.toggle('is-selected', card.getAttribute('data-plan') === button.getAttribute('data-plan-select'));
					});
					setOutput(form, '<strong>اشتراک انتخاب شد.</strong> شماره همراه را وارد کنید تا کارشناس سازمانی برای صدور قرارداد تماس بگیرد.');
					form.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'center' });
					var first = qs('input[type="text"]', form);
					if (first) { first.focus(); }
				});
			});
		});
	}

	function initDockPreview() {
		qsa('[data-dock-preview]').forEach(function (root) {
			var links = qsa('[data-dock-link]', root);
			if (!links.length) { return; }
			var frame = qs('[data-dock-frame]', root);
			var hint = qs('[data-dock-hint]', shellOf(root));
			links.forEach(function (link) {
				link.addEventListener('click', function (event) {
					var target = link.getAttribute('data-dock-link') || '';
					if (!target) { return; }
					var external = /^https?:\/\//i.test(target) && target.indexOf(window.location.origin) !== 0;
					if (!external) { event.preventDefault(); }
					links.forEach(function (other) { other.classList.toggle('is-active', other === link); });
					if (frame && !external) { frame.src = target; }
					if (hint) {
						var label = qs('span', link);
						hint.textContent = external
							? 'آیتم «' + (label ? label.textContent : '') + '» در زبانه جدید باز می‌شود (پیام‌رسان خارجی).'
							: 'پیش‌نمایش «' + (label ? label.textContent : '') + '» در قاب موبایل بارگذاری شد.';
					}
				});
			});
		});
	}

	/* ==================================================================
	   ۱۴) شمارنده‌ها و مسیر تعمیر (انیمیشن‌های صفحه اصلی)
	   ================================================================== */
	function animateCounter(node) {
		var target = parseFloat(node.getAttribute('data-count-to'));
		if (isNaN(target)) { return; }
		var decimals = parseInt(node.getAttribute('data-count-decimals') || '0', 10);
		var suffix = node.getAttribute('data-count-suffix') || '';
		var duration = reduceMotion() ? 0 : 1400;
		if (!duration) {
			node.textContent = toFa(target.toFixed(decimals)) + suffix;
			return;
		}
		var start = null;
		function frame(timestamp) {
			if (!start) { start = timestamp; }
			var progress = Math.min(1, (timestamp - start) / duration);
			var eased = 1 - Math.pow(1 - progress, 3);
			node.textContent = toFa((target * eased).toFixed(decimals)) + suffix;
			if (progress < 1) { window.requestAnimationFrame(frame); }
		}
		window.requestAnimationFrame(frame);
	}

	function initCounters() {
		var counters = qsa('[data-count-to]');
		if (!counters.length || !window.IntersectionObserver) {
			counters.forEach(animateCounter);
			return;
		}
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) { return; }
				animateCounter(entry.target);
				observer.unobserve(entry.target);
			});
		}, { threshold: 0.4 });
		counters.forEach(function (node) { observer.observe(node); });
	}

	function initJourney() {
		qsa('[data-pixva-journey]').forEach(function (journey) {
			if (reduceMotion() || !window.IntersectionObserver) { return; }
			var steps = qsa('.pixva-journey__step', journey);
			if (!steps.length) { return; }
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) { return; }
					observer.unobserve(entry.target);
					steps.forEach(function (step, index) {
						window.setTimeout(function () { step.classList.add('is-lit'); }, index * 220);
					});
				});
			}, { threshold: 0.3 });
			observer.observe(journey);
		});
	}

	/* ==================================================================
	   ۱۵) کارت QR گارانتی (کتابخانه qrcode-generator)
	   ================================================================== */
	function initQR() {
		var cards = qsa('[data-pixva-qr]');
		if (!cards.length) { return; }
		if (typeof window.qrcode !== 'function') { return; }

		cards.forEach(function (card) {
			var value = card.getAttribute('data-qr-value');
			var target = qs('[data-qr-target]', card);
			if (!value || !target) { return; }
			try {
				var qr = window.qrcode(0, 'M');
				qr.addData(value);
				qr.make();
				target.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 1, scalable: true });
				card.classList.add('is-ready');
			} catch (error) {
				target.hidden = true;
			}
		});
	}

	/* ==================================================================
	   ۱۶) راه‌اندازی
	   ================================================================== */
	function boot() {
		initAIStudio();
		initAIFloating();
		initTVSimulator();
		initRGBTester();
		initBlinkTester();
		initAudioTester();
		initAudioAnalyzer();
		initRepairVsBuy();
		initSizeSlider();
		initCostBreakdown();
		initStockChecker();
		initTimeline();
		initWarrantyCard();
		initReportExport();
		initRequestForms();
		initEtaFinder();
		initTransportFee();
		initPowerSaver();
		initViewingDistance();
		initBacklightLife();
		initTradeIn();
		initProtector();
		initStabilizer();
		initShippingCalc();
		initReminderCalc();
		initInterference();
		initCableCheck();
		initChecklists();
		initTabs();
		initHighlightStacks();
		initVirtualTour();
		initPatternCanvases();
		initCameraTools();
		initVoiceSearch();
		initOfflineBanner();
		initPwaInstall();
		initBranchSwitcher();
		initSubscription();
		initDockPreview();
		initCounters();
		initJourney();
		initQR();
		document.documentElement.classList.add('pixva-tools-ready');
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaTools = {
		money: money,
		toFa: toFa,
		post: post,
		rest: rest,
		askAI: function (payload) { return post('pixva_ai_diagnose', cfg.nonce, payload); },
		redraw: boot
	};
})();
