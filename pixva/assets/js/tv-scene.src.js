/**
 * هیروی پیکسوا — تلویزیون OLED واقع‌گرا با نمای بازشونده.
 * منبع خوانا. خروجی باندل‌شده: tv-scene.js
 *   cd /tmp/tvbundle && npx esbuild /home/user/wpwebtv/pixva/assets/js/tv-scene.src.js --bundle --format=iife --minify --outfile=/home/user/wpwebtv/pixva/assets/js/tv-scene.js
 */
import * as THREE from 'three';

function screenTexture() {
	const canvas = document.createElement('canvas');
	canvas.width = 1024;
	canvas.height = 576;
	const ctx = canvas.getContext('2d');
	const wash = ctx.createLinearGradient(0, 0, 1024, 576);
	wash.addColorStop(0, '#1a3d5c');
	wash.addColorStop(0.42, '#10243a');
	wash.addColorStop(1, '#071018');
	ctx.fillStyle = wash;
	ctx.fillRect(0, 0, 1024, 576);

	const streak = ctx.createLinearGradient(80, 0, 720, 520);
	streak.addColorStop(0, 'rgba(255,255,255,0.22)');
	streak.addColorStop(0.28, 'rgba(255,255,255,0.05)');
	streak.addColorStop(0.55, 'rgba(255,255,255,0)');
	ctx.fillStyle = streak;
	ctx.fillRect(0, 0, 1024, 576);

	const vignette = ctx.createRadialGradient(512, 300, 40, 512, 288, 620);
	vignette.addColorStop(0, 'rgba(0,0,0,0)');
	vignette.addColorStop(1, 'rgba(0,0,0,0.42)');
	ctx.fillStyle = vignette;
	ctx.fillRect(0, 0, 1024, 576);

	const tex = new THREE.CanvasTexture(canvas);
	tex.colorSpace = THREE.SRGBColorSpace;
	tex.anisotropy = 4;
	return tex;
}

function shadowTexture() {
	const canvas = document.createElement('canvas');
	canvas.width = 256;
	canvas.height = 256;
	const ctx = canvas.getContext('2d');
	const g = ctx.createRadialGradient(128, 128, 12, 128, 128, 120);
	g.addColorStop(0, 'rgba(11,28,46,0.28)');
	g.addColorStop(1, 'rgba(11,28,46,0)');
	ctx.fillStyle = g;
	ctx.fillRect(0, 0, 256, 256);
	const tex = new THREE.CanvasTexture(canvas);
	tex.colorSpace = THREE.SRGBColorSpace;
	return tex;
}

function metal(color, roughness, metalness) {
	return new THREE.MeshStandardMaterial({
		color,
		roughness,
		metalness,
	});
}

function addBox(parent, w, h, d, material, x, y, z) {
	const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), material);
	mesh.position.set(x, y, z);
	parent.add(mesh);
	return mesh;
}

