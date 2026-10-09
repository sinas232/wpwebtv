/**
 * PIXVA core script (progressive enhancement; every feature works without it).
 *
 * - Mobile navigation disclosure.
 * - Provider-agnostic analytics: pixva.track(name, props) pushes
 *   {event: 'pixva_' + name, ...props} to window.dataLayer and dispatches a
 *   `pixva:track` CustomEvent. Event names and prop keys are allow-listed
 *   (PIXVA.events / PIXVA.props) and values are coerced to short scalars so no
 *   PII (names, phones, codes, free text) can leak (§39, §49).
 * - Declarative tracking attributes: data-track, data-track-submit,
 *   data-track-start, data-track-view (+ data-track-success / -fail on forms).
 * - AJAX forms ([data-pixva-form]) posting to admin-ajax.php with per-field
 *   errors, a focusable status region and duplicate-submit protection; the
 *   PRG admin-post flow remains the no-JS path.
 * - Conditional fields: data-show-when="field=value|value2".
 * - Header elevation state (.is-scrolled) and restrained scroll reveals
 *   (.reveal → .in-view via IntersectionObserver; skipped for reduced motion
 *   and when IntersectionObserver is unavailable — content never hides).
 */
(function () {
	'use strict';

	var cfg = window.PIXVA || {};
	var i18n = cfg.i18n || {};
	var allowedEvents = cfg.events || [];
	var allowedProps = cfg.props || [];

	/* ------------------------------------------------------------------ */
	/* Analytics                                                           */
	/* ------------------------------------------------------------------ */

	function cleanProps(props) {
		var out = {};
		Object.keys(props || {}).forEach(function (k) {
			if (allowedProps.indexOf(k) === -1) {
				return;
			}
			var v = props[k];
			if (typeof v === 'number' && isFinite(v)) {
				out[k] = v;
			} else if (typeof v === 'boolean') {
				out[k] = v;
			} else if (typeof v === 'string' && v !== '') {
				// Keys/slugs only: no spaces, digits-heavy strings or long text.
				var s = v.toLowerCase().replace(/[^a-z0-9_\-]/g, '').slice(0, 40);
				if (s !== '' && !/\d{5,}/.test(s)) {
					out[k] = s;
				}
			}
		});
		return out;
	}

	function track(name, props) {
		if (allowedEvents.indexOf(name) === -1) {
			return;
		}
		var payload = cleanProps(props);
		if (cfg.route) {
			payload.route = cleanProps({ route: cfg.route }).route;
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(Object.assign({ event: 'pixva_' + name }, payload));
		try {
			document.dispatchEvent(new CustomEvent('pixva:track', { detail: { name: name, props: payload } }));
		} catch (e) {
			/* old browsers: dataLayer push is enough */
		}
	}

	function dataProps(el) {
		return {
			label: el.getAttribute('data-track-label') || undefined,
			location: el.getAttribute('data-track-location') || undefined,
			problem: el.getAttribute('data-track-problem') || undefined
		};
	}

	/* ------------------------------------------------------------------ */
	/* Safe HTML insertion                                                 */
	/* ------------------------------------------------------------------ */
	/*
	 * Server fragments (order view, warranty view, estimate, error results)
	 * are inserted with an allow-list sanitizer instead of a raw innerHTML
	 * assignment. The HTML is parsed into an inert DOMParser document, every
	 * element outside SAFE_TAGS is unwrapped (or dropped with its content when
	 * it is executable/embedded), and every attribute outside SAFE_ATTRS is
	 * removed. URL attributes must use http(s), mailto, tel, fragment or
	 * relative forms. Server-side escaping remains the first line of defence;
	 * this is the second.
	 */
	var SAFE_TAGS = {
		a: 1, article: 1, b: 1, br: 1, circle: 1, details: 1, div: 1, em: 1, g: 1,
		h2: 1, h3: 1, header: 1, i: 1, img: 1, li: 1, line: 1, ol: 1, p: 1, path: 1,
		polygon: 1, polyline: 1, rect: 1, span: 1, strong: 1, summary: 1, svg: 1,
		time: 1, ul: 1
	};
	var DROP_TAGS = {
		base: 1, button: 1, embed: 1, form: 1, frame: 1, iframe: 1, input: 1, link: 1,
		meta: 1, noscript: 1, object: 1, script: 1, select: 1, style: 1, template: 1,
		textarea: 1, title: 1
	};
	var SAFE_ATTRS = {
		'aria-hidden': 1, 'clip-rule': 1, alt: 1, class: 1, cx: 1, cy: 1, d: 1,
		datetime: 1, dir: 1, fill: 1, 'fill-rule': 1, focusable: 1, height: 1, hidden: 1,
		href: 1, id: 1, lang: 1, loading: 1, points: 1, r: 1, role: 1, rx: 1, ry: 1,
		src: 1, stroke: 1, 'stroke-linecap': 1, 'stroke-linejoin': 1, 'stroke-width': 1,
		title: 1, viewbox: 1, width: 1, x: 1, x1: 1, x2: 1, y: 1, y1: 1, y2: 1
	};
	// Ids that would shadow or clobber browser/page globals (DOM clobbering).
	var RESERVED_IDS = {
		alert: 1, body: 1, cookie: 1, document: 1, eval: 1, fetch: 1, forms: 1, frames: 1,
		head: 1, history: 1, innerHTML: 1, location: 1, localStorage: 1, navigator: 1,
		opener: 1, parent: 1, pixva: 1, PIXVA: 1, sessionStorage: 1, self: 1, top: 1,
		window: 1, dataLayer: 1, console: 1
	};
	var SAFE_ID = /^[A-Za-z][A-Za-z0-9_-]{0,79}$/;

	function safeUrl(value) {
		// Strip whitespace and control characters that browsers ignore in schemes.
		var v = String(value).replace(/[\u0000-\u0020\u007f]/g, '');
		if (/^[a-z][a-z0-9+.-]*:/i.test(v)) {
			return /^(https?|mailto|tel):/i.test(v);
		}
		return true; // relative, fragment or protocol-relative path
	}

	function cleanAttributes(el, tag) {
		Array.prototype.slice.call(el.attributes).forEach(function (attr) {
			var name = attr.name.toLowerCase();
			var keep = SAFE_ATTRS[name] || /^(aria|data)-[a-z0-9_-]+$/.test(name);
			if (keep && name === 'href' && tag !== 'a') { keep = false; }
			if (keep && name === 'src' && tag !== 'img') { keep = false; }
			if (keep && (name === 'href' || name === 'src') && !safeUrl(attr.value)) { keep = false; }
			if (keep && name === 'id' && (!SAFE_ID.test(attr.value) || RESERVED_IDS[attr.value])) { keep = false; }
			if (!keep) { el.removeAttribute(attr.name); }
		});
	}

	function cleanNode(node) {
		Array.prototype.slice.call(node.childNodes).forEach(function (child) {
			if (child.nodeType === 3) { return; } // text is inert
			if (child.nodeType !== 1) { node.removeChild(child); return; } // comments, PIs
			var tag = String(child.localName || child.nodeName).toLowerCase();
			if (DROP_TAGS[tag]) { node.removeChild(child); return; }
			if (!SAFE_TAGS[tag]) {
				cleanNode(child);
				while (child.firstChild) { node.insertBefore(child.firstChild, child); }
				node.removeChild(child);
				return;
			}
			cleanAttributes(child, tag);
			cleanNode(child);
		});
	}

	/* Sanitise a node that is already in an inert document (e.g. DOMParser), then move its children. */
	function setSafeFromNode(target, source) {
		if (!target || !source) { return; }
		cleanNode(source);
		var frag = document.createDocumentFragment();
		while (source.firstChild) { frag.appendChild(source.firstChild); }
		target.textContent = '';
		target.appendChild(frag);
	}

	function setSafeHTML(target, html) {
		if (!target) { return; }
		var parsed = new DOMParser().parseFromString('<!doctype html><body>' + String(html == null ? '' : html), 'text/html');
		cleanNode(parsed.body);
		var frag = document.createDocumentFragment();
		while (parsed.body.firstChild) { frag.appendChild(parsed.body.firstChild); }
		target.textContent = '';
		target.appendChild(frag);
	}

	var pixva = (window.pixva = window.pixva || {});
	pixva.track = track;
	pixva.setSafeHTML = setSafeHTML;
	pixva.setSafeFromNode = setSafeFromNode;

	(cfg.pending || []).forEach(function (e) {
		track(e.name, e.props);
	});

	document.querySelectorAll('[data-track-view]').forEach(function (el) {
		track(el.getAttribute('data-track-view'), dataProps(el));
	});

	document.addEventListener('click', function (ev) {
		var el = ev.target.closest ? ev.target.closest('[data-track]') : null;
		if (el) {
			track(el.getAttribute('data-track'), dataProps(el));
		}
	});

	document.addEventListener('submit', function (ev) {
		var f = ev.target;
		if (f && f.hasAttribute && f.hasAttribute('data-track-submit')) {
			var props = dataProps(f);
			var problem = f.querySelector('[name="problem"]');
			if (problem && problem.value) {
				props.problem = problem.value;
			}
			track(f.getAttribute('data-track-submit'), props);
		}
	});

	document.querySelectorAll('[data-track-start]').forEach(function (f) {
		var once = function () {
			f.removeEventListener('focusin', once);
			track(f.getAttribute('data-track-start'), dataProps(f));
		};
		f.addEventListener('focusin', once);
	});

	/* ------------------------------------------------------------------ */
	/* Navigation                                                          */
	/* ------------------------------------------------------------------ */

	var toggle = document.querySelector('.nav-toggle');
	var nav = document.getElementById('site-nav');
	if (toggle && nav) {
		toggle.hidden = false;
		var setOpen = function (open) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			nav.classList.toggle('is-open', open);
			var sr = toggle.querySelector('.screen-reader-text');
			if (sr) {
				sr.textContent = open ? (i18n.close || '') : (i18n.menu || '');
			}
		};
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') !== 'true';
			setOpen(open);
			if (open) {
				var first = nav.querySelector('a[href]');
				if (first) {
					first.focus();
				}
			}
		});
		document.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
				setOpen(false);
				toggle.focus();
			}
		});
	}

	/* ------------------------------------------------------------------ */
	/* Header elevation on scroll                                          */
	/* ------------------------------------------------------------------ */

	var header = document.querySelector('.site-header');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 4);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	/* Mobile sticky CTA: shown once the first screen has been scrolled past. */
	if (document.querySelector('.m-cta')) {
		var onMobileCta = function () {
			document.documentElement.classList.toggle('m-cta-on', window.scrollY > window.innerHeight * 0.6);
		};
		window.addEventListener('scroll', onMobileCta, { passive: true });
		onMobileCta();
	}

	/* ------------------------------------------------------------------ */
	/* Scroll reveal (progressive enhancement; content is visible without  */
	/* JS and with reduced motion — .reveal only hides under .js + motion). */
	/* ------------------------------------------------------------------ */

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var revealEls = document.querySelectorAll('.reveal');
	if (!reduceMotion && 'IntersectionObserver' in window && revealEls.length) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('in-view');
					io.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		revealEls.forEach(function (el) {
			io.observe(el);
		});
	} else {
		revealEls.forEach(function (el) {
			el.classList.add('in-view');
		});
	}

	/* ------------------------------------------------------------------ */
	/* Helpers shared with feature scripts                                 */
	/* ------------------------------------------------------------------ */

	function el(tag, attrs, text) {
		var n = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			n.setAttribute(k, attrs[k]);
		});
		if (text !== undefined && text !== null) {
			n.textContent = String(text);
		}
		return n;
	}

	/** Notice element mirroring pixva_notice() (text only, never HTML). */
	function notice(type, message, live) {
		var box = el('div', { class: 'notice notice--' + type });
		if (live) {
			box.setAttribute('role', type === 'error' ? 'alert' : 'status');
		}
		var body = el('div');
		body.appendChild(el('p', null, message));
		box.appendChild(body);
		return box;
	}

	function setBusy(form, busy) {
		form.setAttribute('aria-busy', busy ? 'true' : 'false');
		form.querySelectorAll('[type="submit"], [data-submit]').forEach(function (b) {
			b.disabled = busy;
			b.setAttribute('aria-busy', busy ? 'true' : 'false');
			if (busy) {
				b.dataset.label = b.dataset.label || b.textContent;
				b.textContent = i18n.sending || b.textContent;
			} else if (b.dataset.label) {
				b.textContent = b.dataset.label;
			}
		});
	}

	function fieldWrap(form, key) {
		return form.querySelector('[data-field="' + key + '"]');
	}

	function clearErrors(form) {
		form.querySelectorAll('.field--error').forEach(function (w) {
			w.classList.remove('field--error');
		});
		form.querySelectorAll('.field__error').forEach(function (p) {
			p.textContent = '';
			p.hidden = true;
		});
		form.querySelectorAll('[aria-invalid="true"]').forEach(function (c) {
			c.removeAttribute('aria-invalid');
		});
		var st = form.querySelector('[data-form-status]');
		if (st) {
			st.textContent = '';
		}
	}

	function showFieldError(form, key, message) {
		var wrap = fieldWrap(form, key);
		var err = wrap ? wrap.querySelector('.field__error') : form.querySelector('#f-' + key + '-err');
		var controls = form.querySelectorAll('[name="' + key + '"], [name="' + key + '[]"]');
		if (wrap) {
			wrap.classList.add('field--error');
		}
		if (err) {
			err.textContent = message;
			err.hidden = false;
		}
		controls.forEach(function (c) {
			c.setAttribute('aria-invalid', 'true');
			if (err && err.id) {
				var d = (c.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
				if (d.indexOf(err.id) === -1) {
					d.push(err.id);
					c.setAttribute('aria-describedby', d.join(' '));
				}
			}
		});
		return controls[0] || null;
	}

	function showStatus(form, type, message) {
		var st = form.querySelector('[data-form-status]');
		if (!st) {
			return;
		}
		st.textContent = '';
		st.appendChild(notice(type, message, true));
		st.focus({ preventScroll: false });
	}

	pixva.el = el;
	pixva.notice = notice;
	pixva.setBusy = setBusy;

	/* ------------------------------------------------------------------ */
	/* Conditional fields                                                  */
	/* ------------------------------------------------------------------ */

	function fieldValue(form, name) {
		var nodes = form.querySelectorAll('[name="' + name + '"]');
		var val = '';
		nodes.forEach(function (n) {
			if ((n.type === 'radio' || n.type === 'checkbox') && !n.checked) {
				return;
			}
			val = n.value;
		});
		return val;
	}

	function applyShowWhen(form) {
		form.querySelectorAll('[data-show-when]').forEach(function (ctrl) {
			var rule = ctrl.getAttribute('data-show-when').split('=');
			var values = (rule[1] || '').split('|');
			var show = values.indexOf(fieldValue(form, rule[0])) !== -1;
			var wrap = ctrl.closest('.field') || ctrl;
			wrap.hidden = !show;
		});
	}

	document.querySelectorAll('form').forEach(function (form) {
		if (!form.querySelector('[data-show-when]')) {
			return;
		}
		applyShowWhen(form);
		form.addEventListener('change', function () {
			applyShowWhen(form);
		});
	});

	/* ------------------------------------------------------------------ */
	/* AJAX forms                                                          */
	/* ------------------------------------------------------------------ */

	function clientValidate(form) {
		var first = null;
		var seen = {};
		form.querySelectorAll('[required]').forEach(function (c) {
			var wrap = c.closest('.field');
			if (c.disabled || (wrap && wrap.hidden) || seen[c.name]) {
				return;
			}
			seen[c.name] = true;
			var empty;
			if (c.type === 'radio') {
				empty = !form.querySelector('[name="' + c.name + '"]:checked');
			} else if (c.type === 'checkbox') {
				empty = !c.checked;
			} else {
				empty = c.value.trim() === '';
			}
			if (empty) {
				var ctrl = showFieldError(form, c.name.replace('[]', ''), i18n.required || '');
				first = first || ctrl;
			}
		});
		return first;
	}

	function successPanel(form, data) {
		var box = el('div', { class: 'success', tabindex: '-1' });
		box.appendChild(el('h2', { class: 'success__title' }, data.message || ''));
		if (data.code) {
			box.appendChild(el('p', { class: 'code code--lg', dir: 'ltr' }, data.code));
			if (form.getAttribute('data-code-help')) {
				box.appendChild(el('p', null, form.getAttribute('data-code-help')));
			}
		}
		if (data.tracking_url) {
			var actions = el('p', { class: 'form__actions' });
			actions.appendChild(el('a', { class: 'btn btn--primary', href: data.tracking_url }, form.getAttribute('data-tracking-label') || ''));
			box.appendChild(actions);
		}
		return box;
	}

	function bindAjaxForm(form) {
		if (!window.fetch || !window.FormData || !cfg.ajax) {
			return;
		}
		var sending = false;
		form.addEventListener('submit', function (ev) {
			if (form.dataset.native === '1') {
				return; // fallback re-submit
			}
			ev.preventDefault();
			if (sending) {
				return; // duplicate-submit protection
			}
			clearErrors(form);
			var invalid = clientValidate(form);
			if (invalid) {
				showStatus(form, 'error', i18n.fixErrors || '');
				invalid.focus();
				return;
			}
			sending = true;
			setBusy(form, true);
			var fd = new FormData(form);
			fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
				.then(function (res) {
					return res.text().then(function (txt) {
						var json = null;
						try {
							json = JSON.parse(txt);
						} catch (e) {
							json = null;
						}
						return { status: res.status, json: json };
					});
				})
				.then(function (r) {
					if (!r.json || typeof r.json.success !== 'boolean') {
						// Unexpected (e.g. security plugin, expired session): use the no-JS path.
						form.dataset.native = '1';
						HTMLFormElement.prototype.submit.call(form);
						return;
					}
					var data = r.json.data || {};
					if (r.json.success) {
						if (form.getAttribute('data-track-success')) {
							track(form.getAttribute('data-track-success'), { result: 'ok', has_photos: !!form.querySelector('input[type="file"]') && Array.prototype.some.call(form.querySelectorAll('input[type="file"]'), function (f) { return f.files && f.files.length > 0; }) });
						}
						if (data.redirect) {
							window.location.assign(data.redirect);
							return;
						}
						if (form.hasAttribute('data-reload-on-success')) {
							window.location.reload();
							return;
						}
						if (data.code || form.hasAttribute('data-replace-on-success')) {
							var panel = successPanel(form, data);
							form.replaceWith(panel);
							panel.focus();
							return;
						}
						showStatus(form, 'success', data.message || '');
						return;
					}
					var errors = data.errors || {};
					var first = null;
					Object.keys(errors).forEach(function (k) {
						if (k !== '_form') {
							var c = showFieldError(form, k, errors[k]);
							first = first || c;
						}
					});
					showStatus(form, 'error', data.message || i18n.fixErrors || '');
					if (form.getAttribute('data-track-fail')) {
						track(form.getAttribute('data-track-fail'), { reason: r.status === 429 ? 'rate_limited' : (first ? 'validation' : 'server'), status: r.status });
					}
					if (first) {
						first.focus();
					}
				})
				.catch(function () {
					showStatus(form, 'error', i18n.error || '');
					if (form.getAttribute('data-track-fail')) {
						track(form.getAttribute('data-track-fail'), { reason: 'network' });
					}
				})
				.then(function () {
					sending = false;
					if (document.body.contains(form)) {
						setBusy(form, false);
					}
				});
		});
	}

	document.querySelectorAll('form[data-pixva-form]').forEach(bindAjaxForm);
}());
