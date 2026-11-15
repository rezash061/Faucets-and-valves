/**
 * Header behaviour: solid background on scroll, search panel, mobile nav drawer.
 */
(function () {
	'use strict';

	var header = document.getElementById('site-header');
	if (!header) return;

	// Overlay header (front page) turns solid after a little scroll.
	if (header.dataset.overlay === 'true') {
		var onScroll = function () { header.classList.toggle('is-solid', window.scrollY > 40); };
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	} else {
		header.classList.add('is-solid');
	}

	// Search panel (header button and mobile bar button share it).
	var panel = document.getElementById('header-search');
	var toggles = document.querySelectorAll('.search-toggle');
	var searchOpener = null;
	function setSearch(open, opener) {
		if (!panel) return;
		panel.hidden = !open;
		toggles.forEach(function (t) { t.setAttribute('aria-expanded', String(open)); });
		header.classList.toggle('search-open', open);
		if (open) {
			searchOpener = opener || document.activeElement;
			header.classList.add('is-solid');
			var input = panel.querySelector('input[type="search"]');
			if (input) input.focus();
		} else {
			if (header.dataset.overlay === 'true') header.classList.toggle('is-solid', window.scrollY > 40);
			// Back to the button that opened it, if focus was still inside the panel.
			if (searchOpener && document.contains(searchOpener) && (!document.activeElement || document.activeElement === document.body || panel.contains(document.activeElement))) searchOpener.focus();
			searchOpener = null;
		}
	}
	toggles.forEach(function (t) {
		t.addEventListener('click', function () { setSearch(panel.hidden, t); });
	});

	// Mobile drawer.
	var drawer = document.getElementById('nav-drawer');
	var openBtn = header.querySelector('.nav-toggle');
	var lastFocus = null;
	function setDrawer(open) {
		if (!drawer || !openBtn) return;
		drawer.hidden = !open;
		openBtn.setAttribute('aria-expanded', String(open));
		document.body.classList.toggle('no-scroll', open);
		if (open) {
			lastFocus = document.activeElement;
			var first = drawer.querySelector('a, button');
			if (first) first.focus();
		} else if (lastFocus) {
			lastFocus.focus();
		}
	}
	if (openBtn) openBtn.addEventListener('click', function () { setDrawer(true); });
	if (drawer) {
		drawer.addEventListener('click', function (e) {
			if (e.target.closest('[data-close], .nav-drawer__close')) setDrawer(false);
		});
		drawer.addEventListener('keydown', function (e) {
			if (e.key !== 'Tab') return;
			var f = drawer.querySelectorAll('a[href], button:not([disabled])');
			if (!f.length) return;
			var first = f[0], last = f[f.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		});
	}

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		if (drawer && !drawer.hidden) setDrawer(false);
		else if (panel && !panel.hidden) setSearch(false);
	});
})();
