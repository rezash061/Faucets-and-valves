import * as THREE from 'three';
import { gsap } from './gsap.js';
import { setupRenderer, makeEnvironment, makeMaterials, waterTexture } from './materials.js';

export const R = 1.6; // pipe radius

/* ---------- geometry helpers ---------- */
export function cyl(r, len, mat, seg = 40) {
	const m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, len, seg), mat);
	m.castShadow = true; m.receiveShadow = true;
	return m;
}
export function alongX(mesh) { mesh.rotation.z = Math.PI / 2; return mesh; }
export function hex(r, len, mat) { return cyl(r, len, mat, 6); }

export function pipeX(x1, x2, y, mat) {
	const m = alongX(cyl(R, Math.abs(x2 - x1), mat));
	m.position.set((x1 + x2) / 2, y, 0);
	return m;
}
function pipeY(x, y1, y2, mat) {
	const m = cyl(R, Math.abs(y2 - y1), mat);
	m.position.set(x, (y1 + y2) / 2, 0);
	return m;
}
/** Quarter elbow; corner = where the two pipe axes meet; dirA/dirB unit vectors of the legs. */
function elbow(corner, quadrantRotZ, mat) {
	const bend = 3.2;
	const t = new THREE.Mesh(new THREE.TorusGeometry(bend, R, 24, 32, Math.PI / 2), mat);
	t.castShadow = true;
	t.position.copy(corner);
	t.rotation.z = quadrantRotZ;
	return t;
}
export function collar(x, y, axis, mat) {
	const c = hex(R * 1.32, 1.6, mat);
	if (axis === 'x') alongX(c);
	c.position.set(x, y, 0);
	return c;
}
function tee(x, y, mat) {
	const g = new THREE.Group();
	const s = new THREE.Mesh(new THREE.SphereGeometry(R * 1.28, 32, 24), mat);
	s.castShadow = true; g.add(s);
	g.position.set(x, y, 0);
	return g;
}
function bracket(x, y, axis, mats) {
	const g = new THREE.Group();
	const ring = new THREE.Mesh(new THREE.TorusGeometry(R * 1.12, 0.22, 10, 32), mats.satinNickel);
	ring.castShadow = true;
	if (axis === 'x') ring.rotation.y = Math.PI / 2;
	else ring.rotation.x = Math.PI / 2;
	g.add(ring);
	const rod = cyl(0.3, 5.2, mats.satinNickel, 12); rod.rotation.x = Math.PI / 2; rod.position.z = -3.4; g.add(rod);
	const plate = cyl(1.2, 0.4, mats.satinNickel, 24); plate.rotation.x = Math.PI / 2; plate.position.z = -5.9; g.add(plate);
	g.position.set(x, y, 0);
	return g;
}

/* ---------- valves ---------- */
function handwheel(mat, radius = 3.2) {
	const w = new THREE.Group();
	const rim = new THREE.Mesh(new THREE.TorusGeometry(radius, 0.38, 14, 48), mat);
	rim.rotation.x = Math.PI / 2; rim.castShadow = true; w.add(rim);
	for (let i = 0; i < 3; i++) {
		const sp = cyl(0.26, radius * 2, mat, 10);
		sp.rotation.z = Math.PI / 2; sp.rotation.y = (i * Math.PI) / 3; w.add(sp);
	}
	const hub = cyl(0.8, 0.9, mat, 20); w.add(hub);
	return w;
}

export function gateValve(m, wheelMat) {
	const g = new THREE.Group();
	const body = alongX(cyl(2.3, 5.2, m.brass, 10)); g.add(body);
	g.add(alongX(hex(2.25, 1.6, m.brass)).translateY(-3.4));
	g.add(alongX(hex(2.25, 1.6, m.brass)).translateY(3.4));
	const bonnet = cyl(1.75, 4.6, m.brass, 28); bonnet.position.y = 3.6; g.add(bonnet);
	const flange = cyl(2.1, 0.8, m.brass, 6); flange.position.y = 1.9; g.add(flange);
	const moving = new THREE.Group();
	const stem = cyl(0.36, 6, m.chrome, 16); stem.position.y = 6; moving.add(stem);
	const wheel = handwheel(wheelMat); wheel.position.y = 8.3; moving.add(wheel);
	g.add(moving);
	return { group: g, open: (on) => gsap.timeline()
		.to(wheel.rotation, { y: on ? -Math.PI * 6 : 0, duration: 1.4, ease: 'power2.inOut' }, 0)
		.to(moving.position, { y: on ? 1.6 : 0, duration: 1.4, ease: 'power2.inOut' }, 0) };
}

