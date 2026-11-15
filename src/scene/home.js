/**
 * Front page: the house. Reads window.FV_SCENE.rooms (one per category) and upgrades the
 * markup in #fv-house: room name labels over the model, and, for the picked room, a label on
 * each faucet/valve that links to its products.
 */
import { gsap } from './gsap.js';
import { createHouse } from './house.js';

const D = window.FV_SCENE || { rooms: [] };
const $ = (s, r = document) => r.querySelector(s);
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Where each spot's label sits relative to its point: [lift px, side]. Keeps neighbours apart. */
const LIFT = { 'kitchen.sink': [30, -46], 'kitchen.filter': [30, 52], 'kitchen.gas': [34, 30], 'wc.angle': [26, 50], 'wc.sprayer': [34, -10], 'mech.check': [96, -44], 'mech.globe': [92, 0], 'bath.shower': [30, -30], 'yard.tap': [30, -20], 'yard.gas': [36, 30] };

function webglOK() {
	if (navigator.connection && navigator.connection.saveData) return false;
	try { return !!document.createElement('canvas').getContext('webgl2'); } catch (e) { return false; }
}

function init(sec) {
	const canvas = $('.fv-house__canvas', sec);
	const labels = [...sec.querySelectorAll('.rlabel')];
	const spotsEl = $('.fv-spots', sec);
	const card = $('.fv-roomcard', sec);
	const backBtn = $('.fv-house__back', sec);
	const house = createHouse(canvas, {
		rooms: D.rooms.map((r) => ({ key: r.key, room: r.room })),
		onPick: (key) => pick(key),
		onHover: (key) => labels.forEach((l) => l.classList.toggle('is-hover', l.dataset.key === key)),
	});
	sec.classList.add('is-3d');
	const roomOf = Object.fromEntries(house.rooms.map((r) => [r.key, r]));

	// One link per faucet/valve, created up front and shown for the picked room.
	const spots = [];
	D.rooms.forEach((r) => {
		const room = roomOf[String(r.key)];
		if (!room) return;
		Object.keys(room.spots).forEach((id) => {
			const s = (r.spots || {})[id];
			if (!s) return;
			const a = document.createElement('a');
			a.className = 'spot'; a.href = s.url || r.url; a.tabIndex = -1;
			const [lift, dx] = LIFT[room.type + '.' + id] || [30, 0];
			a.style.setProperty('--lift', lift + 'px'); a.style.setProperty('--dx', dx + 'px');
			a.innerHTML = '<i></i><span><b></b><small></small></span>';
			a.querySelector('b').textContent = s.label; a.querySelector('small').textContent = s.sub || '';
			spotsEl.appendChild(a);
			spots.push({ el: a, key: String(r.key), pos: room.spots[id] });
		});
	});

	house.setOnFrame(() => {
		labels.forEach((l) => {
			const room = roomOf[l.dataset.key];
			if (!room) return;
			const p = house.project(room.anchor);
			l.style.transform = `translate(${p.x}px, ${p.y}px) translate(-50%, -100%)`;
		});
		spots.forEach((s) => {
			if (s.key !== house.selected) return;
			const p = house.project(s.pos);
			s.el.style.transform = `translate(${p.x}px, ${p.y}px)`;
		});
	});

	let lastFocus = null;
	let fromHistory = false;
	function close() {
		if (history.state && history.state.fvRoom) history.back(); // popstate does the rest
		else pick(null);
	}
	window.addEventListener('popstate', (e) => {
		fromHistory = true;
		pick(e.state && e.state.fvRoom ? e.state.fvRoom : null);
		fromHistory = false;
	});
	function pick(key) {
		key = key && roomOf[key] ? String(key) : null;
		if (key === house.selected) return;
		if (key && !house.selected) lastFocus = document.activeElement;
		const was = house.selected;
		house.select(key);
		sec.classList.toggle('is-room', !!key);
		// A history entry per visit, so the phone's back gesture zooms out instead of leaving.
		if (key && !was && !fromHistory) history.pushState({ fvRoom: key }, '');
		else if (key && was) history.replaceState({ fvRoom: key }, '');
		backBtn.hidden = !key;
		labels.forEach((l) => l.setAttribute('aria-expanded', l.dataset.key === key ? 'true' : 'false'));
		spots.forEach((s) => { const on = s.key === key; s.el.classList.toggle('is-on', on); s.el.tabIndex = on ? 0 : -1; });
		if (key) {
			const data = D.rooms.find((r) => String(r.key) === key);
			$('.fv-roomcard__title', card).textContent = data.name;
			$('.fv-roomcard__meta', card).textContent = data.meta || '';
			$('.fv-roomcard__all', card).href = data.url;
			card.hidden = false;
			requestAnimationFrame(() => card.classList.add('is-open'));
			setTimeout(() => { const b = $('.fv-roomcard__back', card); if (b.offsetParent) b.focus({ preventScroll: true }); }, 60);
		} else {
			card.classList.remove('is-open');
			setTimeout(() => { if (!house.selected) card.hidden = true; }, 500);
			if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
		}
	}
	$('.fv-rooms', sec).addEventListener('click', (e) => {
		const l = e.target.closest('.rlabel');
		if (!l || e.metaKey || e.ctrlKey || e.shiftKey) return; // let new-tab clicks through
		e.preventDefault(); pick(l.dataset.key);
	});
	$('.fv-roomcard__back', card).addEventListener('click', close);
	backBtn.addEventListener('click', close);
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && house.selected) close(); });

	if (!reduce && gsap) {
		house.intro();
		canvas.classList.add('is-in');
		gsap.fromTo(sec.querySelectorAll('.titleblock .w > span'), { yPercent: 110 }, { yPercent: 0, duration: 0.9, stagger: 0.06, ease: 'power3.out', delay: 0.6 });
		labels.forEach((l, i) => setTimeout(() => l.classList.add('is-in'), 1700 + i * 130));
	} else {
		house.still();
		labels.forEach((l) => l.classList.add('is-in'));
	}
}

function boot() {
	const sec = document.getElementById('fv-house');
	if (!sec || !D.rooms.length || !webglOK()) return;
	try { init(sec); } catch (err) { console.warn('[fv] house', err); sec.classList.remove('is-3d'); }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
