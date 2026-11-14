/**
 * The house: a white architectural cutaway model. Each room stands for a product category.
 * Picking a room colours it, moves the camera in and exposes its "spots" (the faucets and
 * valves in that room) for labelling. Units are metres; faucet/valve models (built in cm-ish
 * units elsewhere) are scaled down when placed.
 *
 * Rooms: kitchen | bath | wc | mech (boiler room) | yard. The living room is scenery.
 */
import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';
import { setupRenderer, makeEnvironment, makeMaterials } from './materials.js';
import { gateValve, ballValve, globeValve, checkValve, pipeX } from './manifold.js';
import { buildFaucet } from './anatomy.js';

const WHITE = 0xf3f2ee;
const T = 0.15;          // wall thickness
const H = 2.6;           // full wall height
const LOW = 1.0;         // cut wall height
const ZB = -4 + T / 2 + 0.012; // inner face of the back wall (with liner)

const DEF = {
	mech:    { x0: -6,   x1: -3.4, z0: -4, z1: 0,  top: 1.7, view: { az: 46, el: 26, x0: -6.1, x1: -4.6, cx: -5.3 }, tint: { floor: 0xd9c58c, wall: 0xf3ead0, furn: 0xc9a55c } },
	bath:    { x0: -3.4, x1: 0.2,  z0: -4, z1: 0,  top: 2.4, tint: { floor: 0x9cc3dd, wall: 0xdcebf5, furn: 0x5e9bc7 } },
	wc:      { x0: 0.2,  x1: 2.0,  z0: -4, z1: -1, top: 2.0, tint: { floor: 0xa7cfc4, wall: 0xe0f0ec, furn: 0x6fae9f } },
	kitchen: { x0: 2.0,  x1: 6,    z0: -4, z1: 0,  top: 2.4, tint: { floor: 0xdcb999, wall: 0xf4e6d6, furn: 0xb07a52 } },
	yard:    { x0: 2.4,  x1: 6,    z0: 0,  z1: 4,  top: 1.3, tint: { floor: 0xc4d6ab, wall: 0xe8f0dc, furn: 0x6e9b5a } },
};
export const ROOM_TYPES = Object.keys(DEF);

/* ---------- small builders ---------- */
const V = (x, y, z) => new THREE.Vector3(x, y, z);
function box(w, h, d, mat, r = 0.012) {
	const rr = Math.min(r, w / 2 - 1e-4, h / 2 - 1e-4, d / 2 - 1e-4);
	const g = rr > 0.002 ? new RoundedBoxGeometry(w, h, d, 2, rr) : new THREE.BoxGeometry(w, h, d);
	return new THREE.Mesh(g, mat);
}
/** Box with its bottom centre at (x, y, z). */
function B(p, w, h, d, x, y, z, mat, r) { const m = box(w, h, d, mat, r); m.position.set(x, y + h / 2, z); p.add(m); return m; }
/** Upright cylinder with its bottom centre at (x, y, z). */
function C(p, r, h, x, y, z, mat, seg = 28) { const m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, h, seg), mat); m.position.set(x, y + h / 2, z); p.add(m); return m; }
const UP = V(0, 1, 0);
function rod(p, a, b, r, mat) {
	const d = b.clone().sub(a), len = d.length();
	const m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, len, 16), mat);
	m.position.copy(a).addScaledVector(d, 0.5);
	m.quaternion.setFromUnitVectors(UP, d.normalize());
	p.add(m); return m;
}
function tube(p, pts, r, mat) {
	const m = new THREE.Mesh(new THREE.TubeGeometry(new THREE.CatmullRomCurve3(pts), 40, r, 10), mat);
	p.add(m); return m;
}
function ball(p, r, x, y, z, mat, sx = 1, sy = 1, sz = 1) { const m = new THREE.Mesh(new THREE.SphereGeometry(r, 28, 20), mat); m.position.set(x, y, z); m.scale.set(sx, sy, sz); p.add(m); return m; }
/** Open-topped vessel (tub, sink): floor plus four walls. Bottom centre at (x, y, z). */
function basin(p, w, h, d, x, y, z, mat, t = 0.035) {
	B(p, w, t, d, x, y, z, mat, 0.01);
	B(p, w, h, t, x, y, z - d / 2 + t / 2, mat, 0.015); B(p, w, h, t, x, y, z + d / 2 - t / 2, mat, 0.015);
	B(p, t, h, d, x - w / 2 + t / 2, y, z, mat, 0.015); B(p, t, h, d, x + w / 2 - t / 2, y, z, mat, 0.015);
}
/** Wall along z whose top slopes from hB at the back down to hF over `sl` metres, then stays flat. */
function slopeWall(p, x, t, z0, z1, hB, hF, sl, mat) {
	const L = z1 - z0, s = new THREE.Shape();
	s.moveTo(0, 0); s.lineTo(L, 0); s.lineTo(L, hF); if (sl < L) s.lineTo(sl, hF); s.lineTo(0, hB); s.closePath();
	const m = new THREE.Mesh(new THREE.ExtrudeGeometry(s, { depth: t, bevelEnabled: false }), mat);
	m.rotation.y = -Math.PI / 2; m.position.set(x + t / 2, 0, z0); p.add(m); return m;
}
function lineTexture(kind) {
	const c = document.createElement('canvas'); c.width = c.height = 256;
	const g = c.getContext('2d');
	g.fillStyle = '#fff'; g.fillRect(0, 0, 256, 256);
	g.strokeStyle = 'rgba(40,40,40,.2)'; g.lineWidth = 3;
	if (kind === 'wood') {
		for (let i = 0; i < 4; i++) { const y = i * 64; g.beginPath(); g.moveTo(0, y); g.lineTo(256, y); g.stroke(); const x = i % 2 ? 64 : 192; g.beginPath(); g.moveTo(x, y); g.lineTo(x, y + 64); g.stroke(); }
	} else if (kind === 'paver') {
		g.strokeRect(0, 0, 256, 128); g.strokeRect(-128, 128, 256, 128); g.strokeRect(128, 128, 256, 128);
	} else { g.strokeRect(0, 0, 256, 256); }
	const t = new THREE.CanvasTexture(c);
	t.wrapS = t.wrapT = THREE.RepeatWrapping; t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 8;
	return t;
}
function floor(p, x0, x1, z0, z1, mat, kind, size) {
	const w = x1 - x0, d = z1 - z0;
	mat.map = lineTexture(kind); mat.map.repeat.set(w / size, d / size);
	const m = new THREE.Mesh(new THREE.PlaneGeometry(w, d), mat);
	m.rotation.x = -Math.PI / 2; m.position.set((x0 + x1) / 2, 0.004, (z0 + z1) / 2); p.add(m); return m;
}

