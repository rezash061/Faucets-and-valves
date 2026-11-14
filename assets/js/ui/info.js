/**
 * Info pages: highlight the table of contents entry in view; filter FAQ questions.
 */
(function () {
	'use strict';

	var links = document.querySelectorAll('.info-toc a');
	if (links.length && 'IntersectionObserver' in window) {
		var map = {};
		links.forEach(function (a) { map[decodeURIComponent(a.hash.slice(1))] = a; });
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting) return;
				links.forEach(function (a) { a.classList.toggle('is-current', a === map[en.target.id]); });
			});
		}, { rootMargin: '-20% 0px -70% 0px' });
		Object.keys(map).forEach(function (id) { var el = document.getElementById(id); if (el) io.observe(el); });
	}

	var box = document.querySelector('.faq__search');
	var body = document.querySelector('.faq__body');
	if (!box || !body) return;
	var items = body.querySelectorAll('details');
	if (!items.length) return;
	box.hidden = false;
	var input = box.querySelector('input');
	var none = document.querySelector('.faq__none');
	// Persian/Arabic letter variants and digits compare equal.
	function norm(s) {
		return s.toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[‌ً-ْ]/g, '')
			.replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); });
	}
	input.addEventListener('input', function () {
		var q = norm(input.value.trim());
		var shown = 0;
		items.forEach(function (d) {
			var hit = !q || norm(d.textContent).indexOf(q) !== -1;
			d.classList.toggle('is-hidden', !hit);
			if (hit) shown++;
			if (q && hit) d.open = true;
		});
		none.hidden = shown !== 0;
	});
})();
