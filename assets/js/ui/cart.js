/**
 * Cart: update automatically after a quantity change (WooCommerce does the AJAX update).
 * Card-to-card: copy buttons.
 */
(function () {
	'use strict';

	// Auto-update the cart.
	var timer = null;
	document.addEventListener('change', function (e) {
		if (!e.target.matches('.woocommerce-cart-form input.qty')) return;
		clearTimeout(timer);
		timer = setTimeout(function () {
			var btn = document.querySelector('.woocommerce-cart-form [name="update_cart"]');
			if (btn) { btn.disabled = false; btn.removeAttribute('aria-disabled'); btn.click(); }
		}, 600);
	});

	// Receipt file picker: show the chosen file's name in place of the browser's own (English) text.
	document.addEventListener('change', function (e) {
		var input = e.target;
		if (!input.classList || !input.classList.contains('file-pick__input')) return;
		var name = input.closest('.file-pick').querySelector('.file-pick__name');
		name.textContent = input.files && input.files.length ? input.files[0].name : name.getAttribute('data-empty');
		input.closest('.file-pick').classList.toggle('has-file', !!(input.files && input.files.length));
	});

	// Copy buttons.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-copy]');
		if (!btn) return;
		var text = btn.getAttribute('data-copy');
		var done = function () {
			var label = btn.textContent;
			btn.textContent = 'کپی شد ✓';
			btn.classList.add('is-copied');
			setTimeout(function () { btn.textContent = label; btn.classList.remove('is-copied'); }, 1800);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done).catch(function () { fallback(text); done(); });
		} else {
			fallback(text); done();
		}
	});
	function fallback(text) {
		var t = document.createElement('textarea');
		t.value = text; t.setAttribute('readonly', ''); t.style.position = 'absolute'; t.style.left = '-9999px';
		document.body.appendChild(t); t.select();
		try { document.execCommand('copy'); } catch (err) { /* ignore */ }
		t.remove();
	}
})();
