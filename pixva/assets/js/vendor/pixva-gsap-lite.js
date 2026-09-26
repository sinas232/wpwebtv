/**
 * pixva-gsap-lite — موتور تویین/اسکرول سبک با API سازگار با زیرمجموعه GSAP.
 *
 * چرا این فایل؟ قالب پیکسوا باید بدون وابستگی بیرونی کار کند. اگر مدیر سایت نسخه
 * رسمی GSAP را در assets/js/vendor/gsap.min.js (و ScrollTrigger.min.js) بگذارد یا
 * آدرس CDN را در سفارشی‌ساز تنظیم کند، قالب همان را بارگذاری می‌کند و این موتور
 * اصلاً صف نمی‌شود (pixva_cinematic_libs در inc/cinematic.php).
 *
 * زیرمجموعه پشتیبانی‌شده:
 *   gsap.to / from / fromTo / set / timeline / registerPlugin / delayedCall / utils
 *   ScrollTrigger.create({ trigger, start, end, pin, scrub, animation, onUpdate,
 *                          onEnter, onLeave, onEnterBack, onLeaveBack })
 *   ScrollTrigger.refresh / update / getAll / killAll
 *
 * همه تغییرات فقط با transform/opacity انجام می‌شود (GPU) تا افت فریم نداشته باشیم.
 *
 * @package Pixva
 * @since   1.6.0
 * @license GPL-2.0-or-later
 */
