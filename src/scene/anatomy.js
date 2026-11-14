import * as THREE from 'three';
import { setupRenderer, makeEnvironment, makeMaterials } from './materials.js';

function mesh(geo, mat) { const m = new THREE.Mesh(geo, mat); m.castShadow = true; m.receiveShadow = true; return m; }
function lathe(points, mat, seg = 64) { return mesh(new THREE.LatheGeometry(points.map(([x, y]) => new THREE.Vector2(x, y)), seg), mat); }
function hexNut(r, h, mat) { return mesh(new THREE.CylinderGeometry(r, r, h, 6), mat); }

/**
 * A single-lever basin mixer built from its real parts.
 * Each part: { obj, explode: Vector3 offset, anchor: local point for its label, label, sub }.
 */
export function buildFaucet(m) {
	const parts = [];
	const add = (obj, explode, anchor, label, sub) => { parts.push({ obj, explode, anchor, label, sub, home: obj.position.clone() }); return obj; };

	// Lever.
	const lever = new THREE.Group();
	const arm = mesh(new THREE.CapsuleGeometry(0.42, 8.2, 8, 20), m.chrome); arm.rotation.z = Math.PI / 2; arm.position.x = 4.2; lever.add(arm);
	const lhub = mesh(new THREE.CylinderGeometry(0.9, 0.9, 1.4, 32), m.chrome); lever.add(lhub);
	lever.position.set(0, 23.9, 0); lever.rotation.z = 0.28;
	add(lever, new THREE.Vector3(0, 17, 0), new THREE.Vector3(-1, 0, 0), 'اهرم', 'برنج با آبکاری کروم');

	// Cap (bonnet) with dome.
	const cap = lathe([[0, 0], [1.98, 0], [1.98, 2.3], [1.75, 2.85], [1.1, 3.15], [0, 3.2]], m.chrome);
	cap.position.y = 20.2;
	add(cap, new THREE.Vector3(0, 12, 0), new THREE.Vector3(-2, 1.4, 0), 'کلاهک', 'پیچ‌شو، قابل تعویض');

	// Ceramic cartridge.
	const cart = new THREE.Group();
	cart.add(mesh(new THREE.CylinderGeometry(1.45, 1.45, 3.6, 48), m.ceramic));
	[-0.9, 0.7].forEach((y) => { const ring = mesh(new THREE.TorusGeometry(1.46, 0.09, 8, 48), m.plastic); ring.rotation.x = Math.PI / 2; ring.position.y = y; cart.add(ring); });
	const base = mesh(new THREE.CylinderGeometry(1.5, 1.5, 0.5, 48), m.plastic); base.position.y = -2; cart.add(base);
	const cstem = mesh(new THREE.CylinderGeometry(0.34, 0.34, 1.5, 16), m.brassPolished); cstem.position.y = 2.5; cart.add(cstem);
	cart.position.y = 19.4;
	add(cart, new THREE.Vector3(0, 6.5, 0), new THREE.Vector3(-1.5, 0, 0), 'کارتریج سرامیکی', '۳۵ میلی‌متر، ۵۰۰٬۰۰۰ بار باز و بسته');

	// Body.
	const body = lathe([[0, 0], [1.95, 0], [1.95, 19.4], [1.85, 20.2], [0, 20.2]], m.chrome);
	body.position.y = 0.8;
	add(body, new THREE.Vector3(0, 0, 0), new THREE.Vector3(-2, 6, 0), 'بدنه', 'برنج ریخته‌گری، ۳۲ سانتی‌متر');

	// Spout with aerator.
	const spout = new THREE.Group();
	const tube = mesh(new THREE.CapsuleGeometry(0.88, 10.6, 10, 32), m.chrome); tube.rotation.z = Math.PI / 2; tube.position.x = -5.3; spout.add(tube);
	const aer = mesh(new THREE.CylinderGeometry(0.72, 0.72, 0.9, 32), m.satinNickel); aer.position.set(-10.2, -0.8, 0); spout.add(aer);
	const mesh_ = mesh(new THREE.CircleGeometry(0.55, 24), m.plastic); mesh_.rotation.x = Math.PI / 2; mesh_.position.set(-10.2, -1.26, 0); spout.add(mesh_);
	spout.position.set(-1.6, 15.6, 0);
	add(spout, new THREE.Vector3(-8, 1, 0), new THREE.Vector3(-10.6, 0.6, 0), 'دهانه و سرشیر', 'سرشیر ضد رسوب، دهانهٔ ۱۴ سانتی‌متر');

	// O-ring.
	const ring = mesh(new THREE.TorusGeometry(2.05, 0.2, 12, 48), m.rubber); ring.rotation.x = Math.PI / 2; ring.position.y = 0.8;
	add(ring, new THREE.Vector3(0, -3.5, 0), new THREE.Vector3(-2.2, 0, 0), 'واشر آب‌بندی', 'لاستیک EPDM');

	// Base plate.
	const plate = lathe([[0, 0], [3.4, 0], [3.65, 0.25], [3.6, 0.55], [3.2, 0.75], [0, 0.75]], m.chrome);
	add(plate, new THREE.Vector3(0, -7, 0), new THREE.Vector3(-3.6, 0.4, 0), 'صفحهٔ پایه', 'ضخامت ۷ میلی‌متر');

	// Threaded shank + mounting nut.
	const shank = new THREE.Group();
	shank.add(mesh(new THREE.CylinderGeometry(0.7, 0.7, 7, 24), m.brass));
	for (let i = 0; i < 16; i++) { const t = mesh(new THREE.TorusGeometry(0.72, 0.07, 6, 24), m.brass); t.rotation.x = Math.PI / 2; t.position.y = -3.2 + i * 0.4; shank.add(t); }
	const nut = hexNut(1.7, 1.1, m.brass); nut.position.y = -1.6; shank.add(nut);
	shank.position.y = -3.6;
	add(shank, new THREE.Vector3(0, -12, 0), new THREE.Vector3(-1.7, -1.6, 0), 'پیچ و مهرهٔ نصب', 'برنج، رزوهٔ کامل');

	// Braided hoses with brass nuts.
	const hoses = new THREE.Group();
	[[-0.55, 1], [0.55, -1]].forEach(([x, s]) => {
		const curve = new THREE.CatmullRomCurve3([
			new THREE.Vector3(x, -1, 0), new THREE.Vector3(x + s * 0.4, -8, 0.6 * s), new THREE.Vector3(x + s * 1.8, -15, -0.8 * s), new THREE.Vector3(x + s * 1.2, -21, 0.4 * s),
		]);
		hoses.add(mesh(new THREE.TubeGeometry(curve, 64, 0.42, 16), m.braid));
		const end = curve.getPoint(1);
		const n = hexNut(0.95, 1.5, m.brassPolished); n.position.copy(end).add(new THREE.Vector3(0, -0.6, 0)); hoses.add(n);
	});
	hoses.position.y = -6.8;
	add(hoses, new THREE.Vector3(0, -18, 0), new THREE.Vector3(-1.4, -11, 0), 'شیلنگ استیل', 'اتصال ½ اینچ، ۴۰ سانتی‌متر');

	const group = new THREE.Group();
	parts.forEach((p) => group.add(p.obj));
	return { group, parts };
}

