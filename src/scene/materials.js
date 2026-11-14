import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

/** Studio reflections for metals (no HDR file needed). */
export function makeEnvironment(renderer) {
	const pmrem = new THREE.PMREMGenerator(renderer);
	const env = pmrem.fromScene(new RoomEnvironment(), 0.03).texture;
	pmrem.dispose();
	return env;
}

export function setupRenderer(canvas) {
	const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
	renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = THREE.ACESFilmicToneMapping;
	renderer.toneMappingExposure = 1.05;
	renderer.shadowMap.enabled = true;
	renderer.shadowMap.type = THREE.PCFSoftShadowMap;
	return renderer;
}

/** Fine directional brushing for satin metals, as a roughness map. */
function brushedTexture(scale = 1) {
	const c = document.createElement('canvas');
	c.width = 256; c.height = 256;
	const g = c.getContext('2d');
	g.fillStyle = '#7a7a7a'; g.fillRect(0, 0, 256, 256);
	for (let i = 0; i < 1400; i++) {
		const y = Math.random() * 256, v = 100 + Math.random() * 70;
		g.strokeStyle = `rgba(${v},${v},${v},${0.25 + Math.random() * 0.35})`;
		g.lineWidth = Math.random() * 1.2;
		g.beginPath(); g.moveTo(0, y); g.lineTo(256, y + (Math.random() - 0.5) * 2); g.stroke();
	}
	const t = new THREE.CanvasTexture(c);
	t.wrapS = t.wrapT = THREE.RepeatWrapping;
	t.repeat.set(scale, scale);
	return t;
}

/** Plaster wall with soft noise. */
function plasterTexture() {
	const c = document.createElement('canvas');
	c.width = 512; c.height = 512;
	const g = c.getContext('2d');
	g.fillStyle = '#d8d5ce'; g.fillRect(0, 0, 512, 512);
	const img = g.getImageData(0, 0, 512, 512);
	for (let i = 0; i < img.data.length; i += 4) {
		const n = (Math.random() - 0.5) * 14;
		img.data[i] += n; img.data[i + 1] += n; img.data[i + 2] += n;
	}
	g.putImageData(img, 0, 0);
	const t = new THREE.CanvasTexture(c);
	t.colorSpace = THREE.SRGBColorSpace;
	t.wrapS = t.wrapT = THREE.RepeatWrapping;
	t.repeat.set(4, 2);
	return t;
}

/** Braided stainless hose: diagonal weave as a bump + roughness map. */
function braidTexture() {
	const c = document.createElement('canvas');
	c.width = 128; c.height = 128;
	const g = c.getContext('2d');
	g.fillStyle = '#808080'; g.fillRect(0, 0, 128, 128);
	for (let k = -128; k < 256; k += 8) {
		g.strokeStyle = 'rgba(230,230,230,.9)'; g.lineWidth = 3;
		g.beginPath(); g.moveTo(k, 0); g.lineTo(k + 128, 128); g.stroke();
		g.strokeStyle = 'rgba(40,40,40,.7)'; g.lineWidth = 2;
		g.beginPath(); g.moveTo(k + 128, 0); g.lineTo(k, 128); g.stroke();
	}
	const t = new THREE.CanvasTexture(c);
	t.wrapS = t.wrapT = THREE.RepeatWrapping;
	t.repeat.set(1, 30);
	return t;
}

/** Scrolling water streaks inside glass sections. */
export function waterTexture() {
	const c = document.createElement('canvas');
	c.width = 256; c.height = 64;
	const g = c.getContext('2d');
	const grd = g.createLinearGradient(0, 0, 0, 64);
	grd.addColorStop(0, '#3d8fd6'); grd.addColorStop(0.5, '#7cc0f0'); grd.addColorStop(1, '#2f76b8');
	g.fillStyle = grd; g.fillRect(0, 0, 256, 64);
	for (let i = 0; i < 70; i++) {
		g.fillStyle = `rgba(255,255,255,${0.2 + Math.random() * 0.5})`;
		const x = Math.random() * 256, y = Math.random() * 64, w = 6 + Math.random() * 30;
		g.fillRect(x, y, w, 1 + Math.random() * 1.5);
	}
	const t = new THREE.CanvasTexture(c);
	t.colorSpace = THREE.SRGBColorSpace;
	t.wrapS = t.wrapT = THREE.RepeatWrapping;
	return t;
}

export function makeMaterials() {
	const brushed = brushedTexture(2);
	const braid = braidTexture();
	return {
		chrome: new THREE.MeshPhysicalMaterial({ color: 0xffffff, metalness: 1, roughness: 0.06, clearcoat: 0.4, clearcoatRoughness: 0.05 }),
		satinNickel: new THREE.MeshPhysicalMaterial({ color: 0xd9dbdc, metalness: 1, roughness: 0.32, roughnessMap: brushed }),
		brass: new THREE.MeshPhysicalMaterial({ color: 0xd8a74a, metalness: 1, roughness: 0.24, roughnessMap: brushed }),
		brassPolished: new THREE.MeshPhysicalMaterial({ color: 0xe2b457, metalness: 1, roughness: 0.1 }),
		copper: new THREE.MeshPhysicalMaterial({ color: 0xc27a4e, metalness: 1, roughness: 0.28, roughnessMap: brushed }),
		iron: new THREE.MeshStandardMaterial({ color: 0x2b2e31, metalness: 0.55, roughness: 0.55 }),
		redWheel: new THREE.MeshPhysicalMaterial({ color: 0x8c1d1b, metalness: 0.1, roughness: 0.35, clearcoat: 0.6, clearcoatRoughness: 0.2 }),
		vinylLever: new THREE.MeshPhysicalMaterial({ color: 0x1d4e8f, metalness: 0, roughness: 0.4, clearcoat: 0.5 }),
		rubber: new THREE.MeshStandardMaterial({ color: 0x151517, metalness: 0, roughness: 0.85 }),
		ceramic: new THREE.MeshPhysicalMaterial({ color: 0xf3f1ec, metalness: 0, roughness: 0.25, clearcoat: 1, clearcoatRoughness: 0.1 }),
		plastic: new THREE.MeshStandardMaterial({ color: 0x2d2f33, metalness: 0, roughness: 0.6 }),
		braid: new THREE.MeshPhysicalMaterial({ color: 0xcfd3d6, metalness: 1, roughness: 0.38, roughnessMap: braid, bumpMap: braid, bumpScale: 0.6 }),
		glass: new THREE.MeshPhysicalMaterial({ color: 0xbcd9ec, metalness: 0, roughness: 0.02, transparent: true, opacity: 0.16, clearcoat: 1, clearcoatRoughness: 0, envMapIntensity: 2.5, specularIntensity: 1, depthWrite: false }),
		wall: new THREE.MeshStandardMaterial({ map: plasterTexture(), roughness: 0.95, metalness: 0 }),
	};
}