export function createHouse(canvas, { rooms: wanted = [], onPick, onHover } = {}) {
	const renderer = setupRenderer(canvas);
	renderer.toneMappingExposure = 0.92;
	const scene = new THREE.Scene();
	scene.environment = makeEnvironment(renderer);
	scene.environmentIntensity = 0.38;
	const m = makeMaterials();
	const clay = (c = WHITE, rough = 0.9) => new THREE.MeshStandardMaterial({ color: c, roughness: rough });
	const white = clay();
	const steel = new THREE.MeshStandardMaterial({ color: 0xc9ced2, metalness: 1, roughness: 0.32 });
	const sinkDark = new THREE.MeshStandardMaterial({ color: 0x8d9398, metalness: 1, roughness: 0.4 });
	const dark = new THREE.MeshStandardMaterial({ color: 0x2b2e31, roughness: 0.22 });
	const mirror = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 1, roughness: 0.03 });
	const pane = new THREE.MeshStandardMaterial({ color: 0xcfe2ee, roughness: 0.1, metalness: 0.2 });
	const gasLever = new THREE.MeshPhysicalMaterial({ color: 0xf2c230, roughness: 0.45, clearcoat: 0.6 });
	const chrome = m.chrome, cer = m.ceramic;

	scene.add(new THREE.HemisphereLight(0xffffff, 0xb9b2a6, 0.62));
	const key = new THREE.DirectionalLight(0xfff1dd, 3.1);
	key.position.set(-6, 13, 9); key.castShadow = true;
	const big = renderer.capabilities.maxTextureSize >= 4096 && Math.min(innerWidth, innerHeight) > 600;
	key.shadow.mapSize.set(big ? 4096 : 2048, big ? 4096 : 2048);
	Object.assign(key.shadow.camera, { left: -10, right: 10, top: 10, bottom: -10, near: 1, far: 40 });
	key.shadow.bias = -0.0004; key.shadow.normalBias = 0.02; key.shadow.radius = 5;
	scene.add(key);
	const fill = new THREE.DirectionalLight(0xdfe9ff, 0.35); fill.position.set(8, 6, 4); scene.add(fill);

	const house = new THREE.Group(); scene.add(house);
	const ground = new THREE.Mesh(new THREE.PlaneGeometry(80, 80), new THREE.ShadowMaterial({ opacity: 0.26 }));
	ground.rotation.x = -Math.PI / 2; ground.position.y = -0.22; ground.receiveShadow = true; scene.add(ground);

	/* ---------- shell ---------- */
	B(house, 12.5, 0.22, 8.5, 0, -0.22, 0, white, 0.03);                 // slab
	B(house, 12 + T, H, T, 0, 0, -4, white, 0);                           // back wall
	slopeWall(house, -6, T, -4, 4, H, LOW, 4, white);                     // left outer wall
	[-3.4, 0.2, 2.0].forEach((x) => slopeWall(house, x - T / 2, T, -4, 0, H, LOW, 2.2, white));
	// Low wall between the back rooms and the front, with door gaps.
	[[-6, -5.2], [-4.3, -2.2], [-1.3, 0.2], [2.0, 2.6], [3.6, 6]].forEach(([a, b]) => B(house, b - a, LOW, T, (a + b) / 2, 0, 0, white, 0));
	[[0.2, 0.7], [1.6, 2.0]].forEach(([a, b]) => B(house, b - a, LOW, T, (a + b) / 2, 0, -1, white, 0)); // wc front
	[[0, 1.2], [2.8, 4]].forEach(([a, b]) => B(house, T, LOW, b - a, 2.4, 0, (a + b) / 2, white, 0));     // living / yard
	B(house, T, 0.12, 4, 6, 0, -2, white, 0);                             // kitchen side, cut to a plinth
	B(house, 8.4, 0.12, T, -1.8, 0, 4, white, 0);                         // front, cut to a plinth
	B(house, 3.6, 0.5, T, 4.2, 0, 4, white, 0); B(house, T, 0.5, 4, 6, 0, 2, white, 0); // yard fence
	// Windows on the back wall (frame + pane).
	[[-4.9, 1.5, 0.7, 0.6], [-0.2, 1.75, 0.6, 0.5], [1.5, 1.7, 0.5, 0.45]].forEach(([x, y, w, h]) => {
		B(house, w + 0.1, h + 0.1, 0.03, x, y - 0.05, ZB + 0.01, white, 0.004); B(house, w, h, 0.02, x, y, ZB + 0.025, pane, 0);
	});

	/* ---------- rooms ---------- */
	const rooms = {};
	ROOM_TYPES.forEach((type) => {
		const d = DEF[type];
		const g = new THREE.Group(); house.add(g);
		const mats = { floor: clay(), wall: clay(), furn: clay(WHITE, 0.75) };
		mats.floor.userData.base = 0xe6e4de;
		const r = rooms[type] = { type, def: d, group: g, mats, k: 0, goal: 0, spots: {}, key: null };
		r.center = V((d.x0 + d.x1) / 2, 0, (d.z0 + d.z1) / 2);
		r.anchor = type === 'yard' ? V(r.center.x, 1.5, r.center.z) : V(r.center.x, H + 0.15, -4);
		// Wall liners: what gets coloured when the room is picked.
		if (type !== 'yard') {
			B(g, d.x1 - d.x0 - T, H, 0.012, r.center.x, 0, -4 + T / 2 + 0.006, mats.wall, 0);
			const lx = d.x0 + T / 2, rx = d.x1 - T / 2;
			if (type === 'mech') slopeWall(g, lx, 0.012, -4 + T / 2, d.z1, H - 0.03, LOW, 4 - T / 2, mats.wall);
			else slopeWall(g, lx, 0.012, -4 + T / 2, d.z1, H - 0.11, LOW, 2.2 - T / 2, mats.wall);
			if (type !== 'kitchen') slopeWall(g, rx - 0.012, 0.012, -4 + T / 2, d.z1, H - 0.11, LOW, 2.2 - T / 2, mats.wall);
		} else {
			B(g, 2.4, LOW, 0.012, 4.8, 0, T / 2 + 0.006, mats.wall, 0);
		}
	});
	ROOM_TYPES.forEach((t) => rooms[t].mats.floor.color.set(0xe6e4de));
	const K = rooms.kitchen, Ba = rooms.bath, W = rooms.wc, Me = rooms.mech, Y = rooms.yard;

	function faucet(scale, rotY) {
		const f = buildFaucet(m);
		f.parts.slice(-2).forEach((p) => { p.obj.visible = false; }); // no shank or hoses above the counter
		f.group.scale.setScalar(scale); f.group.rotation.y = rotY;
		return f.group;
	}
	function valve(make, scale, mats = m) {
		const v = make(mats).group; v.scale.setScalar(scale);
		v.traverse((o) => { if (o.isGroup && Math.abs(o.position.y - 4.4) < 0.01 && o.children.length === 2) o.rotation.y = 0; }); // ball valve: lever in line
		return v;
	}

	/* Kitchen */
	{
		const g = K.group, f = K.mats.furn;
		floor(g, 2.0 + T / 2, 6, -4 + T / 2, -T / 2, K.mats.floor, 'tile', 0.6);
		B(g, 3.0, 0.86, 0.6, 3.65, 0, ZB + 0.3, f, 0.008);
		for (let i = 0; i < 5; i++) rod(g, V(2.3 + i * 0.6, 0.72, ZB + 0.615), V(2.6 + i * 0.6, 0.72, ZB + 0.615), 0.008, chrome);
		B(g, 3.04, 0.04, 0.64, 3.65, 0.86, ZB + 0.31, white, 0.006);
		B(g, 0.72, 0.012, 0.44, 3.0, 0.9, ZB + 0.33, steel, 0.004); B(g, 0.5, 0.006, 0.34, 2.93, 0.912, ZB + 0.33, sinkDark, 0.002);
		const kf = faucet(0.0125, Math.PI / 2); kf.position.set(3.0, 0.9, ZB + 0.08); g.add(kf);
		K.spots.sink = V(3.0, 1.22, ZB + 0.12);
		// Drinking-water tap: a slim gooseneck.
		C(g, 0.012, 0.03, 3.42, 0.9, ZB + 0.09, chrome);
		tube(g, [V(3.42, 0.92, ZB + 0.09), V(3.42, 1.14, ZB + 0.09), V(3.42, 1.2, ZB + 0.15), V(3.42, 1.13, ZB + 0.21)], 0.009, chrome);
		K.spots.filter = V(3.42, 1.2, ZB + 0.15);
		// Hob, hood, gas valve with its hose.
		B(g, 0.6, 0.012, 0.5, 4.5, 0.9, ZB + 0.33, dark, 0.004);
		[[-0.15, -0.11], [0.15, -0.11], [-0.15, 0.12], [0.15, 0.12]].forEach(([dx, dz]) => { const t = new THREE.Mesh(new THREE.TorusGeometry(0.075, 0.008, 8, 28), steel); t.rotation.x = Math.PI / 2; t.position.set(4.5 + dx, 0.918, ZB + 0.33 + dz); g.add(t); });
		B(g, 0.6, 0.1, 0.46, 4.5, 1.62, ZB + 0.23, white, 0.01); B(g, 0.24, 0.86, 0.22, 4.5, 1.72, ZB + 0.11, white, 0.01);
		rod(g, V(5.12, 1.12, ZB + 0.05), V(5.0, 1.12, ZB + 0.05), 0.016, steel);
		const gv = valve(ballValve, 0.016, { ...m, vinylLever: gasLever }); gv.position.set(4.93, 1.12, ZB + 0.05); g.add(gv);
		tube(g, [V(4.86, 1.12, ZB + 0.05), V(4.78, 1.1, ZB + 0.06), V(4.76, 0.98, ZB + 0.07), V(4.74, 0.91, ZB + 0.1)], 0.011, m.braid);
		K.spots.gas = V(4.93, 1.2, ZB + 0.06);
		B(g, 1.9, 0.7, 0.35, 3.1, 1.52, ZB + 0.175, f, 0.008); B(g, 0.7, 0.7, 0.35, 5.5, 1.92, ZB + 0.175, f, 0.008).visible = false;
		B(g, 0.7, 1.18, 0.68, 5.5, 0, ZB + 0.35, white, 0.02); B(g, 0.7, 0.66, 0.68, 5.5, 1.2, ZB + 0.35, white, 0.02);
		rod(g, V(5.22, 0.7, ZB + 0.71), V(5.22, 1.1, ZB + 0.71), 0.01, chrome); rod(g, V(5.22, 1.3, ZB + 0.71), V(5.22, 1.6, ZB + 0.71), 0.01, chrome);
		// Island with stools.
		B(g, 1.7, 0.86, 0.75, 3.9, 0, -1.55, f, 0.008); B(g, 1.8, 0.04, 0.85, 3.9, 0.86, -1.55, white, 0.006);
		[3.4, 4.0, 4.6].forEach((x) => { C(g, 0.16, 0.04, x, 0.62, -0.92, white); C(g, 0.02, 0.62, x, 0, -0.92, chrome); C(g, 0.14, 0.012, x, 0, -0.92, chrome); });
		[[3.6, 0.07, 0.1], [4.25, 0.05, 0.16]].forEach(([x, r, h]) => C(g, r, h, x, 0.9, -1.6, white));
	}

	/* Bathroom */
	{
		const g = Ba.group, f = Ba.mats.furn, xl = -3.4 + T / 2 + 0.012;
		floor(g, -3.4 + T / 2, 0.2 - T / 2, -4 + T / 2, -T / 2, Ba.mats.floor, 'tile', 0.3);
		basin(g, 1.7, 0.56, 0.78, xl + 0.86, 0, ZB + 0.4, cer, 0.06);
		B(g, 1.58, 0.02, 0.66, xl + 0.86, 0.2, ZB + 0.4, cer, 0.004);
		// Shower: wall mixer, riser, head, hand shower.
		const sx = xl + 0.7, sy = 1.05;
		rod(g, V(sx - 0.11, sy, ZB + 0.05), V(sx + 0.11, sy, ZB + 0.05), 0.022, chrome);
		[-0.13, 0.13].forEach((dx) => rod(g, V(sx + dx - 0.02, sy, ZB + 0.05), V(sx + dx + 0.02, sy, ZB + 0.05), 0.03, chrome));
		rod(g, V(sx, sy, ZB), V(sx, sy, ZB + 0.05), 0.014, chrome);
		rod(g, V(sx, sy - 0.01, ZB + 0.05), V(sx, sy - 0.07, ZB + 0.15), 0.014, chrome);
		rod(g, V(sx, sy, ZB + 0.05), V(sx, 2.1, ZB + 0.05), 0.011, chrome);
		tube(g, [V(sx, 2.1, ZB + 0.05), V(sx, 2.16, ZB + 0.1), V(sx, 2.15, ZB + 0.34)], 0.011, chrome);
		C(g, 0.11, 0.014, sx, 2.12, ZB + 0.34, chrome);
		rod(g, V(sx + 0.3, 1.2, ZB + 0.03), V(sx + 0.3, 1.36, ZB + 0.06), 0.016, chrome);
		tube(g, [V(sx + 0.13, sy - 0.02, ZB + 0.05), V(sx + 0.2, 0.8, ZB + 0.08), V(sx + 0.34, 0.86, ZB + 0.07), V(sx + 0.3, 1.2, ZB + 0.04)], 0.007, m.braid);
		Ba.spots.shower = V(sx, sy + 0.06, ZB + 0.08);
		B(g, 0.012, 1.45, 0.72, xl + 1.74, 0.56, ZB + 0.38, m.glass, 0);
		// Vanity with vessel basin, tall faucet and mirror.
		const vx = -1.15;
		B(g, 0.85, 0.5, 0.48, vx, 0.34, ZB + 0.24, f, 0.008); B(g, 0.89, 0.03, 0.5, vx, 0.84, ZB + 0.25, white, 0.006);
		basin(g, 0.5, 0.13, 0.34, vx, 0.87, ZB + 0.29, cer, 0.022);
		const bf = faucet(0.0115, Math.PI / 2); bf.position.set(vx, 0.87, ZB + 0.06); g.add(bf);
		Ba.spots.basin = V(vx, 1.17, ZB + 0.1);
		B(g, 0.74, 0.94, 0.02, vx, 1.28, ZB + 0.01, white, 0.004); B(g, 0.7, 0.9, 0.012, vx, 1.3, ZB + 0.02, mirror, 0);
		// Towel radiator with its valve, on the left wall.
		const rz0 = -2.55, rz1 = -2.05, rx = xl + 0.05;
		[rz0, rz1].forEach((z) => rod(g, V(rx, 0.42, z), V(rx, 1.2, z), 0.014, chrome));
		for (let i = 0; i < 7; i++) rod(g, V(rx, 0.5 + i * 0.11, rz0), V(rx, 0.5 + i * 0.11, rz1), 0.01, chrome);
		B(g, 0.03, 0.4, 0.36, rx + 0.02, 0.72, (rz0 + rz1) / 2, f, 0.012);
		rod(g, V(rx, 0.42, rz1), V(rx, 0.3, rz1), 0.012, chrome);
		C(g, 0.026, 0.06, rx, 0.25, rz1, chrome); rod(g, V(rx, 0.28, rz1), V(rx + 0.06, 0.28, rz1), 0.022, cer); rod(g, V(xl - 0.01, 0.26, rz1), V(rx, 0.26, rz1), 0.01, chrome);
		Ba.spots.radiator = V(rx + 0.04, 0.3, rz1);
		// Washing machine, mat, laundry basket.
		B(g, 0.6, 0.85, 0.58, -0.3, 0, -0.5, white, 0.02);
		const door = new THREE.Mesh(new THREE.TorusGeometry(0.17, 0.025, 10, 32), steel); door.position.set(-0.3, 0.48, -0.2); g.add(door);
		const glassD = new THREE.Mesh(new THREE.CircleGeometry(0.16, 32), dark); glassD.position.set(-0.3, 0.48, -0.205); g.add(glassD);
		B(g, 0.8, 0.012, 0.5, xl + 0.9, 0.004, ZB + 1.15, f, 0.004);
		C(g, 0.18, 0.45, -1.2, 0, -0.55, f);
	}

	/* WC */
	{
		const g = W.group, f = W.mats.furn, xl = 0.2 + T / 2 + 0.012;
		floor(g, 0.2 + T / 2, 2.0 - T / 2, -4 + T / 2, -1 - T / 2, W.mats.floor, 'tile', 0.3);
		const tx = 1.15;
		B(g, 0.4, 0.36, 0.17, tx, 0.42, ZB + 0.09, cer, 0.03); B(g, 0.42, 0.03, 0.19, tx, 0.78, ZB + 0.09, cer, 0.012);
		C(g, 0.022, 0.012, tx, 0.81, ZB + 0.09, chrome);
		B(g, 0.24, 0.3, 0.42, tx, 0, ZB + 0.32, cer, 0.05);
		ball(g, 0.2, tx, 0.31, ZB + 0.42, cer, 0.95, 0.62, 1.3);
		ball(g, 0.2, tx, 0.415, ZB + 0.42, cer, 0.98, 0.09, 1.32);
		// Angle valve feeding the cistern.
		const ax = tx - 0.36, ay = 0.22;
		rod(g, V(ax, ay, ZB), V(ax, ay, ZB + 0.05), 0.012, chrome); C(g, 0.03, 0.006, ax, ay - 0.003, ZB + 0.005, chrome).rotation.x = Math.PI / 2;
		rod(g, V(ax, ay, ZB + 0.05), V(ax, ay, ZB + 0.085), 0.02, chrome);
		tube(g, [V(ax, ay + 0.01, ZB + 0.05), V(ax + 0.02, ay + 0.12, ZB + 0.06), V(ax + 0.12, 0.36, ZB + 0.07), V(tx - 0.17, 0.44, ZB + 0.08)], 0.007, m.braid);
		W.spots.angle = V(ax, ay + 0.03, ZB + 0.07);
		// Hand sprayer on its own valve.
		const hx = tx - 0.5, hy = 0.8;
		rod(g, V(hx, hy, ZB), V(hx, hy, ZB + 0.045), 0.012, chrome); rod(g, V(hx - 0.035, hy, ZB + 0.045), V(hx + 0.035, hy, ZB + 0.045), 0.018, chrome);
		rod(g, V(hx - 0.035, hy, ZB + 0.045), V(hx - 0.06, hy, ZB + 0.045), 0.024, chrome);
		rod(g, V(hx + 0.12, hy + 0.02, ZB), V(hx + 0.12, hy + 0.02, ZB + 0.04), 0.008, chrome);
		rod(g, V(hx + 0.12, hy - 0.06, ZB + 0.04), V(hx + 0.12, hy + 0.1, ZB + 0.045), 0.013, chrome); rod(g, V(hx + 0.12, hy + 0.1, ZB + 0.045), V(hx + 0.12, hy + 0.13, ZB + 0.075), 0.017, chrome);
		tube(g, [V(hx, hy - 0.02, ZB + 0.045), V(hx + 0.01, hy - 0.4, ZB + 0.06), V(hx + 0.1, hy - 0.42, ZB + 0.06), V(hx + 0.12, hy - 0.06, ZB + 0.04)], 0.006, m.braid);
		W.spots.sprayer = V(hx, hy + 0.03, ZB + 0.06);
		// Small hand basin on the left wall.
		const bz = -2.25;
		basin(g, 0.3, 0.11, 0.4, xl + 0.16, 0.76, bz, cer, 0.022);
		C(g, 0.02, 0.5, xl + 0.06, 0.28, bz, chrome);
		const wf = faucet(0.0075, Math.PI); wf.position.set(xl + 0.035, 0.87, bz); g.add(wf);
		W.spots.basin = V(xl + 0.06, 1.08, bz);
		C(g, 0.11, 0.26, 1.72, 0, -1.3, f); rod(g, V(xl, 0.62, -3.1), V(xl + 0.07, 0.62, -3.1), 0.008, chrome); rod(g, V(xl + 0.07, 0.62, -3.16), V(xl + 0.07, 0.62, -3.04), 0.05, white);
	}

	/* Boiler room */
	{
		const g = Me.group, f = Me.mats.furn, xl = -6 + T / 2 + 0.012;
		floor(g, -6 + T / 2, -3.4 - T / 2, -4 + T / 2, -T / 2, Me.mats.floor, 'tile', 0.6);
		// Hot-water cylinder and wall boiler.
		const tkx = -5.42, tkz = ZB + 0.42;
		C(g, 0.29, 1.3, tkx, 0.14, tkz, white, 40); ball(g, 0.29, tkx, 1.44, tkz, white, 1, 0.45, 1); ball(g, 0.29, tkx, 0.14, tkz, white, 1, 0.3, 1);
		[0, 2.1, 4.2].forEach((a) => C(g, 0.02, 0.14, tkx + Math.cos(a) * 0.2, 0, tkz + Math.sin(a) * 0.2, steel));
		[0.5, 1.1].forEach((y) => { const t = new THREE.Mesh(new THREE.TorusGeometry(0.292, 0.008, 6, 40), steel); t.rotation.x = Math.PI / 2; t.position.set(tkx, y, tkz); g.add(t); });
		const bx = -4.35;
		B(g, 0.46, 0.74, 0.3, bx, 1.2, ZB + 0.15, white, 0.03); B(g, 0.2, 0.07, 0.01, bx, 1.3, ZB + 0.305, dark, 0.003);
		C(g, 0.055, 0.5, bx, 1.94, ZB + 0.15, steel);
		// Pipework along the left wall, carrying the four valve types.
		const s = 0.03, px = xl + 0.12, y1 = 0.7, y2 = 0.3, zc = -1.85;
		const net = new THREE.Group(); net.scale.setScalar(s); net.rotation.y = Math.PI / 2; net.position.set(px, y1, zc); g.add(net);
		[[-56, -44.5], [-35.5, -17.5], [-8.5, 8.5], [17.5, 35.5], [44.5, 56]].forEach(([a, b]) => net.add(pipeX(a, b, 0, m.copper)));
		net.add(pipeX(-56, 56, (y2 - y1) / s, m.copper));
		const place = (v, u) => { v.position.set(u, 0, 0); net.add(v); return V(px, y1 + 0.17, zc - u * s); };
		Me.spots.gate = place(gateValve(m, m.redWheel).group, -40);
		Me.spots.check = place(checkValve(m).group, -13); Me.spots.check.y -= 0.06;
		Me.spots.ball = place(valve(ballValve, 1), 13); Me.spots.ball.y -= 0.02;
		Me.spots.globe = place(globeValve(m).group, 40);
		const zF = zc + 56 * s, zBk = zc - 56 * s;
		[y1, y2].forEach((y) => { rod(g, V(px, y, zF), V(px, 0, zF + 0.0001), 0.035, m.copper); [-0.6, 0.5].forEach((dz) => rod(g, V(xl, y, zc + dz), V(px, y, zc + dz), 0.012, steel)); });
		// Pump on the lower line.
		rod(g, V(px, y2, zc + 0.16), V(px, y2, zc - 0.16), 0.085, m.iron); rod(g, V(px, y2, zc), V(px + 0.18, y2, zc), 0.08, m.iron);
		// Back to the cylinder and the boiler.
		rod(g, V(px, y1, zBk), V(px, y1, tkz), 0.035, m.copper); rod(g, V(px, y1, tkz), V(tkx - 0.26, y1, tkz), 0.035, m.copper);
		const zw = ZB + 0.07;
		rod(g, V(px, y2, zBk), V(px, y2, zw), 0.035, m.copper); ball(g, 0.04, px, y2, zw, m.brass);
		rod(g, V(px, y2, zw), V(bx - 0.1, y2, zw), 0.035, m.copper); ball(g, 0.04, bx - 0.1, y2, zw, m.brass); rod(g, V(bx - 0.1, y2, zw), V(bx - 0.1, 1.2, zw), 0.035, m.copper);
		rod(g, V(bx + 0.1, 1.2, zw), V(bx + 0.1, 0, zw), 0.03, m.copper);
		// Pressure gauge, floor drain, shelf with boxes.
		C(g, 0.05, 0.02, px, y1 + 0.08, zc + 1.0, steel).rotation.z = Math.PI / 2; rod(g, V(px, y1, zc + 1.0), V(px, y1 + 0.06, zc + 1.0), 0.008, m.brass);
		C(g, 0.07, 0.006, -4.6, 0.004, -1.6, steel);
		B(g, 0.9, 0.04, 0.4, -4.0, 0.5, -0.35, f, 0.006); B(g, 0.04, 0.5, 0.4, -4.43, 0, -0.35, f, 0.006); B(g, 0.04, 0.5, 0.4, -3.57, 0, -0.35, f, 0.006);
		B(g, 0.3, 0.22, 0.26, -4.15, 0.54, -0.35, white, 0.01); B(g, 0.24, 0.3, 0.24, -3.8, 0.54, -0.35, white, 0.01);
	}

	/* Yard */
	{
		const g = Y.group, f = Y.mats.furn, zw = T / 2 + 0.012;
		floor(g, 2.4 + T / 2, 6 - T / 2, T / 2, 4 - T / 2, Y.mats.floor, 'paver', 0.8);
		// Garden tap with hose and bucket.
		const tx = 4.0, ty = 0.62;
		rod(g, V(tx, ty, zw), V(tx, ty, zw + 0.09), 0.017, m.brass); C(g, 0.03, 0.008, tx, ty - 0.004, zw + 0.004, m.brass).rotation.x = Math.PI / 2;
		rod(g, V(tx, ty, zw + 0.085), V(tx, ty - 0.07, zw + 0.13), 0.014, m.brass);
		rod(g, V(tx, ty, zw + 0.06), V(tx, ty + 0.05, zw + 0.06), 0.008, m.brass); rod(g, V(tx - 0.04, ty + 0.05, zw + 0.06), V(tx + 0.04, ty + 0.05, zw + 0.06), 0.008, m.redWheel);
		Y.spots.tap = V(tx, ty + 0.07, zw + 0.07);
		for (let i = 0; i < 5; i++) { const t = new THREE.Mesh(new THREE.TorusGeometry(0.17 - (i % 2) * 0.012, 0.016, 8, 36), f); t.position.set(3.2, 0.5, zw + 0.03 + i * 0.03); g.add(t); }
		rod(g, V(3.2, 0.66, zw), V(3.2, 0.66, zw + 0.2), 0.012, steel);
		tube(g, [V(tx, ty - 0.08, zw + 0.13), V(tx - 0.05, 0.2, zw + 0.2), V(3.6, 0.03, zw + 0.3), V(3.3, 0.04, zw + 0.22), V(3.22, 0.34, zw + 0.1)], 0.015, f);
		const bucket = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.11, 0.26, 28, 1, true), steel); bucket.material.side = THREE.DoubleSide; bucket.position.set(4.35, 0.13, 0.42); g.add(bucket);
		C(g, 0.11, 0.01, 4.35, 0, 0.42, steel);
		// Gas riser: main valve, regulator, meter.
		const gx = 5.1, gz = zw + 0.1;
		rod(g, V(gx, 0, gz), V(gx, 0.86, gz), 0.02, steel);
		const mv = valve(ballValve, 0.017, { ...m, vinylLever: gasLever }); mv.rotation.z = Math.PI / 2; mv.rotation.y = -Math.PI / 2; mv.position.set(gx, 0.4, gz); g.add(mv);
		Y.spots.gas = V(gx, 0.48, gz + 0.08);
		const reg = C(g, 0.075, 0.05, gx, 0.86, gz, steel); reg.rotation.x = Math.PI / 2; reg.position.y = 0.88;
		rod(g, V(gx, 0.88, gz), V(5.38, 0.88, gz), 0.02, steel);
		B(g, 0.26, 0.32, 0.17, 5.5, 0.6, gz, white, 0.03); B(g, 0.14, 0.07, 0.01, 5.5, 0.78, gz + 0.088, dark, 0.003);
		rod(g, V(5.62, 0.88, gz), V(5.62, 0.88, zw - 0.02), 0.02, steel); rod(g, V(5.5, 0.9, gz), V(5.62, 0.88, gz), 0.02, steel);
		// Planter with shrubs, table and chairs.
		B(g, 0.45, 0.4, 3.0, 5.6, 0, 2.3, white, 0.02);
		[[1.1, 0.2], [1.6, 0.25], [2.15, 0.21], [2.7, 0.26], [3.3, 0.22]].forEach(([z, r], i) => ball(g, r, 5.6 + (i % 2 ? 0.03 : -0.03), 0.4 + r * 0.75, z, f, 1, 0.9, 1));
		C(g, 0.45, 0.03, 3.7, 0.7, 2.6, white, 40); C(g, 0.03, 0.7, 3.7, 0, 2.6, steel); C(g, 0.2, 0.015, 3.7, 0, 2.6, steel);
		[[3.0, 2.3, 0.5], [4.35, 3.0, -2.4]].forEach(([x, z, a]) => { const c = new THREE.Group(); B(c, 0.42, 0.04, 0.42, 0, 0.42, 0, f, 0.012); B(c, 0.42, 0.42, 0.04, 0, 0.46, -0.2, f, 0.012); [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([i, j]) => C(c, 0.014, 0.42, i * 0.18, 0, j * 0.18, steel)); c.position.set(x, 0, z); c.rotation.y = a; g.add(c); });
		C(g, 0.2, 0.3, 2.95, 0, 3.5, white); ball(g, 0.3, 2.95, 0.55, 3.5, f, 1, 1.1, 1);
	}

	/* Living room (scenery, stays white) */
	{
		const g = new THREE.Group(); house.add(g);
		const fm = clay(0xe6e4de);
		floor(g, -6 + T / 2, 2.4 - T / 2, T / 2, 4 - T / 2, fm, 'wood', 1.2);
		const lx = -6 + T / 2;
		B(g, 0.9, 0.38, 2.2, lx + 0.5, 0.06, 2.2, white, 0.06); B(g, 0.22, 0.75, 2.2, lx + 0.13, 0.06, 2.2, white, 0.07);
		[1.2, 3.2].forEach((z) => B(g, 0.9, 0.56, 0.2, lx + 0.5, 0.06, z, white, 0.07));
		[1.75, 2.65].forEach((z) => B(g, 0.6, 0.14, 0.82, lx + 0.58, 0.42, z, white, 0.06));
		B(g, 2.4, 0.012, 1.7, -4.0, 0.004, 2.2, clay(0xe9e7e1), 0.004);
		B(g, 1.0, 0.04, 0.6, -4.1, 0.34, 2.2, white, 0.012); [[-0.42, -0.24], [0.42, -0.24], [-0.42, 0.24], [0.42, 0.24]].forEach(([i, j]) => C(g, 0.015, 0.34, -4.1 + i, 0, 2.2 + j, steel));
		C(g, 0.016, 1.3, lx + 0.4, 0, 3.65, steel); C(g, 0.15, 0.012, lx + 0.4, 0, 3.65, steel); const sh = new THREE.Mesh(new THREE.CylinderGeometry(0.13, 0.2, 0.26, 28), white); sh.position.set(lx + 0.4, 1.36, 3.65); g.add(sh);
		// Dining table with four chairs.
		B(g, 1.7, 0.05, 0.9, 0.4, 0.72, 2.3, white, 0.015); [[-0.75, -0.36], [0.75, -0.36], [-0.75, 0.36], [0.75, 0.36]].forEach(([i, j]) => B(g, 0.05, 0.72, 0.05, 0.4 + i, 0, 2.3 + j, white, 0.01));
		[[0.0, 1.6, 0], [0.8, 1.6, 0], [0.0, 3.0, Math.PI], [0.8, 3.0, Math.PI]].forEach(([x, z, a]) => { const c = new THREE.Group(); B(c, 0.42, 0.04, 0.42, 0, 0.44, 0, white, 0.012); B(c, 0.42, 0.46, 0.04, 0, 0.48, -0.2, white, 0.012); [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([i, j]) => C(c, 0.016, 0.44, i * 0.18, 0, j * 0.18, white)); c.position.set(x, 0, z); c.rotation.y = a; g.add(c); });
		C(g, 0.09, 0.2, 0.4, 0.77, 2.3, white);
		// Sideboard, plant, radiator under the low wall.
		B(g, 1.6, 0.55, 0.4, -2.6, 0, 0.32, white, 0.012);
		C(g, 0.1, 0.16, -3.1, 0.55, 0.32, white); ball(g, 0.16, -3.1, 0.82, 0.32, white, 1, 1.1, 1); B(g, 0.3, 0.2, 0.03, -2.3, 0.55, 0.3, white, 0.005);
		for (let i = 0; i < 9; i++) B(g, 0.06, 0.5, 0.09, 0.9 + i * 0.075, 0.12, 0.16, white, 0.02);
	}

	house.traverse((o) => { if (o.isMesh) { o.castShadow = o.material !== m.glass; o.receiveShadow = true; } });

	/* ---------- which rooms are live ---------- */
	const live = [];
	wanted.forEach((w) => { const r = rooms[w.room]; if (r && !r.key) { r.key = String(w.key); live.push(r); } });
	const byKey = Object.fromEntries(live.map((r) => [r.key, r]));
	const hits = live.map((r) => {
		const d = r.def, h = r.type === 'yard' ? 1.0 : 1.9;
		const hit = new THREE.Mesh(new THREE.BoxGeometry(d.x1 - d.x0, h, d.z1 - d.z0), new THREE.MeshBasicMaterial({ visible: false }));
		hit.position.set(r.center.x, h / 2, r.center.z); hit.userData.key = r.key; scene.add(hit); return hit;
	});
	const corners = (x0, x1, y1, z0, z1) => [x0, x1].flatMap((x) => [0, y1].flatMap((y) => [z0, z1].map((z) => V(x, y, z))));
	live.forEach((r) => { const d = r.def, v = d.view || {}; r.box = corners(v.x0 ?? d.x0 - 0.1, v.x1 ?? d.x1 + 0.1, d.top, d.z0, d.z1 + 0.15); r.tc = V(v.cx ?? r.center.x, d.top * 0.42, r.center.z); });
	const ALL = corners(-6.3, 6.3, H, -4.2, 4.3);

	/* ---------- camera ---------- */
	const camera = new THREE.PerspectiveCamera(26, 1, 0.2, 200);
	const rad = THREE.MathUtils.degToRad;
	const cur = { t: V(0, 0.8, 0), az: rad(48), el: rad(62), dist: 60, ox: 0, oy: 0 };
	const goal = { t: V(0, 0.8, 0), az: 0, el: 0, dist: 30, ox: 0, oy: 0 };
	let selected = null, Wd = 1, Hd = 1, mouseX = 0, until = 0, hoverKey = null, settled = false;
	const dirOf = (az, el) => V(Math.sin(az) * Math.cos(el), Math.sin(el), Math.cos(az) * Math.cos(el));
	function fitDist(t, az, el, pts, margin) {
		const d = dirOf(az, el), right = V(0, 1, 0).cross(d).normalize(), up = d.clone().cross(right);
		const th = Math.tan(rad(camera.fov) / 2), tw = th * camera.aspect;
		let D = 0; const v = V(0, 0, 0);
		pts.forEach((p) => { v.copy(p).sub(t); const f = v.dot(d); D = Math.max(D, Math.abs(v.dot(right)) * margin / tw + f, Math.abs(v.dot(up)) * margin / th + f); });
		return D;
	}
	function aim() {
		const wide = camera.aspect > 1.15;
		if (selected) {
			const r = byKey[selected];
			const v = r.def.view || {};
			goal.t.copy(r.tc); goal.az = rad(v.az ?? (wide ? 16 : 10)); goal.el = rad(v.el ?? 30);
			goal.dist = fitDist(goal.t, goal.az, goal.el, r.box, wide ? 1.42 : 1.12);
			goal.ox = wide ? 0.08 : 0; goal.oy = wide ? -0.05 : 0.1;
		} else {
			goal.t.set(0, 0.9, 0); goal.az = rad(22); goal.el = rad(wide ? 37 : 50);
			goal.dist = fitDist(goal.t, goal.az, goal.el, ALL, wide ? 1.34 : 1.02);
			goal.ox = wide ? 0.12 : 0; goal.oy = wide ? 0.035 : 0.04;
		}
		wake(2.5);
	}
	function wake(sec = 1.2) { settled = false; until = Math.max(until, performance.now() + sec * 1000); }
	function resize() {
		const r = canvas.getBoundingClientRect();
		if (!r.width || !r.height) return;
		Wd = r.width; Hd = r.height;
		renderer.setSize(Wd, Hd, false);
		camera.aspect = Wd / Hd;
		aim();
	}
	new ResizeObserver(resize).observe(canvas);
	resize();

	/* ---------- picking ---------- */
	const ray = new THREE.Raycaster(), ptr = new THREE.Vector2();
	function keyAt(e) {
		const r = canvas.getBoundingClientRect();
		ptr.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
		ray.setFromCamera(ptr, camera);
		const hit = ray.intersectObjects(hits)[0];
		return hit ? hit.object.userData.key : null;
	}
	function setHover(k) {
		if (k === hoverKey) return;
		hoverKey = k; canvas.style.cursor = k && k !== selected ? 'pointer' : '';
		tints(); onHover && onHover(k);
	}
	function tints() { live.forEach((r) => { r.goal = r.key === selected ? 1 : r.key === hoverKey && !selected ? 0.3 : r.key === hoverKey ? 0.18 : 0; }); wake(); }
	let down = null;
	canvas.addEventListener('pointermove', (e) => { const r = canvas.getBoundingClientRect(); mouseX = ((e.clientX - r.left) / r.width) * 2 - 1; setHover(keyAt(e)); wake(); });
	canvas.addEventListener('pointerleave', () => { mouseX = 0; setHover(null); });
	canvas.addEventListener('pointerdown', (e) => { down = { x: e.clientX, y: e.clientY }; });
	canvas.addEventListener('pointerup', (e) => {
		if (!down || Math.hypot(e.clientX - down.x, e.clientY - down.y) > 8) return;
		down = null;
		onPick && onPick(keyAt(e));
	});

	/* ---------- frame ---------- */
	const white3 = new THREE.Color(WHITE), tmpC = new THREE.Color(), tmp = V(0, 0, 0);
	let last = performance.now(), visible = true, onFrame = null, shadowFrames = 3;
	renderer.shadowMap.autoUpdate = false;
	function frame(now) {
		requestAnimationFrame(frame);
		const dt = Math.min(0.12, (now - last) / 1000); last = now;
		if (!visible || (now > until && settled)) return;
		const k = 1 - Math.pow(0.0025, dt);
		cur.t.lerp(goal.t, k);
		cur.az += (goal.az + mouseX * 0.035 - cur.az) * k; cur.el += (goal.el - cur.el) * k;
		cur.dist += (goal.dist - cur.dist) * k; cur.ox += (goal.ox - cur.ox) * k; cur.oy += (goal.oy - cur.oy) * k;
		camera.position.copy(cur.t).addScaledVector(dirOf(cur.az, cur.el), cur.dist);
		camera.lookAt(cur.t);
		camera.setViewOffset(Wd, Hd, cur.ox * Wd, cur.oy * Hd, Wd, Hd);
		live.forEach((r) => {
			if (Math.abs(r.goal - r.k) < 0.001) return;
			r.k += (r.goal - r.k) * (1 - Math.pow(0.002, dt));
			['floor', 'wall', 'furn'].forEach((n) => r.mats[n].color.set(r.mats[n].userData.base || WHITE).lerp(tmpC.set(r.def.tint[n]), r.k));
		});
		settled = Math.abs(goal.dist - cur.dist) < 0.01 && Math.abs(goal.el - cur.el) < 0.002 && Math.abs(goal.az + mouseX * 0.035 - cur.az) < 0.002 && cur.t.distanceTo(goal.t) < 0.01 && live.every((r) => Math.abs(r.goal - r.k) < 0.004);
		if (shadowFrames > 0) { renderer.shadowMap.needsUpdate = true; shadowFrames--; }
		renderer.render(scene, camera);
		onFrame && onFrame();
	}
	requestAnimationFrame(frame);
	new IntersectionObserver(([en]) => { visible = en.isIntersecting; if (visible) wake(); }).observe(canvas);

	return {
		rooms: live,
		setOnFrame: (fn) => { onFrame = fn; },
		/** Screen position of a point in the house. */
		project(v) { tmp.copy(v).project(camera); return { x: (tmp.x * 0.5 + 0.5) * Wd, y: (-tmp.y * 0.5 + 0.5) * Hd }; },
		select(key) { selected = key && byKey[key] ? String(key) : null; tints(); aim(); },
		get selected() { return selected; },
		/** Opening move: start high and far, settle into the overview. */
		intro() { cur.az = rad(52); cur.el = rad(64); cur.dist = goal.dist * 1.7; cur.t.copy(goal.t); cur.ox = goal.ox; cur.oy = goal.oy; wake(4); },
		still() { Object.assign(cur, { az: goal.az, el: goal.el, dist: goal.dist, ox: goal.ox, oy: goal.oy }); cur.t.copy(goal.t); wake(1); },
	};
}
