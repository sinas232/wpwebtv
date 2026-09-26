/**
 * نقشه زنده تعمیرکار (Uber-style) — لایه ۱٫۶٫۰.
 *
 * دو موتور رندر:
 *  ۱) Leaflet (اگر کتابخانه از vendor یا CDN بارگذاری شده باشد) با کاشی Dark.
 *  ۲) نقشه داخلی SVG (بدون هیچ وابستگی و بدون درخواست شبکه) با همان ظاهر دارک،
 *     مسیر نئونی، مارکر متحرک ماشین تعمیرکار و هاله پالس.
 *
 * داده از wp-json/pixva/v1/dispatch-live می‌آید و به‌صورت دوره‌ای تازه می‌شود.
 *
 * @package Pixva
 * @since   1.6.0
 */
(function (window, document) {
	'use strict';

	var cfg = window.pixvaTrackerMap || {};
	var i18n = cfg.i18n || {};
	var toFa = function (value) {
		var fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
		return String(value).replace(/[0-9]/g, function (d) { return fa[parseInt(d, 10)]; });
	};

	function qs(selector, root) { return (root || document).querySelector(selector); }
	function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

	var CAR_PATH = 'M3 13l1.6-4.2A3 3 0 0 1 7.4 7h9.2a3 3 0 0 1 2.8 1.8L21 13m-18 0h18m-18 0v4h2.5m15.5-4v4h-2.5M5 17h14M7.5 15.2h.01M16.5 15.2h.01';

	/* --------------------------- رندر داخلی SVG --------------------------- */
	function InternalMap(canvas, options) {
		this.canvas = canvas;
		this.options = options || {};
		this.svg = null;
		this.bounds = null;
		this.route = [];
		this.progress = 0;
		this.frame = null;
		this.current = null;
		this.build();
	}

	InternalMap.prototype.build = function () {
		var ns = 'http://www.w3.org/2000/svg';
		var svg = document.createElementNS(ns, 'svg');
		svg.setAttribute('viewBox', '0 0 100 62');
		svg.setAttribute('preserveAspectRatio', 'none');
		svg.setAttribute('class', 'pixva-map__svg');
		svg.setAttribute('aria-hidden', 'true');

		var grid = document.createElementNS(ns, 'g');
		grid.setAttribute('class', 'pixva-map__grid');
		var i;
		for (i = 1; i < 10; i++) {
			var line = document.createElementNS(ns, 'line');
			line.setAttribute('x1', String(i * 10));
			line.setAttribute('y1', '0');
			line.setAttribute('x2', String(i * 10));
			line.setAttribute('y2', '62');
			grid.appendChild(line);
		}
		for (i = 1; i < 6; i++) {
			var row = document.createElementNS(ns, 'line');
			row.setAttribute('x1', '0');
			row.setAttribute('y1', String(i * 10));
			row.setAttribute('x2', '100');
			row.setAttribute('y2', String(i * 10));
			grid.appendChild(row);
		}
		svg.appendChild(grid);

		var halo = document.createElementNS(ns, 'circle');
		halo.setAttribute('class', 'pixva-map__halo');
		halo.setAttribute('r', '9');
		svg.appendChild(halo);
		this.halo = halo;

		var path = document.createElementNS(ns, 'polyline');
		path.setAttribute('class', 'pixva-map__route');
		path.setAttribute('fill', 'none');
		svg.appendChild(path);
		this.path = path;

		var travelled = document.createElementNS(ns, 'polyline');
		travelled.setAttribute('class', 'pixva-map__route pixva-map__route--done');
		travelled.setAttribute('fill', 'none');
		svg.appendChild(travelled);
		this.travelled = travelled;

		var origin = document.createElementNS(ns, 'g');
		origin.setAttribute('class', 'pixva-map__point pixva-map__point--origin');
		origin.appendChild(this.dot(ns, 2.2));
		svg.appendChild(origin);
		this.origin = origin;

		var dest = document.createElementNS(ns, 'g');
		dest.setAttribute('class', 'pixva-map__point pixva-map__point--dest');
		dest.appendChild(this.dot(ns, 2.8));
		dest.appendChild(this.ring(ns, 5));
		svg.appendChild(dest);
		this.dest = dest;

		var car = document.createElementNS(ns, 'g');
		car.setAttribute('class', 'pixva-map__car');
		var pulse = document.createElementNS(ns, 'circle');
		pulse.setAttribute('class', 'pixva-map__car-pulse');
		pulse.setAttribute('r', '5');
		car.appendChild(pulse);
		var body = document.createElementNS(ns, 'circle');
		body.setAttribute('class', 'pixva-map__car-body');
		body.setAttribute('r', '2.9');
		car.appendChild(body);
		var glyph = document.createElementNS(ns, 'path');
		glyph.setAttribute('class', 'pixva-map__car-glyph');
		glyph.setAttribute('d', CAR_PATH);
		glyph.setAttribute('transform', 'translate(-2.2,-2.2) scale(0.185)');
		car.appendChild(glyph);
		svg.appendChild(car);
		this.car = car;

		this.canvas.replaceChildren(svg);
		this.svg = svg;
		this.ns = ns;
	};

	InternalMap.prototype.dot = function (ns, r) {
		var circle = document.createElementNS(ns, 'circle');
		circle.setAttribute('r', String(r));
		return circle;
	};

	InternalMap.prototype.ring = function (ns, r) {
		var circle = document.createElementNS(ns, 'circle');
		circle.setAttribute('class', 'pixva-map__ring');
		circle.setAttribute('r', String(r));
		circle.setAttribute('fill', 'none');
		return circle;
	};

	InternalMap.prototype.computeBounds = function (points) {
		var lats = points.map(function (p) { return p[0]; });
		var lngs = points.map(function (p) { return p[1]; });
		var minLat = Math.min.apply(null, lats);
		var maxLat = Math.max.apply(null, lats);
		var minLng = Math.min.apply(null, lngs);
		var maxLng = Math.max.apply(null, lngs);
		var padLat = Math.max(0.004, (maxLat - minLat) * 0.35);
		var padLng = Math.max(0.005, (maxLng - minLng) * 0.35);

		this.bounds = {
			minLat: minLat - padLat,
			maxLat: maxLat + padLat,
			minLng: minLng - padLng,
			maxLng: maxLng + padLng
		};
	};

	InternalMap.prototype.project = function (point) {
		var b = this.bounds;
		var x = ((point[1] - b.minLng) / ((b.maxLng - b.minLng) || 1)) * 100;
		var y = 62 - ((point[0] - b.minLat) / ((b.maxLat - b.minLat) || 1)) * 62;
		return [Math.round(x * 100) / 100, Math.round(y * 100) / 100];
	};

	InternalMap.prototype.setPlace = function (el, xy) {
		el.setAttribute('transform', 'translate(' + xy[0] + ',' + xy[1] + ')');
	};

	InternalMap.prototype.render = function (payload) {
		var self = this;
		var points = (payload.route || []).slice();
		if (!points.length) { return; }

		if (payload.origin) { points.push([payload.origin[0], payload.origin[1]]); }
		if (payload.destination) { points.push([payload.destination[0], payload.destination[1]]); }
		this.computeBounds(points);

		var projected = (payload.route || []).map(function (p) { return self.project(p); });
		this.route = projected;

		var attr = projected.map(function (p) { return p[0] + ',' + p[1]; }).join(' ');
		this.path.setAttribute('points', attr);

		if (payload.origin) { this.setPlace(this.origin, this.project([payload.origin[0], payload.origin[1]])); }
		if (payload.destination) {
			this.setPlace(this.dest, this.project([payload.destination[0], payload.destination[1]]));
			this.setPlace(this.halo, this.project([payload.destination[0], payload.destination[1]]));
		}

		this.progress = Math.max(0, Math.min(1, parseFloat(payload.progress) || 0));
		this.placeCar(this.progress);
	};

	InternalMap.prototype.placeCar = function (progress) {
		if (!this.route.length) { return; }
		var index = Math.min(this.route.length - 1, Math.floor(progress * (this.route.length - 1)));
		var next = Math.min(this.route.length - 1, index + 1);
		var local = (progress * (this.route.length - 1)) - index;
		var x = this.route[index][0] + (this.route[next][0] - this.route[index][0]) * local;
		var y = this.route[index][1] + (this.route[next][1] - this.route[index][1]) * local;

		this.setPlace(this.car, [Math.round(x * 100) / 100, Math.round(y * 100) / 100]);
		this.current = [x, y];

		var travelled = this.route.slice(0, index + 1).concat([[x, y]]);
		this.travelled.setAttribute('points', travelled.map(function (p) { return p[0] + ',' + p[1]; }).join(' '));
	};

	InternalMap.prototype.animateTo = function (progress) {
		var self = this;
		var from = this.progress;
		var to = Math.max(0, Math.min(1, progress));
		if (from === to) { return; }
		var start = null;
		var duration = 900;

		if (this.frame) { window.cancelAnimationFrame(this.frame); }

		var step = function (now) {
			if (null === start) { start = now; }
			var t = Math.min(1, (now - start) / duration);
			self.progress = from + (to - from) * (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
			self.placeCar(self.progress);
			if (t < 1) { self.frame = window.requestAnimationFrame(step); }
		};
		this.frame = window.requestAnimationFrame(step);
	};

	InternalMap.prototype.destroy = function () {
		if (this.frame) { window.cancelAnimationFrame(this.frame); }
		this.canvas.replaceChildren();
	};

	/* ------------------------------ Leaflet ------------------------------ */
	function LeafletMap(canvas, options) {
		this.canvas = canvas;
		this.options = options || {};
		this.map = window.L.map(canvas, {
			zoomControl: false,
			attributionControl: true,
			scrollWheelZoom: false,
			dragging: true
		});
		window.L.tileLayer(this.options.tiles, {
			subdomains: 'abcd',
			maxZoom: 19,
			attribution: this.options.attribution
		}).addTo(this.map);
		this.route = null;
		this.marker = null;
		this.progress = 0;
	}

	LeafletMap.prototype.icon = function (className) {
		return window.L.divIcon({
			className: 'pixva-map__icon ' + className,
			html: '<span class="pixva-map__icon-dot"></span>',
			iconSize: [22, 22],
			iconAnchor: [11, 11]
		});
	};

	LeafletMap.prototype.render = function (payload) {
		var L = window.L;
		var points = (payload.route || []).map(function (p) { return [p[0], p[1]]; });
		if (!points.length) { return; }

		if (this.route) { this.map.removeLayer(this.route); }
		this.route = L.polyline(points, { color: this.options.neon || 'rgb(34,211,238)', weight: 4, opacity: 0.9 }).addTo(this.map);

		if (payload.origin) {
			L.marker([payload.origin[0], payload.origin[1]], { icon: this.icon('is-origin'), title: payload.origin[2] || '' }).addTo(this.map);
		}
		if (payload.destination) {
			L.marker([payload.destination[0], payload.destination[1]], { icon: this.icon('is-dest'), title: payload.destination[2] || '' }).addTo(this.map);
		}

		if (!this.marker) {
			this.marker = L.marker(points[0], { icon: this.icon('is-car') }).addTo(this.map);
		}

		this.points = points;
		this.progress = Math.max(0, Math.min(1, parseFloat(payload.progress) || 0));
		this.placeCar(this.progress);
		this.map.fitBounds(this.route.getBounds(), { padding: [28, 28] });
		if (this.options.zoom) { this.map.setZoom(Math.min(this.map.getZoom(), this.options.zoom)); }
	};

	LeafletMap.prototype.placeCar = function (progress) {
		if (!this.points || !this.points.length) { return; }
		var index = Math.min(this.points.length - 1, Math.floor(progress * (this.points.length - 1)));
		var next = Math.min(this.points.length - 1, index + 1);
		var local = (progress * (this.points.length - 1)) - index;
		var lat = this.points[index][0] + (this.points[next][0] - this.points[index][0]) * local;
		var lng = this.points[index][1] + (this.points[next][1] - this.points[index][1]) * local;
		this.marker.setLatLng([lat, lng]);
		this.progress = progress;
	};

	LeafletMap.prototype.animateTo = function (progress) { this.placeCar(progress); };
	LeafletMap.prototype.destroy = function () { if (this.map) { this.map.remove(); } };

	/* ------------------------------- ماژول ------------------------------- */
	function initSection(section) {
		if ('1' === section.dataset.mapBound) { return; }
		section.dataset.mapBound = '1';

		var canvas = qs('[data-map-canvas]', section);
		var form = qs('[data-map-form]', section);
		var statusBox = qs('[data-map-status]', section);
		var etaEl = qs('[data-map-eta]', section);
		var techEl = qs('[data-map-tech]', section);
		var stateEl = qs('[data-map-state]', section);
		var errorEl = qs('[data-map-error]', section);
		var attrEl = qs('[data-map-attr]', section);

		var map = null;
		var timer = null;
		var lastPayload = null;
		var provider = cfg.provider || 'auto';
		var useLeaflet = cfg.hasLeaflet && window.L && 'internal' !== provider;

		function engine() {
			if (map) { return map; }
			if (!canvas) { return null; }
			var style = window.getComputedStyle(section);
			var options = {
				tiles: cfg.tiles || '',
				attribution: cfg.attribution || '',
				neon: style.getPropertyValue('--map-neon') || 'rgb(34,211,238)',
				zoom: parseInt(section.dataset.mapZoom, 10) || 14
			};
			map = useLeaflet ? new LeafletMap(canvas, options) : new InternalMap(canvas, options);
			if (attrEl) {
				attrEl.textContent = useLeaflet ? (options.attribution || '') : (i18n.simulatedNote || '');
			}
			return map;
		}

		function paintStatus(data) {
			if (!statusBox) { return; }
			statusBox.hidden = false;
			section.dataset.mapMoving = data.moving ? '1' : '0';
			section.dataset.mapArrived = data.arrived ? '1' : '0';
			section.dataset.mapSimulated = data.simulated ? '1' : '0';

			if (etaEl) {
				if (data.arrived) {
					etaEl.textContent = data.statusLabel || '';
				} else if (data.eta > 0) {
					etaEl.textContent = (i18n.moving || '') + ' · ' + (i18n.eta || '') + ': ' + toFa(data.eta) + ' ' + (i18n.minutes || '');
				} else {
					etaEl.textContent = i18n.waiting || '';
				}
			}
			if (techEl) {
				var name = data.technician && data.technician.name ? data.technician.name : '';
				techEl.textContent = name ? (i18n.tech || '') + ': ' + name + (data.technician.skill ? ' · ' + data.technician.skill : '') : (i18n.waiting || '');
			}
			if (stateEl) {
				stateEl.textContent = data.simulated ? (data.statusLabel || '') + ' · ' + (i18n.simulated || '') : (data.statusLabel || '');
			}
		}

		function apply(data) {
			lastPayload = data;
			var instance = engine();
			if (!instance) { return; }
			instance.render(data);
			paintStatus(data);
			if (errorEl) { errorEl.hidden = true; }

			var refresh = Math.max(5, parseInt(section.dataset.mapRefresh, 10) || data.refresh || 20);
			if (timer) { window.clearInterval(timer); }
			if (data.moving) {
				timer = window.setInterval(function () {
					if (map && lastPayload) {
						map.animateTo(Math.min(1, lastPayload.progress + 0.02));
					}
				}, Math.max(1200, (refresh * 1000) / 6));
			}
		}

		function load(code, phone) {
			if (!code) { return Promise.resolve(null); }
			var url = (cfg.restUrl || '/wp-json/pixva/v1') + '/dispatch-live?code=' + encodeURIComponent(code) + '&phone=' + encodeURIComponent(phone || '');
			section.dataset.mapLoading = '1';

			return window.fetch(url, {
				headers: { 'X-WP-Nonce': cfg.nonce || '' },
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().then(function (data) { return { ok: response.ok, data: data }; });
			}).then(function (payload) {
				delete section.dataset.mapLoading;
				if (!payload.ok) {
					if (errorEl) {
						errorEl.hidden = false;
						errorEl.textContent = payload.data.message || i18n.error || '';
					}
					if (statusBox) { statusBox.hidden = true; }
					return null;
				}
				apply(payload.data);
				return payload.data;
			}).catch(function () {
				delete section.dataset.mapLoading;
				if (errorEl) {
					errorEl.hidden = false;
					errorEl.textContent = i18n.error || '';
				}
				return null;
			});
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				var code = (qs('[name="code"]', form) || {}).value || '';
				var phone = (qs('[name="phone"]', form) || {}).value || '';
				load(code.trim(), phone.trim());
			});
		}

		// اگر کد از پیش تنظیم شده (شورت‌کد/ویجت) بلافاصله بارگذاری کن.
		var preset = section.dataset.mapCode || '';
		if (preset) {
			var presetPhone = section.dataset.mapPhone || '';
			load(preset, presetPhone);
		}

		// نتیجه استعلام پیگیری در همان صفحه → نقشه خودکار پر می‌شود.
		document.addEventListener('pixva:track-result', function (event) {
			var data = event.detail || {};
			if (!data.code) { return; }
			var phone = data.phone || (form ? ((qs('[name="phone"]', form) || {}).value || '') : '');
			if (phone) {
				load(data.code, phone);
			} else {
				// بدون شماره همراه، داده موقعیت از REST گرفته نمی‌شود؛ وضعیت نمایشی.
				apply({
					code: data.code,
					status: data.status || 'pending',
					statusLabel: data.statusLabel || '',
					technician: { name: '', skill: '', phone: '' },
					eta: 0,
					progress: 0,
					route: [],
					moving: false,
					arrived: false,
					simulated: true,
					refresh: 20
				});
				if (errorEl) {
					errorEl.hidden = false;
					errorEl.textContent = i18n.needPhone || '';
				}
			}
		});

		section.pixvaTracker = { load: load, apply: apply, engine: engine };
	}

	function scan(root) {
		qsa('[data-pixva-tracker]', root || document).forEach(initSection);
	}

	function boot() {
		document.documentElement.classList.add('pixva-map-js');
		scan(document);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.pixvaTrackerScan = scan;
	window.pixvaTrackerEngines = { internal: InternalMap, leaflet: LeafletMap };
}(window, document));
