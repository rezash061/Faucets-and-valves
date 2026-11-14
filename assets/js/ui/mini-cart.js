/**
 * Mini-cart drawer: header cart icon opens it; it also opens after AJAX add-to-cart.
 */
(function () {
	'use strict';

	var drawer = document.getElementById('cart-drawer');
	if (!drawer) return;
	var panel = drawer.querySelector('.cart-drawer__panel');
	var last = null;

	function open() {
		if (!drawer.hidden) return;
		last = document.activeElement;
		drawer.hidden = false;
		document.body.classList.add('no-scroll');
		requestAnimationFrame(function () { drawer.classList.add('is-open'); });
		var close = drawer.querySelector('[data-drawer-close].icon-btn');
		if (close) close.focus();
	}
	function close() {
		drawer.classList.remove('is-open');
		document.body.classList.remove('no-scroll');
		setTimeout(function () { drawer.hidden = true; }, 300);
		if (last && document.contains(last)) last.focus();
	}

	document.addEventListener('click', function (e) {
		var cart = e.target.closest('a.header-cart, a.mobile-bar-cart');
		if (cart && !e.metaKey && !e.ctrlKey) { e.preventDefault(); open(); return; }
		if (e.target.closest('[data-drawer-close]')) close();
	});
	document.addEventListener('keydown', function (e) {
		if (drawer.hidden) return;
		if (e.key === 'Escape') close();
		if (e.key === 'Tab') {
			var f = panel.querySelectorAll('a[href], button:not([disabled]), input');
			if (!f.length) return;
			var first = f[0], lastEl = f[f.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); lastEl.focus(); }
			else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); first.focus(); }
		}
	});

	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', function (e, frags, hash, $btn) {
			// Inside the front page product sheet the header tank fills instead; keep the visitor in place.
			if ($btn && $btn.closest && $btn.closest('.fv-sheet, .fv-anatomy').length) return;
			open();
		});
	}
})();
