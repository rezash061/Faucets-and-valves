/**
 * Front page scenes: the pipe manifold (categories) and the exploded faucet (anatomy).
 *
 * The markup is rendered by PHP and works on its own (links to categories, static product
 * story). This script upgrades it to 3D when WebGL is available, by adding .is-3d to each
 * section. Data comes from window.FV_SCENE (see inc/scene.php).
 */
import { gsap, ScrollTrigger } from './gsap.js';
import { createManifold } from './manifold.js';
import { createAnatomy } from './anatomy.js';
import { createThumbs } from './thumbs.js';

const D = window.FV_SCENE || { branches: [], anatomy: {} };
const $ = (s, r = document) => r.querySelector(s);
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

function webglOK() {
	if (navigator.connection && navigator.connection.saveData) return false;
	try {
		const c = document.createElement('canvas');
		return !!(window.WebGL2RenderingContext && c.getContext('webgl2'));
	} catch (e) { return false; }
}

let thumbs = null;
function fillThumbs(root) {
	const imgs = root.querySelectorAll('img[data-thumb]:not([src])');
	if (!imgs.length) return;
	thumbs ||= createThumbs();
	imgs.forEach((img) => { img.src = thumbs.get(img.dataset.thumb); });
}

/* ---------------- Manifold ---------------- */
function initManifold(stage) {
	const canvas = $('.fv-manifold__canvas', stage);
	const labelsEl = $('.fv-branches', stage);
	const labelEls = [...labelsEl.querySelectorAll('.blabel')];
	const sheet = $('.fv-sheet', stage);
	const sheetBody = $('.fv-sheet__body', sheet);
	let openKey = null;

	const manifold = createManifold(canvas, {
		branches: D.branches.map((b) => ({ key: String(b.key), kind: b.kind })),
		onPick: (key) => (key === openKey ? close() : open(key)),
		onHover: (key) => labelEls.forEach((l) => l.classList.toggle('is-hover', l.dataset.key === key)),
	});
	stage.classList.add('is-3d');

	const byKey = Object.fromEntries(manifold.branches.map((b) => [b.key, b]));
	manifold.setOnFrame(() => {
		labelEls.forEach((l) => {
			const b = byKey[l.dataset.key];
			if (!b) return;
			const p = manifold.project(b.labelAnchor);
			l.style.transform = `translate(${p.x}px, ${p.y}px) translate(-100%, -100%)`;
		});
	});

	labelsEl.addEventListener('click', (e) => {
		const l = e.target.closest('.blabel');
		if (!l || e.metaKey || e.ctrlKey || e.shiftKey) return; // let new-tab clicks through
		e.preventDefault();
		l.dataset.key === openKey ? close() : open(l.dataset.key);
	});

	let lastFocus = null;
	function open(key) {
		const data = D.branches.find((b) => String(b.key) === key);
		const tpl = document.getElementById('fv-sheet-' + key);
		if (!data || !tpl) return;
		lastFocus = document.activeElement;
		openKey = key;
		manifold.setOpen(key);
		labelEls.forEach((l) => {
			const on = l.dataset.key === key;
			l.classList.toggle('is-open', on);
			l.setAttribute('aria-expanded', on ? 'true' : 'false');
		});
		$('.fv-sheet__title', sheet).textContent = data.name;
		$('.fv-sheet__meta', sheet).textContent = data.meta;
		$('.fv-sheet__all', sheet).href = data.url;
		sheetBody.replaceChildren(tpl.content.cloneNode(true));
		fillThumbs(sheetBody);
		sheet.hidden = false;
		requestAnimationFrame(() => sheet.classList.add('is-open'));
		if (!reduce) gsap.fromTo(sheetBody.querySelectorAll('li'), { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.5, stagger: 0.07, delay: 0.25, ease: 'power2.out' });
		setTimeout(() => $('.fv-sheet__close', sheet).focus({ preventScroll: true }), 50);
	}
	function close() {
		if (!openKey) return;
		openKey = null;
		manifold.setOpen(null);
		labelEls.forEach((l) => { l.classList.remove('is-open'); l.setAttribute('aria-expanded', 'false'); });
		sheet.classList.remove('is-open');
		setTimeout(() => { if (!openKey) sheet.hidden = true; }, 600);
		if (lastFocus) lastFocus.focus({ preventScroll: true });
	}
	$('.fv-sheet__close', sheet).addEventListener('click', close);
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && openKey) close(); });

	// Cinematic reveal: the drawing wipes in from the right while the camera settles.
	if (!reduce) {
		manifold.intro();
		gsap.timeline()
			.fromTo(canvas, { clipPath: 'inset(0 0 0 100%)' }, { clipPath: 'inset(0 0 0 0%)', duration: 1.5, ease: 'expo.out' })
			.fromTo(stage.querySelectorAll('.titleblock .w > span'), { yPercent: 110 }, { yPercent: 0, duration: 0.9, stagger: 0.06, ease: 'power3.out' }, 0.5)
			.fromTo(stage.querySelectorAll('.titleblock p, .titleblock .tb-cell'), { opacity: 0 }, { opacity: 1, duration: 0.6, stagger: 0.05 }, 1);
		labelEls.forEach((l, i) => setTimeout(() => l.classList.add('is-in'), 1500 + i * 120));
	} else {
		manifold.openMain();
		labelEls.forEach((l) => l.classList.add('is-in'));
	}
}

