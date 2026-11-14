/**
 * Motion loader.
 *
 * Finds [data-motion] elements and imports only the modules the page needs:
 *   data-motion="cinematic" → cinematic-reveal.js  (01 hero)
 *   data-motion="material"  → material-reveal.js   (02 product images)
 *   data-motion="stagger"   → staggered-entrance.js (03 grids; also drives material inside cards)
 *
 * Respects prefers-reduced-motion. If anything fails, the hidden start states are removed so
 * content is always visible.
 */
(function () {
	'use strict';

	var root = document.documentElement;
	var cfg = window.FV_MOTION || {};
	window.FV_MOTION_READY = true; // Tells the head failsafe we are alive.

	function reveal() {
		root.classList.remove('js-motion');
	}

	if (!root.classList.contains('js-motion') || !window.gsap || !window.ScrollTrigger) {
		reveal();
		return;
	}

	gsap.registerPlugin(ScrollTrigger);

	var ctx = {
		rtl: (document.dir || root.getAttribute('dir')) === 'rtl',
		small: window.matchMedia('(max-width: 767px)').matches,
		finePointer: window.matchMedia('(hover: hover) and (pointer: fine)').matches
	};

	var map = {
		cinematic: 'cinematic-reveal.js',
		stagger: 'staggered-entrance.js',
		material: 'material-reveal.js',
		countup: 'countup.js'
	};

	function load(name) {
		return import(cfg.base + map[name] + '?ver=' + encodeURIComponent(cfg.ver || ''));
	}

	function boot() {
		// WooCommerce's product gallery gets its class from PHP; mark it for Material Reveal.
		// Editor images with the "fv-material" class (block patterns) get it too.
		document.querySelectorAll('.woocommerce-product-gallery.material:not([data-motion]), .fv-material:not([data-motion])').forEach(function (el) {
			el.setAttribute('data-motion', 'material');
		});
		var jobs = [];
		Object.keys(map).forEach(function (name) {
			var els = document.querySelectorAll('[data-motion="' + name + '"]');
			if (!els.length) return;
			jobs.push(load(name).then(function (mod) { mod.init(Array.prototype.slice.call(els), ctx); }));
		});
		Promise.all(jobs).then(function () {
			// Images and fonts change positions after load; keep triggers accurate.
			window.addEventListener('load', function () { ScrollTrigger.refresh(); }, { once: true });
		}).catch(function (err) {
			if (window.console) console.error('[fv-motion]', err);
			reveal();
		});
	}

	// Re-run on content WooCommerce injects with AJAX (filters, infinite scroll).
	document.addEventListener('fv:content-updated', function () {
		load('stagger').then(function (mod) {
			var lists = document.querySelectorAll('[data-motion="stagger"]');
			mod.init(Array.prototype.slice.call(lists), ctx);
			ScrollTrigger.refresh();
		});
	});

	var mm = gsap.matchMedia();
	mm.add('(prefers-reduced-motion: no-preference)', boot);
	mm.add('(prefers-reduced-motion: reduce)', reveal);
})();
