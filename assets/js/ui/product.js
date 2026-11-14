/**
 * Product and cart helpers: quantity stepper (+ / −) and finish swatches.
 */
(function () {
	'use strict';

	var digits = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9' };

	function num(v, fallback) {
		var n = parseFloat(String(v).replace(/[۰-۹]/g, function (d) { return digits[d]; }));
		return isNaN(n) ? fallback : n;
	}

	function fire(el, type) {
		el.dispatchEvent(new Event(type, { bubbles: true }));
		if (window.jQuery) window.jQuery(el).trigger(type); // WooCommerce listens with jQuery.
	}

	/** Wrap each visible quantity input with − and + buttons. */
	function steppers(root) {
		(root || document).querySelectorAll('.quantity').forEach(function (box) {
			var input = box.querySelector('input.qty');
			if (!input || input.type === 'hidden' || box.dataset.stepper) return;
			box.dataset.stepper = '1';
			box.classList.add('qty-stepper');

			function make(dir, label, path) {
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'qty-stepper__btn';
				b.setAttribute('aria-label', label);
				b.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="' + path + '"/></svg>';
				b.addEventListener('click', function () {
					var step = num(input.step, 1) || 1;
					var min = num(input.min, 0);
					var max = input.max === '' ? Infinity : num(input.max, Infinity);
					var next = num(input.value, min) + dir * step;
					next = Math.min(max, Math.max(min, next));
					next = Math.round(next / step) * step;
					if (String(next) === input.value) return;
					input.value = next;
					fire(input, 'input');
					fire(input, 'change');
				});
				return b;
			}
			// DOM order plus, input, minus → in RTL the plus sits on the right.
			box.insertBefore(make(1, 'افزایش تعداد', 'M12 5v14M5 12h14'), input);
			box.appendChild(make(-1, 'کاهش تعداد', 'M5 12h14'));
			input.setAttribute('inputmode', 'numeric');
		});
	}

	/** Finish swatches drive the hidden variation select. */
	function swatches(root) {
		(root || document).querySelectorAll('.swatches').forEach(function (group) {
			if (group.dataset.ready) return;
			group.dataset.ready = '1';
			var select = group.parentNode.querySelector('select.swatches__select');
			if (!select) return;
			function sync() {
				group.querySelectorAll('.swatch').forEach(function (b) {
					b.setAttribute('aria-pressed', String(b.dataset.value === select.value));
					var opt = select.querySelector('option[value="' + CSS.escape(b.dataset.value) + '"]');
					b.disabled = !opt || opt.disabled;
				});
			}
			group.addEventListener('click', function (e) {
				var b = e.target.closest('.swatch');
				if (!b || b.disabled) return;
				select.value = select.value === b.dataset.value ? '' : b.dataset.value;
				fire(select, 'change');
				sync();
			});
			select.addEventListener('change', sync);
			if (window.jQuery) window.jQuery(select.form).on('woocommerce_update_variation_values reset_data', sync);
			sync();
		});
	}

	function init(root) { steppers(root); swatches(root); }
	init();

	// Section nav: mark the section in view (the brass valve under its link).
	var links = document.querySelectorAll('.product-sections__nav a');
	if (links.length && 'IntersectionObserver' in window) {
		var map = {};
		links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting || !map[en.target.id]) return;
				links.forEach(function (a) { a.classList.toggle('is-current', a === map[en.target.id]); });
			});
		}, { rootMargin: '-40% 0px -55% 0px' });
		Object.keys(map).forEach(function (id) { var el = document.getElementById(id); if (el) io.observe(el); });
	}

	// Cart totals re-render with AJAX.
	if (window.jQuery) {
		window.jQuery(document.body).on('updated_wc_div updated_cart_totals', function () { init(); });
	}
})();