export function ballValve(m) {
	const g = new THREE.Group();
	const ball = new THREE.Mesh(new THREE.SphereGeometry(2.5, 36, 24), m.brassPolished); ball.scale.set(1.15, 1, 1); ball.castShadow = true; g.add(ball);
	g.add(alongX(hex(2.15, 1.8, m.brass)).translateY(-3.6));
	g.add(alongX(hex(2.15, 1.8, m.brass)).translateY(3.6));
	const stem = cyl(0.55, 2.2, m.brass, 16); stem.position.y = 3.2; g.add(stem);
	const pivot = new THREE.Group(); pivot.position.y = 4.4; g.add(pivot);
	const lever = new THREE.Mesh(new THREE.BoxGeometry(10, 0.7, 1.7), m.vinylLever); lever.position.x = -4.2; lever.castShadow = true; pivot.add(lever);
	const nut = hex(0.9, 0.9, m.satinNickel); pivot.add(nut);
	pivot.rotation.y = -Math.PI / 2; // closed: lever across the pipe, toward the viewer
	return { group: g, open: (on) => gsap.to(pivot.rotation, { y: on ? 0 : -Math.PI / 2, duration: 0.8, ease: 'back.out(1.6)' }) };
}

export function globeValve(m) {
	const g = new THREE.Group();
	const bulb = new THREE.Mesh(new THREE.SphereGeometry(3, 36, 24), m.brass); bulb.scale.set(1.25, 0.95, 1); bulb.castShadow = true; g.add(bulb);
	g.add(alongX(hex(2.2, 1.6, m.brass)).translateY(-4.1));
	g.add(alongX(hex(2.2, 1.6, m.brass)).translateY(4.1));
	const bonnet = cyl(1.6, 3.6, m.brass, 28); bonnet.position.y = 3.6; g.add(bonnet);
	const moving = new THREE.Group();
	const stem = cyl(0.34, 4.6, m.chrome, 16); stem.position.y = 6.2; moving.add(stem);
	const wheel = handwheel(m.iron, 2.8); wheel.position.y = 8; moving.add(wheel);
	g.add(moving);
	return { group: g, open: (on) => gsap.timeline()
		.to(wheel.rotation, { y: on ? -Math.PI * 4 : 0, duration: 1.2, ease: 'power2.inOut' }, 0)
		.to(moving.position, { y: on ? 1.1 : 0, duration: 1.2, ease: 'power2.inOut' }, 0) };
}

export function checkValve(m) {
	const g = new THREE.Group();
	g.add(alongX(cyl(2.4, 6.4, m.brass, 32)));
	g.add(alongX(hex(2.2, 1.5, m.brass)).translateY(-3.9));
	g.add(alongX(hex(2.2, 1.5, m.brass)).translateY(3.9));
	const cap = hex(1.9, 1.8, m.brass); cap.position.y = 2.9; g.add(cap);
	const arrow = new THREE.Mesh(new THREE.ConeGeometry(0.7, 1.6, 3), m.brassPolished); arrow.rotation.z = Math.PI / 2; arrow.position.set(-1.4, 0, 2.35); g.add(arrow);
	return { group: g, open: (on) => gsap.fromTo(cap.position, { y: 2.9 }, { y: on ? 3.3 : 2.9, duration: 0.25, yoyo: on, repeat: on ? 1 : 0 }) };
}

/* ---------- glass sight section with water ---------- */
export function sightGlass(xRight, xLeft, y, m, wTex) {
	const g = new THREE.Group();
	const len = xRight - xLeft;
	const glass = alongX(cyl(R * 1.12, len, m.glass, 40)); glass.castShadow = false; g.add(glass);
	const pivot = new THREE.Group(); pivot.position.x = len / 2; g.add(pivot); // fills from the valve (right) side
	const mat = new THREE.MeshPhysicalMaterial({ map: wTex.clone(), color: 0xffffff, roughness: 0.08, clearcoat: 1, transparent: true, opacity: 0.92 });
	mat.map.needsUpdate = true;
	mat.map.repeat.set(1.5, 1);
	const water = alongX(cyl(R * 0.98, len, mat, 32)); water.position.x = -len / 2; water.castShadow = false;
	pivot.add(water);
	g.add(collar(len / 2 + 0.6, 0, 'x', m.brass));
	g.add(collar(-len / 2 - 0.6, 0, 'x', m.brass));
	g.position.set((xRight + xLeft) / 2, y, 0);
	return { group: g, fill: pivot, tex: mat.map };
}

/* ---------- scene ---------- */
const STEP = 16; // vertical distance between branches

/**
 * @param {HTMLCanvasElement} canvas
 * @param {{branches: {key:string, kind:'ball'|'gate'|'globe'|'check'}[], onPick:Function, onHover?:Function}} opts
 */
