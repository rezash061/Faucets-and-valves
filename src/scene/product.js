/**
 * Product page: wires the 3D viewer (viewer.js) to the markup from
 * inc/product-3d.php. Loaded only when the section scrolls near the viewport.
 */
import { createViewer } from './viewer.js';

function webglOK() {
	try { return !!document.createElement('canvas').getContext('webgl2'); } catch (e) { return false; }
}

function init(sec) {
	if (sec.dataset.ready) return;
	sec.dataset.ready = '1';
	if (!webglOK()) return;
	const canvas = sec.querySelector('.p3d__canvas');
	let names = [];
	try { names = JSON.parse(sec.dataset.parts || '[]'); } catch (e) { /* keep defaults */ }
	const v = createViewer(canvas, { model: sec.dataset.model, labels: names });
	sec.classList.add('is-ready');
	sec.hidden = false;

	const slider = sec.querySelector('.p3d__explode');
	if (slider) {
		const out = sec.querySelector('.p3d__explode-value');
		const set = () => { v.state.explode = slider.value / 100; if (out) out.textContent = new Intl.NumberFormat('fa-IR').format(slider.value) + '٪'; };
		slider.addEventListener('input', set);
		set();
	}

	const toggle = sec.querySelector('.p3d__toggle');
	if (toggle) {
		const status = sec.querySelector('.p3d__status');
		toggle.addEventListener('click', () => {
			const on = toggle.getAttribute('aria-pressed') !== 'true';
			v.setOpen(on);
			toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
			toggle.textContent = on ? toggle.dataset.close : toggle.dataset.open;
			if (status) status.textContent = on ? status.dataset.open : status.dataset.closed;
		});
	}

	if (v.hasParts) {
		const lines = sec.querySelector('.p3d__lines');
		const box = sec.querySelector('.p3d__labels');
		const pts0 = v.labels();
		box.innerHTML = pts0.map(() => '<div class="alabel"><b></b><span></span></div>').join('');
		lines.innerHTML = pts0.map(() => '<path/><circle r="3"/>').join('');
		const els = [...box.children], paths = [...lines.querySelectorAll('path')], dots = [...lines.querySelectorAll('circle')];
		v.setOnFrame(() => {
			const pts = v.labels();
			const show = Math.max(0, Math.min(1, (v.state.explode - 0.6) / 0.25));
			const w = canvas.clientWidth;
			const narrow = w < 640;
			const colX = Math.min(...pts.map((p) => p.x)) - (narrow ? 24 : 64);
			const gap = narrow ? 24 : 40;
			const order = pts.map((p, i) => i).sort((a, b) => pts[a].y - pts[b].y);
			const ly = []; let prev = -1e9;
			order.forEach((i) => { ly[i] = Math.max(pts[i].y, prev + gap); prev = ly[i]; });
			pts.forEach((p, i) => {
				const el = els[i];
				if (el.firstChild.textContent !== p.label) { el.firstChild.textContent = p.label; el.lastChild.textContent = p.sub; }
				el.style.transform = `translate(${colX - 8}px, ${ly[i]}px) translate(-100%, -50%)`;
				el.style.opacity = show;
				paths[i].setAttribute('d', `M${p.x} ${p.y} H${colX + 16} L${colX} ${ly[i]}`);
				paths[i].style.opacity = show;
				dots[i].setAttribute('cx', p.x); dots[i].setAttribute('cy', p.y);
				dots[i].style.opacity = show;
			});
			lines.setAttribute('viewBox', `0 0 ${w} ${canvas.clientHeight}`);
		});
	}
}

document.querySelectorAll('.product-3d').forEach(init);
