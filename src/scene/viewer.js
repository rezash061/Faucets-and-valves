/**
 * Product page 3D viewer.
 * 'ball' | 'gate' | 'globe' | 'check': the valve between two glass pipe sections.
 * Closed: water stands on the inlet side only. Open: it flows through to the outlet side.
 * Drag to turn the object.
 */
import * as THREE from 'three';
import { gsap } from './gsap.js';
import { setupRenderer, makeEnvironment, makeMaterials, waterTexture } from './materials.js';
import { gateValve, ballValve, globeValve, checkValve, pipeX, sightGlass, alongX, hex, R } from './manifold.js';

export function createViewer(canvas, { model, labels: names = [] }) {
	const renderer = setupRenderer(canvas);
	const scene = new THREE.Scene();
	scene.environment = makeEnvironment(renderer);
	const m = makeMaterials();
	const camera = new THREE.PerspectiveCamera(24, 1, 1, 800);
	const target = new THREE.Vector3();

	scene.add(new THREE.HemisphereLight(0xffffff, 0xb8b2a8, 0.6));
	const key = new THREE.DirectionalLight(0xfff4e6, 2.1);
	key.position.set(24, 60, 40); key.castShadow = true;
	key.shadow.mapSize.set(2048, 2048); key.shadow.radius = 8; key.shadow.bias = -0.0004;
	Object.assign(key.shadow.camera, { left: -50, right: 50, top: 50, bottom: -50, near: 5, far: 220 });
	scene.add(key);
	const rim = new THREE.DirectionalLight(0xdfe8ff, 1); rim.position.set(-40, 20, -30); scene.add(rim);
	const floor = new THREE.Mesh(new THREE.PlaneGeometry(400, 400), new THREE.ShadowMaterial({ opacity: 0.18 }));
	floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true; scene.add(floor);

	const holder = new THREE.Group(); scene.add(holder);
	const state = { explode: 0, open: false, spin: -0.5, tilt: 0 };
	let parts = [];
	let flow = null; // { inlet, outlet, tex[] }
	let valve = null;
	let fit; // (aspect) => distance

	{
		const wTex = waterTexture();
		const make = { ball: ballValve, gate: (mm) => gateValve(mm, m.redWheel), globe: globeValve, check: checkValve }[model] || ballValve;
		valve = make(m);
		holder.add(valve.group);
		// Inlet on the right (water arrives from the right, as on the home page), outlet on the left.
		holder.add(pipeX(4.6, 9.4, 0, m.copper), pipeX(-9.4, -4.6, 0, m.copper));
		const inlet = sightGlass(21, 10, 0, m, wTex);
		const outlet = sightGlass(-10, -21, 0, m, wTex);
		holder.add(inlet.group, outlet.group);
		holder.add(pipeX(21.6, 26, 0, m.copper), pipeX(-26, -21.6, 0, m.copper));
		[26.8, -26.8].forEach((x) => { const c = alongX(hex(R * 1.35, 1.8, m.brass)); c.position.x = x; holder.add(c); });
		inlet.fill.scale.x = 1;
		outlet.fill.scale.x = 0.0001;
		flow = { inlet, outlet };
		floor.position.y = -9;
		fit = (aspect) => {
			const vf = THREE.MathUtils.degToRad(camera.fov) / 2;
			return Math.max(16 / Math.tan(vf), 31 / (Math.tan(vf) * aspect)) * 1.08;
		};
		target.set(0, 2, 0);
	}
	holder.traverse((o) => { if (o.isMesh && o.material !== m.glass) o.castShadow = true; });

	/* ---------- drag to turn ---------- */
	let drag = null;
	canvas.addEventListener('pointerdown', (e) => { drag = { x: e.clientX, y: e.clientY, spin: state.spin, tilt: state.tilt }; canvas.setPointerCapture(e.pointerId); });
	canvas.addEventListener('pointermove', (e) => {
		if (!drag) return;
		state.spin = drag.spin + (e.clientX - drag.x) * 0.008;
		state.tilt = Math.max(-0.25, Math.min(0.35, drag.tilt + (e.clientY - drag.y) * 0.004));
	});
	const end = () => { drag = null; };
	canvas.addEventListener('pointerup', end);
	canvas.addEventListener('pointercancel', end);

	/* ---------- size ---------- */
	let aspect = 1;
	function resize() {
		const r = canvas.getBoundingClientRect();
		if (!r.width || !r.height) return;
		renderer.setSize(r.width, r.height, false);
		aspect = camera.aspect = r.width / r.height;
		camera.updateProjectionMatrix();
	}
	new ResizeObserver(resize).observe(canvas);
	resize();

	/* ---------- frame ---------- */
	const tmp = new THREE.Vector3();
	let running = false, last = performance.now(), onFrame = null;
	function frame(now) {
		const dt = Math.min(0.05, (now - last) / 1000); last = now;
		if (running) {
			const e = state.explode;
			parts.forEach((p, i) => {
				const k = Math.min(1, Math.max(0, e * 1.3 - i * 0.035));
				p.obj.position.copy(p.home).addScaledVector(p.explode, k * k * (3 - 2 * k));
			});
			if (!drag) state.spin += dt * 0.12; // slow idle turn
			holder.rotation.y = state.spin;
			const dist = fit(aspect, e);
			camera.position.set(target.x, target.y + dist * (0.12 + state.tilt), dist);
			camera.lookAt(target);
			if (flow) {
				flow.inlet.tex.offset.x += dt * (state.open ? 1.4 : 0.15);
				flow.outlet.tex.offset.x += dt * 1.4;
			}
			renderer.render(scene, camera);
			onFrame && onFrame();
		}
		requestAnimationFrame(frame);
	}
	requestAnimationFrame(frame);
	new IntersectionObserver(([en]) => { running = en.isIntersecting; }).observe(canvas);

	return {
		state,
		hasParts: parts.length > 0,
		setOnFrame: (fn) => { onFrame = fn; },
		labels() {
			const r = canvas.getBoundingClientRect();
			return parts.map((p) => {
				tmp.copy(p.anchor); p.obj.localToWorld(tmp); tmp.project(camera);
				return { x: (tmp.x * 0.5 + 0.5) * r.width, y: (-tmp.y * 0.5 + 0.5) * r.height, label: p.label, sub: p.sub };
			});
		},
		setOpen(on) {
			if (!valve || on === state.open) return;
			state.open = on;
			valve.open(on);
			gsap.to(flow.outlet.fill.scale, { x: on ? 1 : 0.0001, duration: on ? 1.2 : 0.6, delay: on ? 0.5 : 0, ease: on ? 'power2.out' : 'power2.in' });
		},
	};
}
