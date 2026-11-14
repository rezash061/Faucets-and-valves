import * as THREE from 'three';
import { setupRenderer, makeEnvironment, makeMaterials } from './materials.js';
import { gateValve, ballValve, globeValve, checkValve } from './manifold.js';
import { buildFaucet } from './anatomy.js';

/**
 * Stand-in product pictures rendered from the 3D models, used only for products that have
 * no photo yet. Rendered on demand and cached as data URLs.
 * Keys: faucet-chrome | faucet-black | faucet-gold | faucet-nickel | ball | gate | globe | check.
 */
export function createThumbs() {
	let ctx = null;
	const cache = {};
	function setup() {
		const canvas = document.createElement('canvas');
		const renderer = setupRenderer(canvas);
		renderer.setPixelRatio(1);
		renderer.setSize(360, 450, false);
		renderer.setClearColor(0x000000, 0);
		const scene = new THREE.Scene();
		scene.environment = makeEnvironment(renderer);
		scene.add(new THREE.HemisphereLight(0xffffff, 0x999999, 0.6));
		const key = new THREE.DirectionalLight(0xffffff, 2); key.position.set(20, 40, 30); scene.add(key);
		const camera = new THREE.PerspectiveCamera(24, 360 / 450, 1, 500);
		const m = makeMaterials();
		const finishes = {
			chrome: m.chrome,
			black: new THREE.MeshPhysicalMaterial({ color: 0x1c1c1e, metalness: 0.5, roughness: 0.45, clearcoat: 0.3 }),
			gold: new THREE.MeshPhysicalMaterial({ color: 0xe6be6a, metalness: 1, roughness: 0.14 }),
			nickel: m.satinNickel,
		};
		ctx = { canvas, renderer, scene, camera, m, finishes };
	}
	function shot(obj, dist, ty, rotY = -0.5) {
		const { canvas, renderer, scene, camera } = ctx;
		const holder = new THREE.Group(); holder.add(obj); holder.rotation.y = rotY; scene.add(holder);
		camera.position.set(Math.sin(0.2) * dist, ty + dist * 0.12, Math.cos(0.2) * dist);
		camera.lookAt(0, ty, 0);
		renderer.render(scene, camera);
		const url = canvas.toDataURL('image/png');
		scene.remove(holder);
		return url;
	}
	function make(k) {
		if (!ctx) setup();
		const { m, finishes } = ctx;
		if (k.startsWith('faucet-')) {
			const { group, parts } = buildFaucet({ ...m, chrome: finishes[k.slice(7)] || m.chrome });
			parts.slice(-2).forEach((p) => { p.obj.visible = false; }); // hide shank and hoses
			return shot(group, 78, 12);
		}
		if (k === 'gate') return shot(gateValve(m, m.redWheel).group, 46, 4, -0.35);
		if (k === 'globe') return shot(globeValve(m).group, 44, 3.5, -0.35);
		if (k === 'check') return shot(checkValve(m).group, 36, 0.5, -0.35);
		return shot(ballValve(m).group, 40, 2, -0.35);
	}
	return { get: (k) => (cache[k] ||= make(k)) };
}