(function (window, document) {
	'use strict';

	if (window.gsap && !window.gsap.__pixvaLite) {
		return; // نسخه رسمی GSAP حاضر است؛ موتور سبک جای آن را نمی‌گیرد.
	}

	var TRANSFORM_KEYS = {
		x: 1, y: 1, z: 1,
		scale: 1, scaleX: 1, scaleY: 1, scaleZ: 1,
		rotation: 1, rotationX: 1, rotationY: 1, rotationZ: 1,
		skewX: 1, skewY: 1
	};

	var RESERVED = {
		duration: 1, delay: 1, ease: 1, paused: 1, immediateRender: 1,
		onUpdate: 1, onComplete: 1, onStart: 1, overwrite: 1, stagger: 1,
		scrollTrigger: 1, repeat: 1, yoyo: 1, transformPerspective: 1
	};

	var states = new WeakMap();
	var rafId = null;
	var active = [];

	function stateOf(el) {
		var state = states.get(el);
		if (!state) {
			state = {
				x: 0, y: 0, z: 0,
				scaleX: 1, scaleY: 1, scaleZ: 1,
				rotation: 0, rotationX: 0, rotationY: 0, rotationZ: 0,
				skewX: 0, skewY: 0,
				opacity: null,
				css: {}
			};
			states.set(el, state);
		}
		return state;
	}

	function readState(el, key) {
		var state = stateOf(el);
		if ('opacity' === key) {
			return null === state.opacity ? parseFloat(window.getComputedStyle(el).opacity || '1') : state.opacity;
		}
		if (TRANSFORM_KEYS[key]) {
			if ('scale' === key) { return state.scaleX; }
			var value = state[key];
			return (undefined === value || null === value) ? 0 : value;
		}
		if (state.css[key]) { return parseFloat(state.css[key]) || 0; }
		return parseFloat(window.getComputedStyle(el)[key]) || 0;
	}

	function unitOf(value, fallback) {
		var unit = String(value).replace(/[-+0-9.]/g, '');
		return '' === unit ? (fallback || 'px') : unit;
	}

	function applyState(el) {
		var state = stateOf(el);
		var parts = [];

		if (state.x || state.y || state.z) {
			parts.push('translate3d(' + round2(state.x) + 'px,' + round2(state.y) + 'px,' + round2(state.z) + 'px)');
		}
		if (state.rotationX) { parts.push('rotateX(' + round2(state.rotationX) + 'deg)'); }
		if (state.rotationY) { parts.push('rotateY(' + round2(state.rotationY) + 'deg)'); }
		if (state.rotationZ || state.rotation) { parts.push('rotate(' + round2((state.rotationZ || 0) + (state.rotation || 0)) + 'deg)'); }
		if (state.skewX || state.skewY) { parts.push('skew(' + round2(state.skewX) + 'deg,' + round2(state.skewY) + 'deg)'); }
		if (1 !== state.scaleX || 1 !== state.scaleY || 1 !== state.scaleZ) {
			parts.push('scale3d(' + round3(state.scaleX) + ',' + round3(state.scaleY) + ',' + round3(state.scaleZ) + ')');
		}

		el.style.transform = parts.length ? parts.join(' ') : 'translate3d(0,0,0)';
		el.style.backfaceVisibility = 'hidden';

		if (null !== state.opacity) {
			el.style.opacity = round3(state.opacity);
			el.style.visibility = state.opacity < 0.005 ? 'hidden' : '';
		}

		Object.keys(state.css).forEach(function (key) {
			el.style[key] = state.css[key];
		});
	}

	function round2(v) { return Math.round(v * 100) / 100; }
	function round3(v) { return Math.round(v * 1000) / 1000; }

	/* ------------------------------ easing ------------------------------ */
	var EASES = {
		none: function (t) { return t; },
		linear: function (t) { return t; },
		'power1.in': function (t) { return t * t; },
		'power1.out': function (t) { return 1 - Math.pow(1 - t, 2); },
		'power1.inOut': function (t) { return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; },
		'power2.in': function (t) { return t * t * t; },
		'power2.out': function (t) { return 1 - Math.pow(1 - t, 3); },
		'power2.inOut': function (t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; },
		'power3.in': function (t) { return Math.pow(t, 4); },
		'power3.out': function (t) { return 1 - Math.pow(1 - t, 4); },
		'power3.inOut': function (t) { return t < 0.5 ? 8 * Math.pow(t, 4) : 1 - Math.pow(-2 * t + 2, 4) / 2; },
		'power4.out': function (t) { return 1 - Math.pow(1 - t, 5); },
		'sine.in': function (t) { return 1 - Math.cos((t * Math.PI) / 2); },
		'sine.out': function (t) { return Math.sin((t * Math.PI) / 2); },
		'sine.inOut': function (t) { return -(Math.cos(Math.PI * t) - 1) / 2; },
		'expo.out': function (t) { return 1 === t ? 1 : 1 - Math.pow(2, -10 * t); },
		'expo.inOut': function (t) { return t < 0.5 ? Math.pow(2, 20 * t - 10) / 2 : (2 - Math.pow(2, -20 * t + 10)) / 2; },
		'circ.out': function (t) { return Math.sqrt(1 - Math.pow(t - 1, 2)); },
		'back.out': function (t) { var c = 2.70158; return 1 + c * Math.pow(t - 1, 3) + 1.70158 * Math.pow(t - 1, 2); }
	};

	function easeOf(name) {
		if ('function' === typeof name) { return name; }
		return EASES[name] || EASES['power2.out'];
	}

	function toArray(target) {
		if (!target) { return []; }
		if ('string' === typeof target) { return Array.prototype.slice.call(document.querySelectorAll(target)); }
		if (target.nodeType) { return [target]; }
		if ('length' in target && !target.window) { return Array.prototype.slice.call(target); }
		return [target];
	}

	function parseVars(vars) {
		var out = {
			duration: undefined === vars.duration ? 0.6 : Math.max(0, parseFloat(vars.duration)),
			delay: parseFloat(vars.delay || 0),
			ease: easeOf(vars.ease),
			transform: {},
			opacity: null,
			css: {},
			units: {},
			onUpdate: vars.onUpdate || null,
			onComplete: vars.onComplete || null,
			onStart: vars.onStart || null
		};

		Object.keys(vars || {}).forEach(function (key) {
			if (RESERVED[key]) { return; }
			var value = vars[key];
			if ('opacity' === key || 'autoAlpha' === key) {
				out.opacity = parseFloat(value);
			} else if (TRANSFORM_KEYS[key]) {
				out.transform[key] = parseFloat(value);
				if ('scale' === key) {
					out.transform.scaleX = parseFloat(value);
					out.transform.scaleY = parseFloat(value);
					out.transform.scaleZ = parseFloat(value);
				}
			} else {
				out.css[key] = value;
				out.units[key] = unitOf(value, '');
			}
		});

		return out;
	}

	/* ------------------------------ ticker ------------------------------ */
	function tick(now) {
		rafId = null;
		var i = active.length;
		while (i--) {
			var item = active[i];
			item.render(now);
			if (item.done) { active.splice(i, 1); }
		}
		if (active.length) { rafId = window.requestAnimationFrame(tick); }
	}

	function wake(item) {
		if (active.indexOf(item) === -1) { active.push(item); }
		if (!rafId) { rafId = window.requestAnimationFrame(tick); }
	}

	/* ------------------------------- tween ------------------------------ */
	/**
	 * @param {Array} targets   عناصر.
	 * @param {Array} startList مقدار شروع هر عنصر.
	 * @param {Array} endList   مقدار پایان هر عنصر.
	 * @param {object} meta     duration/delay/ease/callbacks.
	 * @param {boolean} lazyStart اگر true، مقدار شروع در اولین رندر خوانده می‌شود.
	 */
	function Tween(targets, startList, endList, meta, lazyStart) {
		this.targets = targets;
		this.startList = startList;
		this.endList = endList;
		this.meta = meta;
		this.lazyStart = !!lazyStart;
		this.duration = meta.duration;
		this.progressValue = 0;
		this.done = false;
		this.paused = false;
		this.startedAt = null;
		this.begun = false;
	}

	Tween.prototype.captureStarts = function () {
		var self = this;
		this.startList = this.targets.map(function (el) {
			var snap = {};
			Object.keys(self.meta.transform).forEach(function (key) { snap[key] = readState(el, key); });
			if (null !== self.meta.opacity) { snap.opacity = readState(el, 'opacity'); }
			Object.keys(self.meta.css).forEach(function (key) { snap[key] = readState(el, key); });
			return snap;
		});
		this.lazyStart = false;
	};

	Tween.prototype.seek = function (p) {
		if (this.lazyStart) { this.captureStarts(); }
		this.progressValue = Math.max(0, Math.min(1, p));
		var t = this.meta.ease(this.progressValue);
		var meta = this.meta;

		this.targets.forEach(function (el, index) {
			var state = stateOf(el);
			var start = this.startList[index] || {};
			var end = this.endList[index] || {};

			Object.keys(meta.transform).forEach(function (key) {
				var from = undefined === start[key] ? 0 : start[key];
				var to = undefined === end[key] ? from : end[key];
				state[key] = from + (to - from) * t;
			});

			if (null !== meta.opacity) {
				var fromOp = undefined === start.opacity ? 1 : start.opacity;
				var toOp = undefined === end.opacity ? meta.opacity : end.opacity;
				state.opacity = fromOp + (toOp - fromOp) * t;
			}

			Object.keys(meta.css).forEach(function (key) {
				var fromCss = undefined === start[key] ? 0 : start[key];
				var toCss = undefined === end[key] ? parseFloat(meta.css[key]) || 0 : end[key];
				var unit = meta.units[key] || '';
				state.css[key] = round3(fromCss + (toCss - fromCss) * t) + unit;
			});

			applyState(el);
		}.bind(this));

		if (meta.onUpdate) { meta.onUpdate(); }
		return this;
	};

	Tween.prototype.render = function (now) {
		if (this.paused) { return; }
		if (null === this.startedAt) { this.startedAt = now; }
		var elapsed = (now - this.startedAt) / 1000 - this.meta.delay;
		if (elapsed < 0) { return; }
		if (!this.begun) {
			this.begun = true;
			if (this.meta.onStart) { this.meta.onStart(); }
		}
		if (0 === this.duration) {
			this.seek(1);
			this.finish();
			return;
		}
		var p = elapsed / this.duration;
		this.seek(p);
		if (p >= 1) { this.finish(); }
	};

	Tween.prototype.finish = function () {
		if (this.done) { return; }
		this.done = true;
		this.progressValue = 1;
		if (this.meta.onComplete) { this.meta.onComplete(); }
	};

	Tween.prototype.progress = function (value) {
		if (undefined === value) { return this.progressValue; }
		this.paused = true;
		this.done = false;
		return this.seek(value);
	};
	Tween.prototype.pause = function () { this.paused = true; return this; };
	Tween.prototype.resume = function () { this.paused = false; this.startedAt = null; wake(this); return this; };
	Tween.prototype.play = function () { return this.resume(); };
	Tween.prototype.restart = function () { this.progressValue = 0; this.begun = false; return this.resume(); };
	Tween.prototype.kill = function () {
		this.done = true;
		var i = active.indexOf(this);
		if (i > -1) { active.splice(i, 1); }
		return this;
	};
	Tween.prototype.totalDuration = function () { return this.meta.delay + this.duration; };

	/* ------------------------------ helpers ----------------------------- */
	function endsFromVars(targets, parsed) {
		return targets.map(function () {
			var end = {};
			Object.keys(parsed.transform).forEach(function (key) { end[key] = parsed.transform[key]; });
			if (null !== parsed.opacity) { end.opacity = parsed.opacity; }
			Object.keys(parsed.css).forEach(function (key) { end[key] = parseFloat(parsed.css[key]) || 0; });
			return end;
		});
	}

	function startsFromState(targets, parsed) {
		return targets.map(function (el) {
			var start = {};
			Object.keys(parsed.transform).forEach(function (key) { start[key] = readState(el, key); });
			if (null !== parsed.opacity) { start.opacity = readState(el, 'opacity'); }
			Object.keys(parsed.css).forEach(function (key) { start[key] = readState(el, key); });
			return start;
		});
	}

	function startsFromVars(targets, parsed) {
		return targets.map(function () {
			var start = {};
			Object.keys(parsed.transform).forEach(function (key) { start[key] = parsed.transform[key]; });
			if (null !== parsed.opacity) { start.opacity = parsed.opacity; }
			Object.keys(parsed.css).forEach(function (key) { start[key] = parseFloat(parsed.css[key]) || 0; });
			return start;
		});
	}

	/* ------------------------------ timeline ---------------------------- */
	function Timeline(vars) {
		this.vars = vars || {};
		this.children = [];
		this.position = 0;
		this.durationTotal = 0;
		this.paused = !!(this.vars.paused);
		this.progressValue = 0;
		this.done = false;
		this.startedAt = null;
	}

	Timeline.prototype.add = function (child, position) {
		var at = 'number' === typeof position ? position : ('string' === typeof position ? this.position : this.position);
		var duration = 'function' === typeof child.totalDuration ? child.totalDuration() : (child.duration || 0);
		this.children.push({ child: child, start: at, duration: duration });
		this.durationTotal = Math.max(this.durationTotal, at + duration);
		this.position = at + duration;
		return this;
	};

	Timeline.prototype.to = function (targets, vars, position) {
		var list = toArray(targets);
		var parsed = parseVars(vars);
		var tween = new Tween(list, startsFromState(list, parsed), endsFromVars(list, parsed), parsed, false);
		tween.paused = true;
		return this.add(tween, position);
	};

	Timeline.prototype.from = function (targets, vars, position) {
		var list = toArray(targets);
		var parsed = parseVars(vars);
		var starts = startsFromVars(list, parsed);
		var ends = startsFromState(list, parsed);
		var tween = new Tween(list, starts, ends, parsed, false);
		tween.paused = true;
		tween.seek(0); // وضعیت اولیه = مقدار from.
		return this.add(tween, position);
	};

	Timeline.prototype.fromTo = function (targets, fromVars, toVars, position) {
		var list = toArray(targets);
		var fromParsed = parseVars(fromVars);
		var parsed = parseVars(toVars);
		var tween = new Tween(list, startsFromVars(list, fromParsed), endsFromVars(list, parsed), parsed, false);
		tween.paused = true;
		tween.seek(0);
		return this.add(tween, position);
	};

	Timeline.prototype.set = function (targets, vars, position) {
		var next = Object.assign({}, vars, { duration: 0 });
		return this.to(targets, next, position);
	};

	Timeline.prototype.addLabel = function () { return this; };

	Timeline.prototype.seek = function (p) {
		this.progressValue = Math.max(0, Math.min(1, p));
		var time = this.progressValue * this.durationTotal;
		this.children.forEach(function (item) {
			var local = 0 === item.duration ? (time >= item.start ? 1 : 0) : (time - item.start) / item.duration;
			item.child.progress(Math.max(0, Math.min(1, local)));
		});
		return this;
	};

	Timeline.prototype.progress = function (value) {
		if (undefined === value) { return this.progressValue; }
		this.paused = true;
		this.done = false;
		return this.seek(value);
	};

	Timeline.prototype.duration = function (value) {
		if (undefined === value) { return this.durationTotal; }
		this.durationTotal = value;
		return this;
	};

	Timeline.prototype.totalDuration = function () { return this.durationTotal + parseFloat(this.vars.delay || 0); };

	Timeline.prototype.render = function (now) {
		if (this.paused) { return; }
		if (null === this.startedAt) { this.startedAt = now; }
		var elapsed = (now - this.startedAt) / 1000 - parseFloat(this.vars.delay || 0);
		if (elapsed < 0) { return; }
		if (0 === this.durationTotal) { this.seek(1); this.done = true; return; }
		var p = elapsed / this.durationTotal;
		this.seek(p);
		if (p >= 1) {
			this.done = true;
			if (this.vars.onComplete) { this.vars.onComplete(); }
		}
	};

	Timeline.prototype.pause = function () { this.paused = true; return this; };
	Timeline.prototype.play = function () { this.paused = false; this.done = false; this.startedAt = null; wake(this); return this; };
	Timeline.prototype.restart = function () { this.progressValue = 0; return this.play(); };
	Timeline.prototype.kill = function () {
		this.done = true;
		var i = active.indexOf(this);
		if (i > -1) { active.splice(i, 1); }
		return this;
	};

	/* --------------------------- ScrollTrigger -------------------------- */
	var triggers = [];
	var pinned = new WeakMap();
	var scrollY = 0;
	var viewport = 600;

	function measure() {
		scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
		viewport = window.innerHeight || 600;
	}

	function anchorOffset(name, height) {
		if ('center' === name) { return height / 2; }
		if ('bottom' === name) { return height; }
		return 0;
	}

	function parsePosition(value, trigger, selfStart, pxMode) {
		var text = String(undefined === value || null === value ? '' : value).trim();

		if ('number' === typeof value) { return value; }

		if (0 === text.indexOf('+=')) {
			return selfStart + parseFloat(text.slice(2)) * (pxMode ? 1 : viewport / 100);
		}
		if (0 === text.indexOf('-=')) {
			return selfStart - parseFloat(text.slice(2)) * (pxMode ? 1 : viewport / 100);
		}

		var parts = text.split(/\s+/);
		var anchor = parts[0] || 'top';
		var reference = parts[1] || 'top';
		var el = trigger || document.body;
		var rect = el.getBoundingClientRect();
		var top = rect.top + scrollY;

		return top + anchorOffset(anchor, el.offsetHeight || rect.height) - anchorOffset(reference, viewport);
	}

	function Trigger(config) {
		this.config = config || {};
		this.trigger = toArray(this.config.trigger)[0] || null;
		this.animation = this.config.animation || null;
		this.scrub = !!this.config.scrub;
		this.pin = this.config.pin ? (true === this.config.pin ? this.trigger : toArray(this.config.pin)[0]) : null;
		this.onUpdate = this.config.onUpdate || null;
		this.onEnter = this.config.onEnter || null;
		this.onLeave = this.config.onLeave || null;
		this.onEnterBack = this.config.onEnterBack || null;
		this.onLeaveBack = this.config.onLeaveBack || null;
		this.start = 0;
		this.end = 0;
		this.progress = 0;
		this.direction = 1;
		this.isActive = false;
		this.lastProgress = 0;

		this.refresh();
		triggers.push(this);
	}

	Trigger.prototype.refresh = function () {
		measure();
		this.start = parsePosition(this.config.start || 'top top', this.trigger, 0, true);

		var endValue = this.config.end;
		if (undefined === endValue || null === endValue) {
			this.end = this.start + viewport;
		} else {
			this.end = parsePosition(endValue, this.trigger, this.start, 0 === String(endValue).indexOf('+='));
		}

		if (this.end <= this.start) { this.end = this.start + Math.max(1, viewport); }
		if (this.pin && !pinned.get(this.pin)) { this.createPin(); }
		if (this.pin && pinned.get(this.pin)) { this.syncPin(); }
	};

	Trigger.prototype.createPin = function () {
		var el = this.pin;
		if (!el || !el.parentNode) { return; }
		if (el.parentNode.classList && el.parentNode.classList.contains('pixva-pin-spacer')) { return; }

		var rect = el.getBoundingClientRect();
		var spacer = document.createElement('div');
		spacer.className = 'pixva-pin-spacer';

		// همانند GSAP رسمی، عنصر داخل spacer می‌نشیند تا ارتفاع سند درست بماند.
		el.parentNode.insertBefore(spacer, el);
		spacer.appendChild(el);

		var data = {
			spacer: spacer,
			width: Math.round(rect.width),
			left: rect.left,
			height: Math.round(rect.height),
			distance: Math.max(0, Math.round(this.end - this.start))
		};
		spacer.style.height = (data.height + data.distance) + 'px';
		pinned.set(el, data);
	};

	/**
	 * به‌روزرسانی ارتفاع spacer پس از تغییر اندازه صفحه (معادل pinSpacing).
	 */
	Trigger.prototype.syncPin = function () {
		var el = this.pin;
		var data = el ? pinned.get(el) : null;
		if (!data) { return; }

		var rect = el.getBoundingClientRect();
		data.distance = Math.max(0, Math.round(this.end - this.start));
		data.width = Math.round(rect.width) || data.width;
		if ('1' !== el.dataset.pixvaPinned) { data.height = Math.round(rect.height) || data.height; }
		data.spacer.style.height = (data.height + data.distance) + 'px';
	};

	Trigger.prototype.setPinned = function (on) {
		var el = this.pin;
		if (!el) { return; }
		var data = pinned.get(el);
		if (!data) { return; }

		if (on && '1' !== el.dataset.pixvaPinned) {
			var rect = el.getBoundingClientRect();
			data.height = Math.round(rect.height);
			data.width = Math.round(rect.width);

			el.dataset.pixvaPinned = '1';
			el.style.position = 'fixed';
			el.style.top = Math.max(0, Math.round(rect.top)) + 'px';
			el.style.bottom = 'auto';
			el.style.width = data.width + 'px';
			el.style.maxWidth = '100vw';
			el.style.zIndex = '3';
			el.style.willChange = 'transform';
			el.style.margin = '0';
			if ('rtl' === (document.documentElement.getAttribute('dir') || '')) {
				el.style.right = Math.max(0, window.innerWidth - data.left - data.width) + 'px';
				el.style.left = 'auto';
			} else {
				el.style.left = Math.max(0, data.left) + 'px';
				el.style.right = 'auto';
			}
		} else if (!on && '1' === el.dataset.pixvaPinned) {
			delete el.dataset.pixvaPinned;
			el.style.position = '';
			el.style.top = '';
			el.style.bottom = '';
			el.style.width = '';
			el.style.maxWidth = '';
			el.style.left = '';
			el.style.right = '';
			el.style.zIndex = '';
			el.style.willChange = '';
			el.style.margin = '';
		}
	};

	Trigger.prototype.update = function () {
		var raw = (scrollY - this.start) / Math.max(1, this.end - this.start);
		var progress = Math.max(0, Math.min(1, raw));
		var wasActive = this.isActive;

		this.direction = progress >= this.lastProgress ? 1 : -1;
		this.lastProgress = progress;
		this.isActive = progress > 0 && progress < 1;

		if (this.pin) { this.setPinned(this.isActive); }

		if (progress !== this.progress || wasActive !== this.isActive) {
			this.progress = progress;
			if (this.animation && this.scrub && 'function' === typeof this.animation.progress) {
				this.animation.progress(progress);
			}
			if (this.onUpdate) { this.onUpdate(this); }
		}

		if (!wasActive && this.isActive) {
			if (1 === this.direction && this.onEnter) { this.onEnter(this); }
			if (-1 === this.direction && this.onEnterBack) { this.onEnterBack(this); }
		} else if (wasActive && !this.isActive) {
			if (1 === this.direction && this.onLeave) { this.onLeave(this); }
			if (-1 === this.direction && this.onLeaveBack) { this.onLeaveBack(this); }
		}
	};

	Trigger.prototype.kill = function () {
		this.setPinned(false);

		var el = this.pin;
		var data = el ? pinned.get(el) : null;
		if (data && data.spacer && data.spacer.parentNode) {
			data.spacer.parentNode.insertBefore(el, data.spacer);
			data.spacer.remove();
			pinned.delete(el);
		}

		var i = triggers.indexOf(this);
		if (i > -1) { triggers.splice(i, 1); }
	};

	var pending = false;
	function updateAll() {
		if (pending) { return; }
		pending = true;
		window.requestAnimationFrame(function () {
			pending = false;
			measure();
			triggers.slice().forEach(function (trigger) { trigger.update(); });
		});
	}

	var ScrollTrigger = {
		__pixvaLite: true,
		version: '3.13.0-lite',
		create: function (config) { return new Trigger(config); },
		refresh: function () {
			measure();
			triggers.slice().forEach(function (trigger) { trigger.refresh(); trigger.update(); });
		},
		update: updateAll,
		getAll: function () { return triggers.slice(); },
		killAll: function () {
			triggers.slice().forEach(function (trigger) { trigger.kill(); });
			triggers = [];
		},
		isTouch: function () { return !!(window.matchMedia && window.matchMedia('(hover: none)').matches); }
	};

	measure();
	window.addEventListener('scroll', updateAll, { passive: true });
	window.addEventListener('resize', function () { ScrollTrigger.refresh(); }, { passive: true });
	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', function () { ScrollTrigger.refresh(); });
	}

	/* ------------------------------- gsap ------------------------------- */
	var gsap = {
		__pixvaLite: true,
		version: '3.13.0-lite',
		config: { nullTargetWarn: false, autoSleep: 120 },
		globalTimeline: new Timeline({ paused: true }),
		utils: {
			toArray: toArray,
			clamp: function (min, max, value) {
				var fn = function (v) { return Math.max(min, Math.min(max, v)); };
				return undefined === value ? fn : fn(value);
			},
			mapRange: function (inMin, inMax, outMin, outMax, value) {
				var fn = function (v) { return outMin + ((v - inMin) / ((inMax - inMin) || 1)) * (outMax - outMin); };
				return undefined === value ? fn : fn(value);
			},
			interpolate: function (a, b) { return function (t) { return a + (b - a) * t; }; },
			wrap: function (min, max, value) {
				var range = max - min;
				return min + ((((value - min) % range) + range) % range);
			},
			snap: function (step, value) {
				var fn = function (v) { return Math.round(v / step) * step; };
				return undefined === value ? fn : fn(value);
			},
			quickSetter: function (target, prop, unit) {
				var list = toArray(target);
				return function (value) {
					list.forEach(function (el) {
						var state = stateOf(el);
						if ('opacity' === prop) { state.opacity = parseFloat(value); }
						else if (TRANSFORM_KEYS[prop]) { state[prop] = parseFloat(value); }
						else { state.css[prop] = value + (unit || ''); }
						applyState(el);
					});
				};
			}
		},
		registerPlugin: function () { return gsap; },
		set: function (targets, vars) {
			var list = toArray(targets);
			var parsed = parseVars(Object.assign({}, vars, { duration: 0 }));
			var tween = new Tween(list, startsFromState(list, parsed), endsFromVars(list, parsed), parsed, false);
			tween.paused = true;
			tween.seek(1);
			return tween;
		},
		to: function (targets, vars) {
			var list = toArray(targets);
			var parsed = parseVars(vars);
			var tween = new Tween(list, startsFromState(list, parsed), endsFromVars(list, parsed), parsed, false);
			tween.paused = !!parsed.paused || !!(vars && vars.paused);
			if (vars && vars.immediateRender) { tween.seek(0); }
			if (!tween.paused) { wake(tween); }
			return tween;
		},
		from: function (targets, vars) {
			var list = toArray(targets);
			var parsed = parseVars(vars);
			var tween = new Tween(list, startsFromVars(list, parsed), startsFromState(list, parsed), parsed, false);
			tween.paused = !!(vars && vars.paused);
			tween.seek(0);
			if (!tween.paused) { wake(tween); }
			return tween;
		},
		fromTo: function (targets, fromVars, toVars) {
			var list = toArray(targets);
			var fromParsed = parseVars(fromVars);
			var parsed = parseVars(toVars);
			var tween = new Tween(list, startsFromVars(list, fromParsed), endsFromVars(list, parsed), parsed, false);
			tween.paused = !!(toVars && toVars.paused);
			tween.seek(0);
			if (!tween.paused) { wake(tween); }
			return tween;
		},
		timeline: function (vars) {
			var tl = new Timeline(vars || {});
			if (!tl.paused) { wake(tl); }
			return tl;
		},
		delayedCall: function (delay, fn) {
			var id = window.setTimeout(fn, delay * 1000);
			return { kill: function () { window.clearTimeout(id); }, pause: function () { window.clearTimeout(id); } };
		},
		killTweensOf: function (targets) {
			var list = toArray(targets);
			active.slice().forEach(function (item) {
				(item.targets || []).forEach(function (el) {
					if (list.indexOf(el) > -1) { item.kill(); }
				});
			});
		},
		matchMedia: function () {
			var entries = [];
			return {
				add: function (conditions, fn) {
					var list = 'string' === typeof conditions ? [conditions] : Object.keys(conditions || {});
					list.forEach(function (query) {
						var mql = window.matchMedia(query);
						if (mql.matches) {
							var cleanup = fn(mql);
							entries.push({ mql: mql, cleanup: cleanup });
						}
					});
					return this;
				},
				revert: function () {
					entries.forEach(function (entry) { if ('function' === typeof entry.cleanup) { entry.cleanup(); } });
					entries = [];
				},
				kill: function () { this.revert(); }
			};
		},
		ticker: {
			add: function (fn) {
				var loop = function (now) { fn(now); window.requestAnimationFrame(loop); };
				window.requestAnimationFrame(loop);
			},
			remove: function () {},
			lagSmoothing: function () {}
		}
	};

	window.gsap = gsap;
	window.ScrollTrigger = ScrollTrigger;
	window.PixvaTweenEngine = { gsap: gsap, ScrollTrigger: ScrollTrigger, states: states };

	window.addEventListener('pagehide', function () { ScrollTrigger.killAll(); });
}(window, document));
