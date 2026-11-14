/**
 * Quick order: rows with product search (combobox), quantities, pasted code lists,
 * add everything to the cart, then go to the cart.
 */
(function () {
	'use strict';

	var root = document.getElementById('quick-order');
	var cfg = window.FV_QO;
	if (!root || !cfg) return;
	root.hidden = false;

	var rowsEl = root.querySelector('[data-qo="rows"]');
	var fmt = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: cfg.decimals });
	var digits = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9', '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };
	var uid = 0;

	function latin(v) { return String(v).replace(/[۰-۹٠-٩]/g, function (d) { return digits[d]; }); }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

	function ajax(action, params, method) {
		method = method || 'GET';
		var url = new URL(cfg.ajax, location.href);
		url.searchParams.set('action', action);
		url.searchParams.set('nonce', cfg.nonce);
		var opts = { method: method, credentials: 'same-origin' };
		if (method === 'GET') {
			Object.keys(params).forEach(function (k) { url.searchParams.set(k, params[k]); });
		} else {
			var body = new FormData();
			Object.keys(params).forEach(function (k) {
				if (Array.isArray(params[k])) params[k].forEach(function (v) { body.append(k + '[]', v); });
				else body.append(k, params[k]);
			});
			opts.body = body;
		}
		return fetch(url, opts).then(function (r) { return r.json(); }).then(function (res) {
			if (!res || !res.success) throw new Error((res && res.data && res.data.message) || 'error');
			return res.data;
		});
	}

	/* ---------- Rows ---------- */

	function addRow(product, qty) {
		var id = ++uid;
		var row = document.createElement('div');
		row.className = 'qo__row';
		row.setAttribute('role', 'row');
		row.innerHTML =
			'<span class="qo__n" role="cell"></span>' +
			'<span class="qo__product" role="cell">' +
				'<span class="qo__combo">' +
					'<input type="text" class="qo__search" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="qo-list-' + id + '" placeholder="' + esc(cfg.i18n.search) + '" autocomplete="off">' +
					'<ul class="qo__list" id="qo-list-' + id + '" role="listbox" hidden></ul>' +
				'</span>' +
				'<span class="qo__picked" hidden></span>' +
			'</span>' +
			'<span class="qo__unit" role="cell">—</span>' +
			'<span class="qo__qty" role="cell"><input type="text" inputmode="numeric" class="qo__qty-input" value="' + (qty || 1) + '" aria-label="' + esc(cfg.i18n.qty) + '"></span>' +
			'<span class="qo__line" role="cell">—</span>' +
			'<span role="cell"><button type="button" class="icon-btn qo__remove" aria-label="' + esc(cfg.i18n.remove) + '"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6l12 12M18 6 6 18"/></svg></button></span>';
		rowsEl.appendChild(row);
		bindRow(row);
		if (product) pick(row, product);
		renumber();
		return row;
	}

	function renumber() {
		rowsEl.querySelectorAll('.qo__row').forEach(function (r, i) { r.querySelector('.qo__n').textContent = fmt.format(i + 1); });
		update();
	}

	function pick(row, p) {
		row._product = p;
		var picked = row.querySelector('.qo__picked');
		picked.innerHTML =
			(p.image ? '<img src="' + esc(p.image) + '" alt="" width="44" height="44">' : '<span class="qo__noimg"></span>') +
			'<span><b>' + esc(p.name) + '</b><small dir="ltr">' + esc(p.sku || '') + '</small>' +
			(p.in_stock ? '' : '<em>' + esc(cfg.i18n.outStock) + '</em>') + '</span>' +
			'<button type="button" class="qo__change" aria-label="تغییر کالا">تغییر</button>';
		picked.hidden = false;
		row.querySelector('.qo__combo').hidden = true;
		row.querySelector('.qo__unit').textContent = fmt.format(p.price);
		row.classList.toggle('is-out', !p.in_stock);
		update();
	}

	function qtyOf(row) {
		var n = parseFloat(latin(row.querySelector('.qo__qty-input').value));
		return isNaN(n) || n < 0 ? 0 : n;
	}

	function update() {
		var lines = 0, total = 0;
		rowsEl.querySelectorAll('.qo__row').forEach(function (row) {
			var p = row._product, q = qtyOf(row);
			row.querySelector('.qo__line').textContent = p ? fmt.format(p.price * q) : '—';
			if (p && q > 0 && p.in_stock) { lines++; total += p.price * q; }
		});
		root.querySelector('[data-qo="lines"]').textContent = fmt.format(lines);
		root.querySelector('[data-qo="total"]').textContent = fmt.format(total);
	}

	function bindRow(row) {
		var input = row.querySelector('.qo__search');
		var list = row.querySelector('.qo__list');
		var items = [], active = -1, timer = null, seq = 0;

		function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; }
		function highlight(i) {
			var opts = list.querySelectorAll('[role="option"]');
			opts.forEach(function (o, j) { o.setAttribute('aria-selected', String(j === i)); });
			active = i;
			if (opts[i]) { input.setAttribute('aria-activedescendant', opts[i].id); opts[i].scrollIntoView({ block: 'nearest' }); }
		}
		function render() {
			if (!items.length) {
				list.innerHTML = '<li class="qo__none">' + esc(cfg.i18n.none) + '</li>';
			} else {
				list.innerHTML = items.map(function (p, i) {
					return '<li role="option" id="' + list.id + '-' + i + '" aria-selected="false" data-i="' + i + '">' +
						(p.image ? '<img src="' + esc(p.image) + '" alt="" width="36" height="36">' : '<span class="qo__noimg"></span>') +
						'<span><b>' + esc(p.name) + '</b><small dir="ltr">' + esc(p.sku || '') + '</small></span>' +
						'<span class="qo__opt-price">' + fmt.format(p.price) + '</span></li>';
				}).join('');
			}
			list.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}
		function choose(i) {
			if (!items[i]) return;
			pick(row, items[i]);
			close();
			var q = row.querySelector('.qo__qty-input');
			q.focus(); q.select();
			if (row === rowsEl.lastElementChild) addRow();
		}

		input.addEventListener('input', function () {
			clearTimeout(timer);
			var term = input.value.trim();
			if (term.length < 2) { close(); return; }
			timer = setTimeout(function () {
				var my = ++seq;
				ajax('fv_qo_search', { q: term }).then(function (data) {
					if (my !== seq) return;
					items = data; render(); if (items.length) highlight(0);
				}).catch(close);
			}, 250);
		});
		input.addEventListener('keydown', function (e) {
			if (list.hidden) return;
			if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(items.length - 1, active + 1)); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(0, active - 1)); }
			else if (e.key === 'Enter') { e.preventDefault(); choose(active); }
			else if (e.key === 'Escape') { close(); }
		});
		input.addEventListener('blur', function () { setTimeout(close, 150); });
		list.addEventListener('mousedown', function (e) {
			var li = e.target.closest('[data-i]');
			if (li) { e.preventDefault(); choose(+li.dataset.i); }
		});

		row.querySelector('.qo__qty-input').addEventListener('input', update);
		row.addEventListener('click', function (e) {
			if (e.target.closest('.qo__remove')) {
				row.remove();
				if (!rowsEl.children.length) addRow();
				renumber();
			} else if (e.target.closest('.qo__change')) {
				row._product = null;
				row.querySelector('.qo__picked').hidden = true;
				row.querySelector('.qo__combo').hidden = false;
				row.querySelector('.qo__unit').textContent = '—';
				input.value = ''; input.focus();
				update();
			}
		});
	}

	/* ---------- Paste a list ---------- */

	var paste = root.querySelector('#qo-paste');
	var pasteBtn = root.querySelector('[data-qo="toggle-paste"]');
	pasteBtn.addEventListener('click', function () {
		paste.hidden = !paste.hidden;
		pasteBtn.setAttribute('aria-expanded', String(!paste.hidden));
		if (!paste.hidden) paste.querySelector('textarea').focus();
	});

	root.querySelector('[data-qo="parse"]').addEventListener('click', function () {
		var msg = paste.querySelector('.qo__paste-msg');
		var lines = latin(paste.querySelector('textarea').value).split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean);
		var wanted = lines.map(function (l) {
			var parts = l.split(/[\s,;،\t]+/).filter(Boolean);
			var qty = parts.length > 1 ? parseFloat(parts[parts.length - 1]) : 1;
			var sku = parts.length > 1 && !isNaN(qty) ? parts.slice(0, -1).join(' ') : parts.join(' ');
			return { sku: sku, qty: isNaN(qty) || qty <= 0 ? 1 : qty };
		});
		if (!wanted.length) return;
		msg.textContent = '…';
		ajax('fv_qo_skus', { skus: wanted.map(function (w) { return w.sku; }) }, 'POST').then(function (map) {
			var missing = [];
			// Drop empty rows before adding pasted ones.
			rowsEl.querySelectorAll('.qo__row').forEach(function (r) { if (!r._product) r.remove(); });
			wanted.forEach(function (w) {
				var p = map[w.sku];
				if (p) addRow(p, w.qty); else missing.push(w.sku);
			});
			addRow();
			msg.textContent = missing.length ? cfg.i18n.notFound + ' ' + missing.join('، ') : '';
			if (!missing.length) { paste.querySelector('textarea').value = ''; paste.hidden = true; pasteBtn.setAttribute('aria-expanded', 'false'); }
		}).catch(function (e) { msg.textContent = e.message; });
	});

	/* ---------- Submit ---------- */

	root.querySelector('[data-qo="add-row"]').addEventListener('click', function () {
		addRow().querySelector('.qo__search').focus();
	});

	var submit = root.querySelector('[data-qo="submit"]');
	var out = root.querySelector('[data-qo="msg"]');
	submit.addEventListener('click', function () {
		var lines = [];
		rowsEl.querySelectorAll('.qo__row').forEach(function (r) {
			var q = qtyOf(r);
			if (r._product && r._product.in_stock && q > 0) lines.push({ id: r._product.id, qty: q });
		});
		if (!lines.length) { out.textContent = cfg.i18n.empty; return; }
		submit.disabled = true;
		var label = submit.innerHTML;
		submit.textContent = cfg.i18n.adding;
		out.textContent = '';
		ajax('fv_qo_add', { lines: JSON.stringify(lines) }, 'POST').then(function (data) {
			if (data.errors && data.errors.length) {
				out.textContent = data.errors.join(' ');
				submit.disabled = false; submit.innerHTML = label;
				if (data.added) setTimeout(function () { location.href = data.cart_url; }, 2500);
				return;
			}
			location.href = data.cart_url;
		}).catch(function (e) {
			out.textContent = e.message;
			submit.disabled = false; submit.innerHTML = label;
		});
	});

	addRow();
})();