function initTV(root) {
	const canvas = root.querySelector('[data-pixva-tv]');
	if (!canvas) {
		return;
	}
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const mobile = window.matchMedia('(max-width: 760px)').matches;

	let renderer;
	try {
		renderer = new THREE.WebGLRenderer({
			canvas,
			antialias: !mobile,
			alpha: true,
			powerPreference: 'high-performance',
		});
	} catch (error) {
		return;
	}
	renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.25 : 1.6));
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = THREE.ACESFilmicToneMapping;
	renderer.toneMappingExposure = 1.08;

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 20);
	camera.position.set(1.15, 0.42, 2.85);

	scene.add(new THREE.HemisphereLight(0xf7fbff, 0xb7c3d1, 0.85));
	scene.add(new THREE.AmbientLight(0xffffff, 0.22));
	const key = new THREE.DirectionalLight(0xfff6ee, 1.85);
	key.position.set(2.4, 3.2, 2.2);
	scene.add(key);
	const rim = new THREE.DirectionalLight(0x9ec9ff, 0.55);
	rim.position.set(-2.2, 1.1, -1.6);
	scene.add(rim);

	const tv = new THREE.Group();
	scene.add(tv);

	const bezelMat = metal(0x1c222b, 0.32, 0.78);
	const glassMat = new THREE.MeshStandardMaterial({
		color: 0x0e1c2e,
		map: screenTexture(),
		roughness: 0.16,
		metalness: 0.08,
		emissive: 0x123049,
		emissiveIntensity: 0.35,
	});
	const coverMat = metal(0x2c333d, 0.52, 0.42);
	const chassisMat = metal(0x8d96a1, 0.4, 0.62);
	const pcbMat = metal(0x0f4a32, 0.62, 0.12);
	const chipMat = metal(0x1a1d22, 0.45, 0.35);
	const ledMat = new THREE.MeshStandardMaterial({
		color: 0xd9fff8,
		emissive: 0xb6fff4,
		emissiveIntensity: 1.8,
		roughness: 0.25,
		metalness: 0.05,
	});
	const flexMat = metal(0xc56a38, 0.7, 0.08);
	const standMat = metal(0xd5dbe3, 0.22, 0.86);

	const W = 1.66;
	const H = 0.94;
	const bezel = 0.02;

	const front = new THREE.Group();
	tv.add(front);
	addBox(front, W, bezel, 0.03, bezelMat, 0, H / 2 - bezel / 2, 0.02);
	addBox(front, W, 0.045, 0.03, bezelMat, 0, -H / 2 + 0.02, 0.02);
	addBox(front, bezel, H, 0.03, bezelMat, -W / 2 + bezel / 2, 0, 0.02);
	addBox(front, bezel, H, 0.03, bezelMat, W / 2 - bezel / 2, 0, 0.02);
	const screen = addBox(front, W - 0.05, H - 0.08, 0.008, glassMat, 0, 0.01, 0.012);
	screen.material.polygonOffset = true;
	const led = addBox(front, 0.012, 0.012, 0.01, new THREE.MeshStandardMaterial({
		color: 0xff5c35,
		emissive: 0xff5c35,
		emissiveIntensity: 0.8,
		roughness: 0.4,
	}), 0, -H / 2 + 0.02, 0.038);

	const backlight = new THREE.Group();
	const mainboard = new THREE.Group();
	const power = new THREE.Group();
	const ports = new THREE.Group();
	tv.add(backlight, mainboard, power, ports);
	addBox(backlight, W - 0.08, H - 0.1, 0.01, chassisMat, 0, 0, -0.01);
	for (let i = 0; i < 5; i += 1) {
		addBox(backlight, W - 0.22, 0.012, 0.008, ledMat, 0, 0.28 - i * 0.14, 0.002);
	}
	addBox(mainboard, 0.52, 0.28, 0.014, pcbMat, 0.32, -0.08, -0.02);
	addBox(mainboard, 0.14, 0.09, 0.016, chipMat, 0.26, -0.04, -0.006);
	addBox(mainboard, 0.08, 0.06, 0.014, chipMat, 0.42, -0.12, -0.006);
	addBox(power, 0.34, 0.16, 0.014, metal(0x16324a, 0.5, 0.25), -0.42, -0.16, -0.02);
	addBox(power, 0.08, 0.05, 0.02, chipMat, -0.4, -0.14, -0.004);
	addBox(ports, 0.22, 0.018, 0.01, metal(0xc6a15a, 0.35, 0.7), 0.5, -0.28, -0.01);
	addBox(ports, 0.34, 0.018, 0.006, flexMat, -0.08, -0.32, -0.008);

	const cover = new THREE.Group();
	tv.add(cover);
	addBox(cover, W - 0.02, H - 0.02, 0.016, coverMat, 0, 0, -0.028);
	addBox(cover, 0.22, 0.05, 0.01, metal(0x1a1f26, 0.5, 0.3), 0.42, 0.28, -0.012);
	addBox(cover, 0.5, 0.08, 0.008, metal(0x1a1f26, 0.55, 0.2), -0.3, -0.22, -0.012);

	const stand = new THREE.Group();
	tv.add(stand);
	addBox(stand, 0.08, 0.05, 0.08, standMat, 0, -H / 2 - 0.02, 0);
	addBox(stand, 0.62, 0.012, 0.18, standMat, 0, -H / 2 - 0.07, 0.02);

	const shadow = new THREE.Mesh(
		new THREE.PlaneGeometry(2.4, 1.1),
		new THREE.MeshBasicMaterial({ map: shadowTexture(), transparent: true, depthWrite: false })
	);
	shadow.rotation.x = -Math.PI / 2;
	shadow.position.y = -H / 2 - 0.08;
	scene.add(shadow);

	const look = new THREE.Vector3(0, -0.02, 0);
	camera.lookAt(look);

	let open = reduce ? 0.72 : 0;
	let target = open;
	let locked = false;
	let aimX = 0;
	let aimY = 0;
	let parallaxX = 0;
	let parallaxY = 0;
	const clock = new THREE.Clock();

	function applyOpen(amount) {
		front.position.z = amount * 0.46;
		backlight.position.z = amount * 0.1;
		mainboard.position.z = -amount * 0.22;
		power.position.z = -amount * 0.38;
		ports.position.z = -amount * 0.52;
		cover.position.z = -0.02 - amount * 0.72;
		cover.rotation.x = -amount * 0.32;
		cover.rotation.y = amount * 0.12;
		led.material.emissiveIntensity = 0.35 + amount * 1.1;
	}

	function resize() {
		const width = root.clientWidth || 640;
		const height = root.clientHeight || 520;
		renderer.setSize(width, height, false);
		camera.aspect = width / Math.max(height, 1);
		camera.updateProjectionMatrix();
	}

	function setTarget(value) {
		target = value;
	}

	root.addEventListener('pointermove', function (event) {
		const box = root.getBoundingClientRect();
		if (!box.width || !box.height) {
			return;
		}
		aimX = ((event.clientX - box.left) / box.width - 0.5) * 0.16;
		aimY = ((event.clientY - box.top) / box.height - 0.5) * 0.1;
	});
	root.addEventListener('pointerenter', function () {
		if (!locked) {
			setTarget(1);
		}
	});
	root.addEventListener('pointerleave', function () {
		if (!locked) {
			setTarget(reduce ? 0.72 : 0);
		}
	});
	root.addEventListener('click', function () {
		locked = !locked;
		setTarget(locked ? 1 : 0);
		root.classList.toggle('is-open', locked);
	});

	const hint = root.querySelector('[data-tv-hint]');
	if (hint && !reduce) {
		hint.hidden = false;
	}

	resize();
	window.addEventListener('resize', resize);
	applyOpen(open);
	root.classList.add('is-live');

	if (reduce) {
		tv.rotation.y = 0.68;
		renderer.render(scene, camera);
		return;
	}

	let running = true;
	const io = new IntersectionObserver(function (entries) {
		running = entries.some(function (entry) {
			return entry.isIntersecting;
		});
	});
	io.observe(root);

	renderer.setAnimationLoop(function () {
		if (!running || document.hidden) {
			return;
		}
		const t = clock.getElapsedTime();
		open += (target - open) * 0.055;
		applyOpen(open);
		parallaxX += (aimX - parallaxX) * 0.06;
		parallaxY += (aimY - parallaxY) * 0.06;
		const rect = root.getBoundingClientRect();
		const scroll = Math.min(1, Math.max(0, 1 - rect.top / window.innerHeight));
		tv.rotation.y = 0.55 + Math.sin(t * 0.28) * 0.05 + parallaxX;
		tv.rotation.x = parallaxY * 0.55;
		tv.position.y = Math.sin(t * 0.55) * 0.018;
		camera.position.z = 2.85 - scroll * 0.22;
		camera.lookAt(look);
		renderer.render(scene, camera);
	});
}

function boot() {
	const root = document.querySelector('[data-pixva-tv-stage]');
	if (root) {
		initTV(root);
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot);
} else {
	boot();
}