export function createAnatomy(canvas, { labels: names = [] } = {}) {
	const renderer = setupRenderer(canvas);
	renderer.toneMappingExposure = 1.1;
	const scene = new THREE.Scene();
	scene.environment = makeEnvironment(renderer);
	const m = makeMaterials();

	const camera = new THREE.PerspectiveCamera(24, 1, 1, 600);
	const target = new THREE.Vector3(-2, 6, 0);

	scene.add(new THREE.HemisphereLight(0xffffff, 0x9a9690, 0.5));
	const key = new THREE.DirectionalLight(0xffffff, 2.2);
	key.position.set(30, 60, 40); key.castShadow = true;
	key.shadow.mapSize.set(2048, 2048);
	Object.assign(key.shadow.camera, { left: -40, right: 40, top: 50, bottom: -50, near: 5, far: 200 });
	key.shadow.radius = 8;
	scene.add(key);
	const rim = new THREE.DirectionalLight(0xdfe8ff, 1.2); rim.position.set(-40, 20, -30); scene.add(rim);

	const floor = new THREE.Mesh(new THREE.PlaneGeometry(200, 200), new THREE.ShadowMaterial({ opacity: 0.22 }));
	floor.rotation.x = -Math.PI / 2; floor.position.y = -36; floor.receiveShadow = true; scene.add(floor);

	const { group, parts } = buildFaucet(m);
	// Labels can be replaced from WordPress (filter fv_anatomy_parts).
	parts.forEach((p, i) => { if (names[i]) { p.label = names[i][0] || p.label; p.sub = names[i][1] ?? p.sub; } });
	scene.add(group);

	let w = 0, h = 0;
	function resize() {
		const r = canvas.parentElement.getBoundingClientRect();
		w = r.width; h = r.height;
		renderer.setSize(w, h, false);
		camera.aspect = w / h;
		camera.updateProjectionMatrix();
	}
	new ResizeObserver(resize).observe(canvas.parentElement);
	resize();

	const state = { explode: 0, spin: -0.45 };
	const tmp = new THREE.Vector3();
	function apply() {
		const e = state.explode;
		// Ease each part with a small delay by index so they separate in sequence.
		parts.forEach((p, i) => {
			const k = Math.min(1, Math.max(0, e * 1.3 - i * 0.035));
			const s = k * k * (3 - 2 * k);
			p.obj.position.copy(p.home).addScaledVector(p.explode, s);
		});
		group.rotation.y = state.spin;
		// Pull back while exploded so all parts fit.
		const vf = THREE.MathUtils.degToRad(camera.fov) / 2;
		const halfH = 42 + e * 10; // assembled incl. hoses ~42, exploded stack ~52
		const fitH = halfH / Math.tan(vf);
		const fitW = 26 / (Math.tan(vf) * camera.aspect);
		const dist = Math.max(fitH, fitW) * 1.05;
		camera.position.set(Math.sin(0.25) * dist, 10 + e * 6, Math.cos(0.25) * dist);
		target.y = -2 - e * 2;
		camera.lookAt(target);
	}

	function labels() {
		const r = canvas.getBoundingClientRect();
		return parts.map((p) => {
			tmp.copy(p.anchor);
			p.obj.localToWorld(tmp);
			tmp.project(camera);
			return { x: (tmp.x * 0.5 + 0.5) * r.width, y: (-tmp.y * 0.5 + 0.5) * r.height, label: p.label, sub: p.sub };
		});
	}

	let running = false;
	function frame() {
		if (running) { apply(); renderer.render(scene, camera); onFrame && onFrame(); }
		requestAnimationFrame(frame);
	}
	let onFrame = null;
	requestAnimationFrame(frame);
	new IntersectionObserver(([e]) => { running = e.isIntersecting; }).observe(canvas);

	return { state, labels, setOnFrame: (fn) => { onFrame = fn; }, partsCount: parts.length };
}