export function createManifold(canvas, { branches: list, onPick, onHover }) {
	const renderer = setupRenderer(canvas);
	const scene = new THREE.Scene();
	scene.environment = makeEnvironment(renderer);
	scene.environmentIntensity = 0.85;
	const m = makeMaterials();
	const wTex = waterTexture();

	const camera = new THREE.PerspectiveCamera(26, 1, 1, 1000);
	const target = new THREE.Vector3(6, 0, 0);
	const baseTarget = new THREE.Vector3(6, 0, 0);
	const camHome = new THREE.Vector3(26, 8, 168);
	camera.position.copy(camHome);

	// Light: soft key from the upper left so shadows fall to the lower right on the wall.
	scene.add(new THREE.HemisphereLight(0xffffff, 0xb8b2a8, 0.55));
	const key = new THREE.DirectionalLight(0xfff4e6, 2.2);
	key.position.set(-40, 60, 70);
	key.castShadow = true;
	key.shadow.mapSize.set(2048, 2048);
	Object.assign(key.shadow.camera, { left: -90, right: 90, top: 70, bottom: -70, near: 10, far: 260 });
	key.shadow.radius = 6; key.shadow.bias = -0.0004;
	scene.add(key);

	const wall = new THREE.Mesh(new THREE.PlaneGeometry(420, 260), m.wall);
	wall.position.z = -6.1; wall.receiveShadow = true; scene.add(wall);

	const net = new THREE.Group(); scene.add(net);
	const HX = 40, END = -48, VX = 22, GL = [14, 4];
	const n = Math.max(1, list.length);
	const top = ((n - 1) / 2) * STEP;
	const ys = list.map((_, i) => top - i * STEP);

	// Inlet with main gate valve and an always-flowing glass section.
	net.add(pipeX(HX, 120, 0, m.copper));
	const main = gateValve(m, m.redWheel); main.group.position.set(70, 0, 0); net.add(main.group);
	const inletGlass = sightGlass(58, 48, 0, m, wTex); net.add(inletGlass.group);
	net.add(bracket(84, 0, 'x', m), bracket(100, 0, 'x', m));
	// Header: a vertical pipe with elbows at both ends and tees where branches leave.
	if (n > 1) {
		net.add(pipeY(HX, top - 3.2, -top + 3.2, m.copper));
		net.add(elbow(new THREE.Vector3(HX - 3.2, top - 3.2, 0), 0, m.copper));
		net.add(elbow(new THREE.Vector3(HX - 3.2, -top + 3.2, 0), -Math.PI / 2, m.copper));
		ys.slice(1, -1).forEach((y) => net.add(tee(HX, y, m.brass)));
		if (!ys.some((y) => Math.abs(y) < 0.01)) net.add(tee(HX, 0, m.brass));
		for (let i = 0; i < n - 1; i++) net.add(bracket(HX, (ys[i] + ys[i + 1]) / 2, 'y', m));
	}

	const VALVES = { ball: ballValve, gate: (mm) => gateValve(mm, m.redWheel), globe: globeValve, check: checkValve };
	const branches = list.map((b, i) => {
		const y = ys[i];
		const edge = n > 1 && (i === 0 || i === n - 1);
		const startX = edge ? HX - 3.2 : HX;
		const parts = new THREE.Group(); net.add(parts);
		parts.add(pipeX(startX, VX + 4.5, y, m.copper));
		parts.add(pipeX(VX - 4.5, GL[0] + 1.2, y, m.copper));
		parts.add(pipeX(GL[1] - 1.2, END, y, m.copper));
		const v = (VALVES[b.kind] || ballValve)(m);
		v.group.position.set(VX, y, 0); parts.add(v.group);
		const sg = sightGlass(GL[0], GL[1], y, m, wTex); parts.add(sg.group);
		sg.fill.scale.x = 0.0001;
		const capEnd = alongX(hex(R * 1.35, 2, m.brass)); capEnd.position.set(END - 0.8, y, 0); parts.add(capEnd);
		parts.add(bracket(-10, y, 'x', m), bracket(-34, y, 'x', m));
		const hit = new THREE.Mesh(new THREE.BoxGeometry(HX - END + 6, 12, 10), new THREE.MeshBasicMaterial({ visible: false }));
		hit.position.set((HX + END) / 2, y + 2, 0); hit.userData.key = b.key; net.add(hit);
		return { ...b, y, valve: v, glass: sg, hit, labelAnchor: new THREE.Vector3(VX - 7, y + 3.2, 0) };
	});
	const hits = branches.map((b) => b.hit);

	/* ---------- interaction ---------- */
	const ray = new THREE.Raycaster();
	const ptr = new THREE.Vector2();
	let hovered = null, openKey = null;
	function pick(ev) {
		const r = canvas.getBoundingClientRect();
		ptr.set(((ev.clientX - r.left) / r.width) * 2 - 1, -((ev.clientY - r.top) / r.height) * 2 + 1);
		ray.setFromCamera(ptr, camera);
		const h = ray.intersectObjects(hits)[0];
		return h ? h.object.userData.key : null;
	}
	canvas.addEventListener('pointermove', (ev) => {
		const k = pick(ev);
		if (k !== hovered) { hovered = k; canvas.style.cursor = k ? 'pointer' : ''; onHover && onHover(k); }
	});
	canvas.addEventListener('click', (ev) => { const k = pick(ev); if (k) onPick(k); });

	function setOpen(key) {
		branches.forEach((b) => {
			const on = b.key === key;
			const was = b.key === openKey;
			if (on !== was) b.valve.open(on);
			gsap.to(b.glass.fill.scale, { x: on ? 1 : 0.0001, duration: on ? 1.1 : 0.5, delay: on ? 0.45 : 0, ease: on ? 'power2.out' : 'power2.in' });
		});
		openKey = key;
		const b = branches.find((x) => x.key === key);
		gsap.to(target, { y: baseTarget.y + (b ? b.y * 0.25 : 0), x: baseTarget.x + (b ? -10 : 0), duration: 1.2, ease: 'power2.inOut' });
	}

	/* ---------- labels: project anchors to screen ---------- */
	const v3 = new THREE.Vector3();
	function project(vec) {
		v3.copy(vec).project(camera);
		const r = canvas.getBoundingClientRect();
		return { x: (v3.x * 0.5 + 0.5) * r.width, y: (-v3.y * 0.5 + 0.5) * r.height };
	}

	/* ---------- size + loop ---------- */
	let w = 0, h = 0;
	function resize() {
		const r = canvas.parentElement.getBoundingClientRect();
		w = r.width; h = r.height;
		if (!w || !h) return;
		renderer.setSize(w, h, false);
		camera.aspect = w / h;
		// Fit the drawing: narrower screens move the camera back.
		// Fit the drawing: wide screens show the whole network, tall screens show the branches.
		const wide = camera.aspect > 1;
		const halfW = wide ? 86 : 47, halfH = Math.max(wide ? 36 : 40, top + (wide ? 19 : 18));
		const vf = THREE.MathUtils.degToRad(camera.fov / 2);
		const hf = Math.atan(Math.tan(vf) * camera.aspect);
		const dist = Math.max(halfW / Math.tan(hf), halfH / Math.tan(vf)) * 1.04;
		baseTarget.set(wide ? 28 : -4, wide ? -1 : 0, 0);
		target.copy(baseTarget);
		camHome.set(baseTarget.x + dist * 0.12, baseTarget.y + dist * 0.06, dist);
		camera.updateProjectionMatrix();
	}
	new ResizeObserver(resize).observe(canvas.parentElement);
	resize();

	let running = true, alive = true, last = performance.now();
	const mouse = { x: 0, y: 0 };
	window.addEventListener('pointermove', (e) => { mouse.x = e.clientX / window.innerWidth - 0.5; mouse.y = e.clientY / window.innerHeight - 0.5; });
	const camOffset = new THREE.Vector3();
	function frame(now) {
		const dt = Math.min(0.05, (now - last) / 1000); last = now;
		if (running) {
			const flow = dt * 0.9;
			inletGlass.tex.offset.x += flow;
			branches.forEach((b) => { b.glass.tex.offset.x += flow * 1.6; });
			camOffset.set(mouse.x * camHome.z * 0.04, -mouse.y * camHome.z * 0.025, 0);
			camera.position.lerp(new THREE.Vector3().copy(camHome).add(camOffset), 1 - Math.pow(0.0009, dt));
			camera.lookAt(target);
			renderer.render(scene, camera);
			onFrame && onFrame();
		}
		if (alive) requestAnimationFrame(frame);
	}
	let onFrame = null;
	requestAnimationFrame(frame);
	new IntersectionObserver(([e]) => { running = e.isIntersecting; }).observe(canvas);

	return {
		camera, camHome, target, branches, project, setOpen,
		setOnFrame: (fn) => { onFrame = fn; },
		intro() {
			// Start close and high; the per-frame easing pulls the camera back to its home framing.
			camera.position.copy(camHome).add(new THREE.Vector3(-camHome.z * 0.2, camHome.z * 0.12, -camHome.z * 0.45));
			setTimeout(() => main.open(true), 900);
		},
		openMain() { main.open(true); },
	};
}
