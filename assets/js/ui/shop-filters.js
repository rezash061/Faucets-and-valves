/**
 * Shop filters: apply without a page reload, keep the URL shareable, mobile filter sheet.
 *
 * Progressive enhancement: without JavaScript the panel is a normal GET form.
 * After new results arrive it fires `fv:content-updated` so the motion loader can run
 * the Staggered Entrance on the new cards.
 */
(function () {
	'use strict';

	var form = document.getElementById('shop-filter-form');
	var panel = document.getElementById('shop-filters');
	if (!form || !panel || !window.fetch || !window.DOMParser) return;

	var scrim = document.querySelector('[data-filters-close]');
	var desktop = window.matchMedia('(min-width: 1024px)');
	var controller = null;
	var digits = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9', '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };

	function latin(v) {
		return String(v).replace(/[۰-۹٠-٩]/g, function (d) { return digits[d]; }).replace(/[^0-9.]/g, '');
	}

	/** Build the listing URL from the form: arrays become comma lists, empties are dropped. */
	function urlFromForm() {
		var url = new URL(form.getAttribute('action'), location.href);
		var data = new FormData(form);
		var lists = {};
		data.forEach(function (value, key) {
			if (key.slice(-2) === '[]') {
				key = key.slice(0, -2);
				(lists[key] = lists[key] || []).push(value);
				return;
			}
			if (key === 'min_price' || key === 'max_price') value = latin(value);
			if (value === '' || value == null) return;
			url.searchParams.set(key, value);
		});
		Object.keys(lists).forEach(function (key) { url.searchParams.set(key, lists[key].join(',')); });
		// query_type_x only when filter_x is set.
		form.querySelectorAll('input[data-pair]').forEach(function (input) {
			if (!url.searchParams.has(input.dataset.pair)) url.searchParams.delete(input.name);
		});
		if (url.searchParams.get('orderby') === 'menu_order') url.searchParams.delete('orderby');
		return url.toString();
	}

	function swap(doc, selector) {
		var fresh = doc.querySelector(selector);
		var current = document.querySelector(selector);
		if (fresh && current) current.innerHTML = fresh.innerHTML;
	}

	/**
	 * Fetch a listing URL and swap in the results.
	 * @param {string} url
	 * @param {{push?: boolean, syncForm?: boolean}} opts
	 */
	function load(url, opts) {
		opts = opts || {};
		if (controller) controller.abort();
		controller = new AbortController();
		var results = document.getElementById('shop-results');
		results.setAttribute('aria-busy', 'true');
		results.classList.add('is-loading');

		return fetch(url, { credentials: 'same-origin', signal: controller.signal, headers: { 'X-FV-Partial': '1' } })
			.then(function (res) {
				if (!res.ok) throw new Error('HTTP ' + res.status);
				return res.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				swap(doc, '#shop-results');
				if (opts.syncForm) swap(doc, '#shop-filter-form');
				document.title = doc.title;
				if (opts.push !== false) history.pushState({ fvShop: true }, '', url);

				var top = results.getBoundingClientRect().top;
				if (top < 0) window.scrollBy({ top: top - 120, behavior: 'smooth' });

				document.dispatchEvent(new CustomEvent('fv:content-updated', { detail: { source: 'shop-filters' } }));
				if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
			})
			.catch(function (err) {
				if (err.name === 'AbortError') return;
				location.href = url; // Fall back to a normal page load.
			})
			.finally(function () {
				results.removeAttribute('aria-busy');
				results.classList.remove('is-loading');
			});
	}

	// Checkbox / select changes apply at once; price fields apply on change (blur or Enter).
	var timer = null;
	function onChange(e) {
		if (!desktop.matches && !e.target.matches('#shop-orderby')) return; // Mobile applies with the button.
		clearTimeout(timer);
		timer = setTimeout(function () { load(urlFromForm()); }, 250);
	}
	form.addEventListener('change', onChange);
	document.addEventListener('change', function (e) {
		if (e.target && e.target.id === 'shop-orderby') onChange(e);
	});

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		load(urlFromForm()).then(function () { setSheet(false); });
	});

	// Links that change filters (chips, clear all) and pagination.
	document.addEventListener('click', function (e) {
		var a = e.target.closest('a[data-filter-link], #shop-results .woocommerce-pagination a');
		if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
		e.preventDefault();
		load(a.href, { syncForm: true }).then(function () { setSheet(false); });
	});

	window.addEventListener('popstate', function () {
		load(location.href, { push: false, syncForm: true });
	});

	// Mobile sheet.
	var lastFocus = null;
	function setSheet(open) {
		panel.classList.toggle('is-open', open);
		if (scrim) scrim.hidden = !open;
		document.body.classList.toggle('no-scroll', open && !desktop.matches);
		document.querySelectorAll('.filters-open').forEach(function (b) { b.setAttribute('aria-expanded', String(open)); });
		if (open) {
			lastFocus = document.activeElement;
			var first = panel.querySelector('button, a, input');
			if (first) first.focus();
		} else if (lastFocus && document.contains(lastFocus)) {
			lastFocus.focus();
		}
	}
	document.addEventListener('click', function (e) {
		if (e.target.closest('.filters-open')) setSheet(true);
		else if (e.target.closest('.filters-close, [data-filters-close]')) setSheet(false);
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && panel.classList.contains('is-open')) setSheet(false);
	});
	desktop.addEventListener('change', function () { setSheet(false); });
})();