/* ---------------- Anatomy ---------------- */
function initAnatomy(sec) {
	const canvas = $('.fv-anatomy__canvas', sec);
	const anat = createAnatomy(canvas, { labels: (D.anatomy && D.anatomy.labels) || [] });
	sec.classList.add('is-3d');
	const lines = $('.fv-anatomy__lines', sec);
	const albl = $('.fv-anatomy__labels', sec);
	albl.innerHTML = Array.from({ length: anat.partsCount }, () => '<div class="alabel"><b></b><span></span></div>').join('');
	lines.innerHTML = Array.from({ length: anat.partsCount }, () => '<path/><circle r="3"/>').join('');
	const aEls = [...albl.children];
	const lPaths = [...lines.querySelectorAll('path')];
	const lDots = [...lines.querySelectorAll('circle')];
	const texts = [...sec.querySelectorAll('.stage-text')];
	const bar = $('.fv-anatomy__progress b', sec);
	const narrow = () => canvas.clientWidth < 700;

	anat.setOnFrame(() => {
		const pts = anat.labels();
		const show = Math.max(0, Math.min(1, (anat.state.explode - 0.72) / 0.2));
		const colX = Math.min(...pts.map((p) => p.x)) - (narrow() ? 28 : 72); // label column left of the object
		// Push labels apart vertically so none overlap; leaders bend to meet them.
		const gap = narrow() ? 26 : 46;
		const order = pts.map((p, i) => i).sort((a, b) => pts[a].y - pts[b].y);
		const ly = []; let prev = -1e9;
		order.forEach((i) => { ly[i] = Math.max(pts[i].y, prev + gap); prev = ly[i]; });
		pts.forEach((p, i) => {
			const el = aEls[i];
			if (el.firstChild.textContent !== p.label) { el.firstChild.textContent = p.label; el.lastChild.textContent = p.sub; }
			el.style.transform = `translate(${colX - 10}px, ${ly[i]}px) translate(-100%, -50%)`;
			el.style.opacity = show;
			lPaths[i].setAttribute('d', `M${p.x} ${p.y} H${colX + 18} L${colX} ${ly[i]}`);
			lPaths[i].style.opacity = show;
			lDots[i].setAttribute('cx', p.x); lDots[i].setAttribute('cy', p.y);
			lDots[i].style.opacity = show;
		});
		lines.setAttribute('viewBox', `0 0 ${canvas.clientWidth} ${canvas.clientHeight}`);
	});

	if (reduce || !ScrollTrigger) {
		anat.state.spin = 0.4;
		texts.forEach((t, i) => { t.style.display = i === texts.length - 1 ? '' : 'none'; });
		return;
	}
	gsap.registerPlugin(ScrollTrigger);
	gsap.set(texts, { autoAlpha: 0 });
	gsap.set(texts[0], { autoAlpha: 1 });
	const tl = gsap.timeline({
		scrollTrigger: {
			trigger: sec, start: 'top top', end: '+=2800', scrub: 0.9, pin: true,
			onUpdate: (s) => { if (bar) bar.style.transform = `scaleX(${s.progress})`; },
		},
	});
	tl.to(texts[0], { autoAlpha: 0, y: -20, duration: 0.3 }, 0.1)
		.to(anat.state, { explode: 1, spin: 0.35, duration: 1, ease: 'power2.inOut' }, 0)
		.fromTo(texts[1], { autoAlpha: 0, y: 20 }, { autoAlpha: 1, y: 0, duration: 0.3 }, 0.55)
		.to(anat.state, { spin: 0.6, duration: 0.6, ease: 'none' })
		.to(texts[1], { autoAlpha: 0, y: -20, duration: 0.3 })
		.to(anat.state, { explode: 0, spin: 1.2, duration: 1, ease: 'power3.inOut' }, '<');
	if (texts[2]) tl.fromTo(texts[2], { autoAlpha: 0, y: 20 }, { autoAlpha: 1, y: 0, duration: 0.35 }, '-=0.35');
}

/* ---------------- Cart tank feedback ---------------- */
function initCartFeedback() {
	if (!window.jQuery) return;
	window.jQuery(document.body).on('added_to_cart', (e, frags, hash, $btn) => {
		const b = $btn && $btn[0];
		if (b && b.classList.contains('fv-sheet__add')) { b.classList.add('is-added'); b.textContent = D.i18n ? D.i18n.added : b.textContent; }
	});
}

function boot() {
	initCartFeedback();
	if (!webglOK() || !window.gsap) return;
	const m = document.getElementById('fv-manifold');
	const a = document.getElementById('fv-anatomy');
	try { if (m && D.branches.length) initManifold(m); } catch (err) { console.warn('[fv] manifold', err); }
	try { if (a) initAnatomy(a); } catch (err) { console.warn('[fv] anatomy', err); }
	if (window.ScrollTrigger) ScrollTrigger.refresh();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
