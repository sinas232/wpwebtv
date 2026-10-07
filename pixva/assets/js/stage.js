/**
 * صحنه سه‌بعدی فوتر پیکسوا.
 * تلویزیون بازشده روی میز بندینگ: مردمک تشخیص و نوک دستگاه، جهت نشانگر را دنبال می‌کنند.
 * زاویه صفر راست است، π/۲ پایین، π چپ و −π/۲ بالا. جدول ویدیو لازم نیست؛ ریگ زنده است.
 */
(function (factory) {
	var api = factory();
	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}
	if (typeof window !== 'undefined' && typeof document !== 'undefined') {
		api.boot();
	}
})(function () {
	'use strict';

	var TAU = Math.PI * 2;
	var DEADZONE = 8;
	var CAM_Z = 3.72;
	var FOCAL = 2.62;
	var CAM_PITCH = -0.2;
	var BASE_YAW = -0.34;

	function wrap(angle) {
		return (angle % TAU + TAU) % TAU;
	}

	function dampAngle(current, next, k) {
		var delta = next - current;
		while (delta > Math.PI) {
			delta -= TAU;
		}
		while (delta < -Math.PI) {
			delta += TAU;
		}
		return current + delta * k;
	}

	function mix(a, b, t) {
		var amount = Math.max(0, Math.min(1, t));
		return [
			Math.round(a[0] + (b[0] - a[0]) * amount),
			Math.round(a[1] + (b[1] - a[1]) * amount),
			Math.round(a[2] + (b[2] - a[2]) * amount)
		];
	}

	function rgb(c, alpha) {
		if (alpha == null) {
			return 'rgb(' + c[0] + ',' + c[1] + ',' + c[2] + ')';
		}
		return 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + alpha + ')';
	}

	function rotX(p, a) {
		var c = Math.cos(a);
		var s = Math.sin(a);
		return { x: p.x, y: p.y * c - p.z * s, z: p.y * s + p.z * c };
	}

	function rotY(p, a) {
		var c = Math.cos(a);
		var s = Math.sin(a);
		return { x: p.x * c + p.z * s, y: p.y, z: -p.x * s + p.z * c };
	}

	function poseOf(state) {
		var influence = state.influence;
		return {
			angle: state.angle,
			influence: influence,
			explode: state.explode,
			yaw: BASE_YAW + Math.cos(state.angle) * 0.28 * influence,
			pitch: -Math.sin(state.angle) * 0.16 * influence
		};
	}

	function project(p, view) {
		var z = CAM_Z - p.z;
		var f = FOCAL / Math.max(0.35, z);
		return {
			x: view.cx + p.x * f * view.scale,
			y: view.cy - p.y * f * view.scale,
			f: f * view.scale,
			z: z
		};
	}

	function viewFor(w, h, stacked) {
		if (stacked) {
			return {
				cx: w * 0.5,
				cy: h * 0.46,
				scale: Math.min(w * 0.46, h * 0.42),
				desk: false
			};
		}
		return {
			cx: w * 0.5,
			cy: h * 0.44,
			scale: Math.min(w * 0.248, h * 0.46),
			desk: true
		};
	}

	function tvBody(local, pose) {
		var p = rotY(local, pose.yaw);
		p = rotX(p, pose.pitch);
		p.y += 0.32;
		return p;
	}

	function placeTv(local, pose) {
		return rotX(tvBody(local, pose), CAM_PITCH);
	}

	function placeWorld(local) {
		return rotX(local, CAM_PITCH);
	}

	function quad(left, right, top, bottom, z) {
		return [
			{ x: left, y: top, z: z },
			{ x: right, y: top, z: z },
			{ x: right, y: bottom, z: z },
			{ x: left, y: bottom, z: z }
		];
	}

	function sideFaces(left, right, top, bottom, zBack, zFront) {
		return [
			[
				{ x: right, y: top, z: zBack },
				{ x: right, y: top, z: zFront },
				{ x: right, y: bottom, z: zFront },
				{ x: right, y: bottom, z: zBack }
			],
			[
				{ x: left, y: top, z: zFront },
				{ x: left, y: top, z: zBack },
				{ x: left, y: bottom, z: zBack },
				{ x: left, y: bottom, z: zFront }
			],
			[
				{ x: left, y: top, z: zBack },
				{ x: right, y: top, z: zBack },
				{ x: right, y: top, z: zFront },
				{ x: left, y: top, z: zFront }
			],
			[
				{ x: left, y: bottom, z: zFront },
				{ x: right, y: bottom, z: zFront },
				{ x: right, y: bottom, z: zBack },
				{ x: left, y: bottom, z: zBack }
			]
		];
	}

	function mapPoints(points, mapper) {
		var out = new Array(points.length);
		for (var i = 0; i < points.length; i++) {
			out[i] = mapper(points[i]);
		}
		return out;
	}

	function trace(ctx, pts) {
		ctx.beginPath();
		ctx.moveTo(pts[0].x, pts[0].y);
		for (var i = 1; i < pts.length; i++) {
			ctx.lineTo(pts[i].x, pts[i].y);
		}
		ctx.closePath();
	}

	function fillPoly(ctx, pts, style) {
		trace(ctx, pts);
		ctx.fillStyle = style;
		ctx.fill();
	}

	function lerp(a, b, t) {
		return { x: a.x + (b.x - a.x) * t, y: a.y + (b.y - a.y) * t };
	}

	function bilerp(pts, u, v) {
		return lerp(lerp(pts[0], pts[1], u), lerp(pts[3], pts[2], u), v);
	}

	function drawEye(ctx, x, y, r, dx, dy) {
		ctx.beginPath();
		ctx.arc(x, y, r * 1.22, 0, TAU);
		ctx.fillStyle = 'rgba(2, 6, 23, 0.72)';
		ctx.fill();

		var iris = ctx.createRadialGradient(x - r * 0.22, y - r * 0.28, r * 0.08, x, y, r);
		iris.addColorStop(0, '#f8fdff');
		iris.addColorStop(0.16, '#a5f3fc');
		iris.addColorStop(0.42, '#0891b2');
		iris.addColorStop(0.74, '#0c4a6e');
		iris.addColorStop(1, '#020617');
		ctx.beginPath();
		ctx.arc(x, y, r, 0, TAU);
		ctx.fillStyle = iris;
		ctx.fill();
		ctx.lineWidth = Math.max(1.25, r * 0.07);
		ctx.strokeStyle = 'rgba(224, 242, 254, 0.9)';
		ctx.stroke();

		var px = x + dx;
		var py = y + dy;
		var pr = r * 0.44;
		ctx.beginPath();
		ctx.arc(px, py, pr, 0, TAU);
		ctx.fillStyle = '#01040c';
		ctx.fill();
		ctx.lineWidth = Math.max(1, r * 0.045);
		ctx.strokeStyle = 'rgba(34, 211, 238, 0.8)';
		ctx.stroke();

		ctx.beginPath();
		ctx.arc(px - r * 0.15, py - r * 0.16, r * 0.13, 0, TAU);
		ctx.fillStyle = 'rgba(255,255,255,0.96)';
		ctx.fill();
		ctx.beginPath();
		ctx.arc(px + r * 0.15, py + r * 0.12, r * 0.045, 0, TAU);
		ctx.fillStyle = '#fb923c';
		ctx.fill();
	}

	function curve(a, b, c, t) {
		var u = 1 - t;
		return {
			x: u * u * a.x + 2 * u * t * b.x + t * t * c.x,
			y: u * u * a.y + 2 * u * t * b.y + t * t * c.y,
			z: u * u * a.z + 2 * u * t * b.z + t * t * c.z
		};
	}

	function drawStage(ctx, w, h, state, now, options) {
		var opts = options || {};
		var pose = poseOf(state);
		var view = viewFor(w, h, !!opts.stacked);
		var pulse = opts.reduced ? 0.65 : 0.5 + 0.5 * Math.sin(now / 680);
		var ex = pose.explode;

		var gw = 1.4;
		var gh = 0.78;
		var zGlass = 0.05;
		var zDiff = -0.07 * ex;
		var zLed = -0.14 * ex;
		var zTray = -0.22 * ex;
		var dropDiff = -0.24 - 0.06 * ex;
		var dropLed = -0.5 - 0.08 * ex;
		var dropTray = -0.7 - 0.06 * ex;
		var ledW = 1.28;
		var ledH = 0.68;

		function tv(local) {
			return project(placeTv(local, pose), view);
		}
		function world(local) {
			return project(placeWorld(local), view);
		}

		ctx.clearRect(0, 0, w, h);

		var sky = ctx.createLinearGradient(0, 0, 0, h);
		sky.addColorStop(0, '#07101c');
		sky.addColorStop(0.55, '#0a1424');
		sky.addColorStop(1, '#05080f');
		ctx.fillStyle = sky;
		ctx.fillRect(0, 0, w, h);

		var key = ctx.createRadialGradient(w * 0.5, h * 0.42, 20, w * 0.5, h * 0.48, Math.max(w, h) * 0.48);
		key.addColorStop(0, 'rgba(14, 116, 144, 0.28)');
		key.addColorStop(0.45, 'rgba(8, 47, 73, 0.08)');
		key.addColorStop(1, 'rgba(8, 47, 73, 0)');
		ctx.fillStyle = key;
		ctx.fillRect(0, 0, w, h);

		var rim = ctx.createRadialGradient(w * 0.86, h * 0.78, 10, w * 0.8, h * 0.8, w * 0.38);
		rim.addColorStop(0, 'rgba(249, 115, 22, 0.16)');
		rim.addColorStop(1, 'rgba(249, 115, 22, 0)');
		ctx.fillStyle = rim;
		ctx.fillRect(0, 0, w, h);

		var floor = mapPoints(
			[
				{ x: -3.4, y: -1.02, z: -1.8 },
				{ x: 3.4, y: -1.02, z: -1.8 },
				{ x: 3.4, y: -1.02, z: 2.05 },
				{ x: -3.4, y: -1.02, z: 2.05 }
			],
			function (p) {
				return world(p);
			}
		);
		var floorFill = ctx.createLinearGradient(0, floor[0].y, 0, floor[2].y);
		floorFill.addColorStop(0, '#0c1726');
		floorFill.addColorStop(1, '#070d16');
		fillPoly(ctx, floor, floorFill);

		ctx.lineWidth = 1;
		ctx.strokeStyle = 'rgba(148, 163, 184, 0.13)';
		var g;
		for (g = -4; g <= 4; g++) {
			var xLine = mapPoints(
				[
					{ x: g * 0.42, y: -1.015, z: -1.5 },
					{ x: g * 0.42, y: -1.015, z: 1.7 }
				],
				function (p) {
					return world(p);
				}
			);
			ctx.beginPath();
			ctx.moveTo(xLine[0].x, xLine[0].y);
			ctx.lineTo(xLine[1].x, xLine[1].y);
			ctx.stroke();
		}
		for (g = -3; g <= 4; g++) {
			var zLine = mapPoints(
				[
					{ x: -2.2, y: -1.015, z: g * 0.38 },
					{ x: 2.2, y: -1.015, z: g * 0.38 }
				],
				function (p) {
					return world(p);
				}
			);
			ctx.beginPath();
			ctx.moveTo(zLine[0].x, zLine[0].y);
			ctx.lineTo(zLine[1].x, zLine[1].y);
			ctx.stroke();
		}

		var shadow = world({ x: 0, y: -1.0, z: 0.15 });
		ctx.save();
		ctx.translate(shadow.x, shadow.y);
		ctx.scale(1, 0.28);
		var shade = ctx.createRadialGradient(0, 0, 8, 0, 0, shadow.f * 0.85);
		shade.addColorStop(0, 'rgba(0, 0, 0, 0.55)');
		shade.addColorStop(1, 'rgba(0, 0, 0, 0)');
		ctx.fillStyle = shade;
		ctx.beginPath();
		ctx.arc(0, 0, shadow.f * 0.85, 0, TAU);
		ctx.fill();
		ctx.restore();

		var glow = world({ x: 0, y: -0.98, z: 0.2 });
		ctx.save();
		ctx.translate(glow.x, glow.y);
		ctx.scale(1, 0.3);
		var pool = ctx.createRadialGradient(0, 0, 4, 0, 0, glow.f * 0.55);
		pool.addColorStop(0, 'rgba(34, 211, 238, ' + (0.14 + pulse * 0.08) + ')');
		pool.addColorStop(1, 'rgba(34, 211, 238, 0)');
		ctx.fillStyle = pool;
		ctx.beginPath();
		ctx.arc(0, 0, glow.f * 0.55, 0, TAU);
		ctx.fill();
		ctx.restore();

		var boardPts = mapPoints(
			[
				{ x: -0.78, y: -1.0, z: -0.22 },
				{ x: 0.78, y: -1.0, z: -0.22 },
				{ x: 0.78, y: -1.0, z: 0.62 },
				{ x: -0.78, y: -1.0, z: 0.62 }
			],
			function (p) {
				return world(p);
			}
		);
		fillPoly(ctx, boardPts, '#07140f');
		trace(ctx, boardPts);
		ctx.strokeStyle = 'rgba(45, 212, 191, 0.35)';
		ctx.lineWidth = 1.25;
		ctx.stroke();

		function boardMark(x, z, color, radius) {
			var pt = world({ x: x, y: -0.99, z: z });
			ctx.beginPath();
			ctx.arc(pt.x, pt.y, Math.max(2, radius), 0, TAU);
			ctx.fillStyle = color;
			ctx.fill();
		}
		ctx.lineWidth = 1.4;
		ctx.strokeStyle = 'rgba(45, 212, 191, 0.55)';
		var traces = [
			[-0.55, 0.05, 0.1, 0.05],
			[0.1, 0.05, 0.1, 0.42],
			[-0.2, 0.28, 0.45, 0.28],
			[0.45, 0.28, 0.45, 0.5]
		];
		for (var t = 0; t < traces.length; t++) {
			var a = world({ x: traces[t][0], y: -0.985, z: traces[t][1] });
			var b = world({ x: traces[t][2], y: -0.985, z: traces[t][3] });
			ctx.beginPath();
			ctx.moveTo(a.x, a.y);
			ctx.lineTo(b.x, b.y);
			ctx.stroke();
		}
		var chip = mapPoints(
			[
				{ x: -0.16, y: -0.99, z: 0.12 },
				{ x: 0.2, y: -0.99, z: 0.12 },
				{ x: 0.2, y: -0.99, z: 0.4 },
				{ x: -0.16, y: -0.99, z: 0.4 }
			],
			function (p) {
				return world(p);
			}
		);
		fillPoly(ctx, chip, '#0f241c');
		trace(ctx, chip);
		ctx.strokeStyle = 'rgba(148, 163, 184, 0.45)';
		ctx.stroke();
		boardMark(-0.58, 0.48, '#22d3ee', 3.2);
		boardMark(-0.46, 0.48, '#22d3ee', 3.2);
		boardMark(0.58, 0.18, pulse > 0.45 ? '#fb923c' : '#9a3412', 3.4);

		function shifted(points, yShift) {
			var out = new Array(points.length);
			for (var i = 0; i < points.length; i++) {
				out[i] = { x: points[i].x, y: points[i].y + yShift, z: points[i].z };
			}
			return out;
		}

		function drawSlab(left, right, top, bottom, zBack, zFront, yShift, face, edge) {
			var sides = sideFaces(left, right, top, bottom, zBack, zFront);
			for (var s = 0; s < sides.length; s++) {
				fillPoly(ctx, mapPoints(shifted(sides[s], yShift), tv), edge);
			}
			fillPoly(ctx, mapPoints(shifted(quad(left, right, top, bottom, zFront), yShift), tv), face);
		}

		drawSlab(-0.9, 0.9, 0.54, -0.52, zTray - 0.045, zTray, dropTray, '#1e293b', '#94a3b8');
		var cavity = mapPoints(shifted(quad(-0.76, 0.76, 0.44, -0.42, zTray + 0.006), dropTray), tv);
		fillPoly(ctx, cavity, '#0b1220');

		var ledCenter = mapPoints(shifted([{ x: 0, y: 0, z: zLed }], dropLed), tv)[0];
		var bloom = ctx.createRadialGradient(ledCenter.x, ledCenter.y, 8, ledCenter.x, ledCenter.y, Math.max(40, ledCenter.f * 0.7));
		bloom.addColorStop(0, 'rgba(34, 211, 238, 0.28)');
		bloom.addColorStop(1, 'rgba(34, 211, 238, 0)');
		ctx.fillStyle = bloom;
		ctx.beginPath();
		ctx.arc(ledCenter.x, ledCenter.y, Math.max(40, ledCenter.f * 0.7), 0, TAU);
		ctx.fill();
		var ledSides = sideFaces(-ledW / 2, ledW / 2, ledH / 2, -ledH / 2, zLed - 0.025, zLed);
		for (var ls = 0; ls < ledSides.length; ls++) {
			fillPoly(ctx, mapPoints(shifted(ledSides[ls], dropLed), tv), '#155e75');
		}
		fillPoly(
			ctx,
			mapPoints(shifted(quad(-ledW / 2, ledW / 2, ledH / 2, -ledH / 2, zLed), dropLed), tv),
			'#042f3a'
		);
		var hotV = -Math.sin(pose.angle) * pose.influence;
		for (var bar = 0; bar < 9; bar++) {
			var v = -0.82 + bar * 0.205;
			var y0 = v * (ledH / 2);
			var bright = Math.exp(-Math.pow(v - hotV, 2) * 5);
			var col = mix([6, 182, 212], [240, 253, 255], 0.28 + bright * 0.62);
			var bh = ledH * 0.038;
			fillPoly(
				ctx,
				mapPoints(shifted(quad(-ledW * 0.42, ledW * 0.42, y0 + bh, y0 - bh, zLed + 0.008), dropLed), tv),
				rgb(col)
			);
		}

		ctx.save();
		ctx.globalAlpha = 0.92;
		drawSlab(-ledW / 2 - 0.015, ledW / 2 + 0.015, ledH / 2 + 0.015, -ledH / 2 - 0.015, zDiff - 0.016, zDiff, dropDiff, '#dbeafe', '#f8fafc');
		ctx.restore();

		var glassSides = sideFaces(-gw / 2, gw / 2, gh / 2, -gh / 2, zGlass - 0.018, zGlass);
		for (var gs = 0; gs < glassSides.length; gs++) {
			fillPoly(ctx, mapPoints(glassSides[gs], tv), gs === 2 ? '#67e8f9' : '#0e7490');
		}
		var glass = mapPoints(quad(-gw / 2, gw / 2, gh / 2, -gh / 2, zGlass), tv);
		fillPoly(ctx, glass, '#03060d');

		ctx.save();
		trace(ctx, glass);
		ctx.clip();
		var mid = bilerp(glass, 0.5, 0.5);
		var span = Math.hypot(glass[1].x - glass[0].x, glass[1].y - glass[0].y);
		var vig = ctx.createRadialGradient(mid.x, mid.y, span * 0.05, mid.x, mid.y, span * 0.62);
		vig.addColorStop(0, 'rgba(12, 74, 110, 0.42)');
		vig.addColorStop(1, 'rgba(3, 6, 13, 0)');
		ctx.fillStyle = vig;
		ctx.fillRect(0, 0, w, h);

		var bars = ['#f8fafc', '#fde047', '#22d3ee', '#4ade80', '#e879f9', '#f87171', '#60a5fa'];
		for (var bi = 0; bi < bars.length; bi++) {
			var u0 = 0.06 + (bi / bars.length) * 0.88;
			var u1 = 0.06 + ((bi + 1) / bars.length) * 0.88;
			fillPoly(
				ctx,
				[bilerp(glass, u0, 0.8), bilerp(glass, u1, 0.8), bilerp(glass, u1, 0.94), bilerp(glass, u0, 0.94)],
				bars[bi]
			);
		}

		if (!opts.reduced) {
			var scan = ((now / 2600) % 1);
			var sy0 = 0.08 + scan * 0.62;
			fillPoly(
				ctx,
				[bilerp(glass, 0.06, sy0), bilerp(glass, 0.94, sy0), bilerp(glass, 0.94, sy0 + 0.012), bilerp(glass, 0.06, sy0 + 0.012)],
				'rgba(165, 243, 252, 0.38)'
			);
		}

		var sheen = ctx.createLinearGradient(glass[0].x, glass[0].y, glass[2].x, glass[2].y);
		sheen.addColorStop(0, 'rgba(255,255,255,0.16)');
		sheen.addColorStop(0.28, 'rgba(255,255,255,0.03)');
		sheen.addColorStop(0.55, 'rgba(255,255,255,0)');
		ctx.fillStyle = sheen;
		ctx.fillRect(0, 0, w, h);
		ctx.restore();

		trace(ctx, glass);
		ctx.strokeStyle = 'rgba(103, 232, 249, 0.55)';
		ctx.lineWidth = 2;
		ctx.stroke();

		var eyeL = bilerp(glass, 0.37, 0.38);
		var eyeR = bilerp(glass, 0.63, 0.38);
		var glassWpx = Math.hypot(glass[1].x - glass[0].x, glass[1].y - glass[0].y);
		var eyeRds = Math.max(11, glassWpx * 0.072);
		var mag = eyeRds * 0.38 * Math.max(0.35, pose.influence);
		var pdx = Math.cos(pose.angle) * mag;
		var pdy = Math.sin(pose.angle) * mag;
		drawEye(ctx, eyeL.x, eyeL.y, eyeRds, pdx, pdy);
		drawEye(ctx, eyeR.x, eyeR.y, eyeRds, pdx, pdy);

		var frameOuter = quad(-gw / 2 - 0.058, gw / 2 + 0.058, gh / 2 + 0.046, -gh / 2 - 0.086, zGlass + 0.02);
		var frameInner = quad(-gw / 2 - 0.008, gw / 2 + 0.008, gh / 2 + 0.008, -gh / 2 - 0.008, zGlass + 0.02);
		var outerS = mapPoints(frameOuter, tv);
		var innerS = mapPoints(frameInner, tv);
		var strips = [
			[outerS[0], outerS[1], innerS[1], innerS[0]],
			[outerS[1], outerS[2], innerS[2], innerS[1]],
			[outerS[2], outerS[3], innerS[3], innerS[2]],
			[outerS[3], outerS[0], innerS[0], innerS[3]]
		];
		var metal = ctx.createLinearGradient(outerS[0].x, outerS[0].y, outerS[2].x, outerS[2].y);
		metal.addColorStop(0, '#f8fafc');
		metal.addColorStop(0.16, '#cbd5e1');
		metal.addColorStop(0.48, '#475569');
		metal.addColorStop(0.78, '#1e293b');
		metal.addColorStop(1, '#0f172a');
		for (var st = 0; st < strips.length; st++) {
			fillPoly(ctx, strips[st], metal);
		}
		ctx.beginPath();
		ctx.moveTo(outerS[0].x, outerS[0].y);
		ctx.lineTo(outerS[1].x, outerS[1].y);
		ctx.strokeStyle = 'rgba(255,255,255,0.72)';
		ctx.lineWidth = 1.6;
		ctx.stroke();
		trace(ctx, innerS);
		ctx.strokeStyle = 'rgba(15, 23, 42, 0.65)';
		ctx.lineWidth = 2;
		ctx.stroke();

		var ledPos = bilerp(outerS, 0.5, 0.93);
		ctx.beginPath();
		ctx.arc(ledPos.x, ledPos.y, Math.max(2.5, eyeRds * 0.16), 0, TAU);
		ctx.fillStyle = pulse > 0.4 ? '#fb923c' : '#9a3412';
		ctx.shadowColor = '#f97316';
		ctx.shadowBlur = opts.reduced ? 0 : 12;
		ctx.fill();
		ctx.shadowBlur = 0;

		var hot = {
			x: Math.cos(pose.angle) * pose.influence * gw * 0.34,
			y: -Math.sin(pose.angle) * pose.influence * gh * 0.28,
			z: zGlass + 0.05
		};
		var railX = gw / 2 + 0.07;
		var railFace = mapPoints(quad(railX, railX + 0.04, 0.5, -0.5, 0.12), tv);
		fillPoly(ctx, railFace, '#e2e8f0');
		fillPoly(ctx, mapPoints(quad(railX + 0.04, railX + 0.055, 0.5, -0.5, 0.1), tv), '#64748b');
		var carriage = tv({ x: railX + 0.02, y: hot.y, z: 0.14 });
		var tip = tv(hot);
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';
		ctx.beginPath();
		ctx.moveTo(carriage.x, carriage.y);
		ctx.lineTo(tip.x, tip.y);
		ctx.strokeStyle = '#f8fafc';
		ctx.lineWidth = Math.max(4, tip.f * 0.014);
		ctx.stroke();
		ctx.beginPath();
		ctx.moveTo(carriage.x, carriage.y);
		ctx.lineTo(tip.x, tip.y);
		ctx.strokeStyle = 'rgba(34, 211, 238, 0.9)';
		ctx.lineWidth = 1.4;
		ctx.stroke();

		var loupe = Math.max(13, tip.f * 0.062);
		ctx.beginPath();
		ctx.arc(tip.x, tip.y, loupe, 0, TAU);
		ctx.strokeStyle = 'rgba(248, 250, 252, 0.95)';
		ctx.lineWidth = 1.7;
		ctx.stroke();
		ctx.beginPath();
		ctx.arc(tip.x, tip.y, loupe * 0.62, 0, TAU);
		ctx.strokeStyle = 'rgba(34, 211, 238, 0.8)';
		ctx.lineWidth = 1.2;
		ctx.stroke();
		ctx.beginPath();
		ctx.arc(tip.x, tip.y, Math.max(2.4, loupe * 0.16), 0, TAU);
		ctx.fillStyle = '#fb923c';
		ctx.shadowColor = '#f97316';
		ctx.shadowBlur = opts.reduced ? 0 : 18;
		ctx.fill();
		ctx.shadowBlur = 0;
		ctx.beginPath();
		ctx.moveTo(tip.x - loupe * 0.78, tip.y);
		ctx.lineTo(tip.x - loupe * 0.34, tip.y);
		ctx.moveTo(tip.x + loupe * 0.34, tip.y);
		ctx.lineTo(tip.x + loupe * 0.78, tip.y);
		ctx.moveTo(tip.x, tip.y - loupe * 0.78);
		ctx.lineTo(tip.x, tip.y - loupe * 0.34);
		ctx.moveTo(tip.x, tip.y + loupe * 0.34);
		ctx.lineTo(tip.x, tip.y + loupe * 0.78);
		ctx.strokeStyle = 'rgba(248, 250, 252, 0.8)';
		ctx.lineWidth = 1.1;
		ctx.stroke();

		var swayOn = opts.reduced ? 0 : 1;
		ctx.lineCap = 'round';
		for (var cable = 0; cable < 5; cable++) {
			var cx = -0.42 + cable * 0.21;
			var start = tvBody({ x: cx, y: -gh / 2 - 0.02, z: zGlass }, pose);
			var end = { x: cx * 0.55, y: -0.98, z: 0.05 };
			var midp = {
				x: (start.x + end.x) / 2 + Math.sin(now / 700 + cable) * 0.04 * swayOn,
				y: Math.min(start.y, end.y) - 0.28,
				z: (start.z + end.z) / 2 + 0.42
			};
			ctx.beginPath();
			for (var step = 0; step <= 18; step++) {
				var pt = project(placeWorld(curve(start, midp, end, step / 18)), view);
				if (step === 0) {
					ctx.moveTo(pt.x, pt.y);
				} else {
					ctx.lineTo(pt.x, pt.y);
				}
			}
			ctx.strokeStyle = '#7c2d12';
			ctx.lineWidth = Math.max(5, tip.f * 0.016);
			ctx.stroke();
			ctx.strokeStyle = cable % 2 ? '#fb923c' : '#fdba74';
			ctx.lineWidth = Math.max(2.2, tip.f * 0.008);
			ctx.stroke();
		}

		if (opts.fontReady) {
			ctx.font = '600 ' + Math.max(11, Math.round(eyeRds * 0.62)) + 'px Vazirmatn, Tahoma, sans-serif';
			ctx.fillStyle = 'rgba(224, 242, 254, 0.82)';
			ctx.textAlign = 'left';
			ctx.textBaseline = 'middle';
			var label = bilerp(glass, 0.08, 0.16);
			ctx.fillText('تشخیص زنده', label.x, label.y);
			ctx.font = '500 ' + Math.max(10, Math.round(eyeRds * 0.5)) + 'px Vazirmatn, Tahoma, sans-serif';
			ctx.fillStyle = 'rgba(253, 186, 116, 0.9)';
			var deg = Math.round(wrap(pose.angle) * 180 / Math.PI);
			var degPt = bilerp(glass, 0.78, 0.16);
			ctx.fillText(deg + '°', degPt.x, degPt.y);
		}

		if (!opts.reduced && opts.motes) {
			for (var m = 0; m < opts.motes.length; m++) {
				var mote = opts.motes[m];
				var mx = w * (0.32 + mote.x * 0.36);
				var my = h * mote.y;
				ctx.fillStyle = 'rgba(186, 230, 253, ' + (0.12 + mote.s * 0.12) + ')';
				ctx.fillRect(mx, my, mote.s, mote.s);
			}
		}

		return {
			eyeX: (eyeL.x + eyeR.x) / 2,
			eyeY: (eyeL.y + eyeR.y) / 2
		};
	}

	function gazeAngle(eye, pointer) {
		var dx = pointer.x - eye.x;
		var dy = pointer.y - eye.y;
		if (Math.hypot(dx, dy) <= DEADZONE) {
			return null;
		}
		return Math.atan2(dy, dx);
	}

	function boot() {
		var nodes = document.querySelectorAll('[data-pixva-stage]');
		for (var i = 0; i < nodes.length; i++) {
			mount(nodes[i]);
		}
	}

	function mount(root) {
		if (root.getAttribute('data-pixva-stage-ready') === '1') {
			return;
		}
		root.setAttribute('data-pixva-stage-ready', '1');
		var scene = root.querySelector('[data-pixva-scene]');
		var canvas = root.querySelector('[data-pixva-stage-canvas]');
		var hint = root.querySelector('[data-pixva-stage-hint]');
		if (!scene || !canvas || !canvas.getContext) {
			return;
		}
		var ctx = canvas.getContext('2d', { alpha: false });
		if (!ctx) {
			return;
		}

		var mobile = window.matchMedia('(max-width: 700px)');
		var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
		var pointer = null;
		var hasPointer = false;
		var eyeScreen = null;
		var fontReady = false;
		var visible = true;
		var raf = 0;
		var last = 0;
		var cssW = 0;
		var cssH = 0;
		var state = { angle: 0.95, influence: 0.62, explode: 0.78 };
		var desired = { angle: state.angle, influence: state.influence, explode: state.explode };
		var motes = [];
		for (var m = 0; m < 18; m++) {
			motes.push({
				x: Math.random(),
				y: Math.random(),
				s: 0.6 + Math.random() * 1.6,
				v: 0.00008 + Math.random() * 0.00016
			});
		}

		function markFont() {
			fontReady = true;
			paint(performance.now());
		}
		if (document.fonts && document.fonts.load) {
			document.fonts.load('600 16px Vazirmatn').then(markFont).catch(markFont);
		}

		function resize() {
			var rect = scene.getBoundingClientRect();
			cssW = Math.max(1, rect.width);
			cssH = Math.max(1, rect.height);
			var dpr = Math.min(window.devicePixelRatio || 1, 2);
			canvas.width = Math.round(cssW * dpr);
			canvas.height = Math.round(cssH * dpr);
			ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		}

		function paint(now) {
			if (cssW < 2 || cssH < 2) {
				return;
			}
			var eye = drawStage(ctx, cssW, cssH, state, now, {
				fontReady: fontReady,
				reduced: reduced.matches,
				motes: motes,
				stacked: mobile.matches
			});
			var rect = canvas.getBoundingClientRect();
			eyeScreen = { x: rect.left + eye.eyeX, y: rect.top + eye.eyeY };
		}

		function applyGaze() {
			if (!pointer || mobile.matches || !eyeScreen) {
				return;
			}
			var angle = gazeAngle(eyeScreen, pointer);
			if (angle == null) {
				return;
			}
			var dist = Math.hypot(pointer.x - eyeScreen.x, pointer.y - eyeScreen.y);
			var reach = Math.min(window.innerWidth, window.innerHeight) * 0.46;
			desired.angle = angle;
			desired.influence = Math.max(0.18, Math.min(1, dist / reach));
				desired.explode = 0.55 + desired.influence * 0.5;
			hasPointer = true;
			if (hint) {
				hint.classList.add('is-hidden');
			}
		}

		function updateDesired(now) {
			if (mobile.matches && !reduced.matches) {
				var t = now / 1000;
				desired.angle = t * 0.62;
				desired.influence = 0.78;
				desired.explode = 0.72 + Math.sin(t * 0.8) * 0.08;
				return;
			}
			if (!hasPointer && !reduced.matches) {
				var idle = now / 1000;
				desired.angle = 0.85 + Math.sin(idle * 0.42) * 0.7;
				desired.influence = 0.58 + Math.sin(idle * 0.55) * 0.08;
				desired.explode = 0.76;
				return;
			}
			if (!mobile.matches) {
				applyGaze();
			}
		}

		function tick(now) {
			raf = 0;
			if (document.hidden || !visible) {
				return;
			}
			var dt = last ? Math.min(34, now - last) : 16;
			last = now;
			updateDesired(now);
			if (!reduced.matches) {
				for (var i = 0; i < motes.length; i++) {
					motes[i].y -= motes[i].v * dt;
					if (motes[i].y < 0) {
						motes[i].y = 1;
					}
				}
			}
			var k = reduced.matches ? 1 : 1 - Math.pow(0.001, dt / 16.7);
			state.angle = dampAngle(state.angle, desired.angle, k);
			state.influence += (desired.influence - state.influence) * k;
			state.explode += (desired.explode - state.explode) * k;
			paint(now);
			if (!reduced.matches) {
				raf = window.requestAnimationFrame(tick);
			}
		}

		function ensure() {
			if (!raf && !reduced.matches) {
				raf = window.requestAnimationFrame(tick);
			}
		}

		function onPointer(event) {
			pointer = { x: event.clientX, y: event.clientY };
			if (mobile.matches) {
				return;
			}
			if (reduced.matches) {
				applyGaze();
				state.angle = desired.angle;
				state.influence = desired.influence;
				state.explode = desired.explode;
				paint(performance.now());
				return;
			}
			ensure();
		}

		function onMedia() {
			if (reduced.matches) {
				window.cancelAnimationFrame(raf);
				raf = 0;
				if (!hasPointer) {
					state.angle = 0.95;
					state.influence = 0.62;
					state.explode = 0.78;
				}
				paint(performance.now());
				return;
			}
			ensure();
		}

		resize();
		paint(performance.now());
		ensure();

		window.addEventListener('pointermove', onPointer, { passive: true });
		window.addEventListener('pointerdown', onPointer, { passive: true });
		window.addEventListener('resize', function () {
			resize();
			if (pointer) {
				applyGaze();
			}
			paint(performance.now());
		});
		window.addEventListener('scroll', function () {
			if (pointer && !mobile.matches) {
				applyGaze();
				paint(performance.now());
			}
		}, { passive: true });
		if (mobile.addEventListener) {
			mobile.addEventListener('change', onMedia);
			reduced.addEventListener('change', onMedia);
		}
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				last = 0;
				ensure();
			}
		});
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
				if (visible) {
					last = 0;
					ensure();
					paint(performance.now());
				}
			}, { threshold: 0.04 });
			io.observe(root);
		}
		if ('ResizeObserver' in window) {
			var ro = new ResizeObserver(function () {
				resize();
				paint(performance.now());
			});
			ro.observe(scene);
		}
	}

	return {
		boot: boot,
		drawStage: drawStage,
		gazeAngle: gazeAngle,
		poseOf: poseOf,
		wrap: wrap
	};
});
