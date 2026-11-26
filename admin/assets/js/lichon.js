/* Lịch ôn sát hạch — lưới xếp lịch: chọn giáo viên (cọ) rồi bấm/kéo qua các ô để xếp; Tẩy để bỏ ô. */
(function () {
	var cfg = window.LO_GRID;
	if (!cfg) return;
	var brush = 0; // 0 = xem, -1 = tẩy, >0 = id đăng ký
	var painting = null; // {action: 'assign'|'erase', dk: id, onlyDk: id|null}
	var queue = Promise.resolve();
	var toastEl = document.querySelector('[data-lo-toast]');
	var toastTimer = null;
	var hint = document.querySelector('[data-lo-hint] span');
	var hintDefault = hint ? hint.innerHTML : '';

	function toast(msg, type) {
		if (!toastEl) return;
		toastEl.textContent = msg;
		toastEl.className = 'lo-toast is-show is-' + (type || 'info');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.className = 'lo-toast'; }, type === 'error' ? 5000 : 3000);
	}

	function cells() { return document.querySelectorAll('.lo-cell'); }

	function highlight(id) {
		document.querySelectorAll('.lo-grid').forEach(function (g) { g.classList.toggle('has-hl', !!id); });
		cells().forEach(function (td) { td.classList.toggle('is-hl', !!id && +td.getAttribute('data-dk') === id); });
	}

	function refreshChip(id) {
		var d = cfg.dk[id];
		var chip = document.querySelector('[data-lo-gv="' + id + '"]');
		if (!d || !chip) return;
		var miss = Math.max(0, d.so_gio - d.da_xep);
		chip.querySelector('[data-lo-count]').textContent = d.da_xep + '/' + d.so_gio + 'h';
		var bar = chip.querySelector('[data-lo-bar]');
		bar.style.width = Math.min(100, d.so_gio ? Math.round(d.da_xep * 100 / d.so_gio) : 0) + '%';
		bar.className = 'progress-bar ' + (miss ? 'bg-primary' : 'bg-success');
		var badge = chip.querySelector('[data-lo-badge]');
		badge.textContent = miss ? '−' + miss + 'h' : '✓';
		badge.className = 'badge lo-gv-badge ' + (miss ? 'badge-danger' : 'badge-success');
		chip.setAttribute('data-miss', miss ? '1' : '0');
		if (+id === brush) renderHint();
	}

	function renderHint() {
		if (!hint) return;
		if (brush > 0) { var d = cfg.dk[brush]; hint.innerHTML = 'Đang xếp cho <b>' + esc(d.hoten) + '</b> (' + d.da_xep + '/' + d.so_gio + 'h). Bấm/kéo vào ô trống để xếp, bấm ô của GV này để bỏ. Nhấn <b>Esc</b> để thôi chọn.'; }
		else if (brush === -1) hint.innerHTML = 'Chế độ <b>Tẩy</b>: bấm hoặc kéo qua các ô để bỏ. Số giờ được trả lại cho giáo viên.';
		else hint.innerHTML = hintDefault;
	}

	function setBrush(id) {
		brush = id;
		document.querySelectorAll('.lo-gv').forEach(function (b) { b.classList.toggle('is-active', +b.getAttribute('data-lo-gv') === id); });
		document.querySelectorAll('.lo-tool').forEach(function (b) { b.classList.toggle('active', +b.getAttribute('data-lo-brush') === id); });
		document.body.classList.toggle('lo-painting', id !== 0);
		document.body.classList.toggle('lo-erasing', id === -1);
		highlight(id > 0 ? id : 0);
		renderHint();
	}

	function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

	function paintCell(td, id, manual) {
		var d = id ? cfg.dk[id] : null;
		td.setAttribute('data-dk', id || 0);
		td.textContent = d ? d.ten : '';
		td.style.background = d ? d.color : '';
		td.classList.toggle('is-filled', !!d);
		td.classList.toggle('is-manual', !!d && manual);
		if (!d) td.classList.remove('is-par');
		td.classList.toggle('is-hl', !!d && brush > 0 && id === brush);
		var label = 'xe ' + td.getAttribute('data-xe') + ' · ' + td.getAttribute('data-gio') + 'h–' + (+td.getAttribute('data-gio') + 1) + 'h';
		td.title = d ? d.hoten + ' · ' + label + (manual ? ' · xếp tay' : '') : label + ' · trống';
	}

	function send(td, newId, oldId) {
		var body = new FormData();
		body.append('_csrf', cfg.csrf);
		body.append('id_dot', cfg.dot);
		body.append('id_ngay', td.getAttribute('data-ngay'));
		body.append('so_xe', td.getAttribute('data-xe'));
		body.append('gio', td.getAttribute('data-gio'));
		body.append('id_dangky', newId);
		queue = queue.then(function () {
			return fetch('index.php?com=daotao&act=lichonCell', { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-CSRF-Token': cfg.csrf, 'Accept': 'application/json' } })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.ok) throw new Error(res.msg || 'Không thể lưu ô lịch.');
					if (res.counts) Object.keys(res.counts).forEach(function (k) { if (cfg.dk[k]) { cfg.dk[k].da_xep = res.counts[k].da_xep; cfg.dk[k].so_gio = res.counts[k].so_gio; refreshChip(k); } });
					if (res.warn) toast(res.warn, 'warn');
				})
				.catch(function (err) {
					// Hoàn tác giao diện khi máy chủ từ chối.
					if (newId && cfg.dk[newId]) { cfg.dk[newId].da_xep--; refreshChip(newId); }
					if (oldId && cfg.dk[oldId]) { cfg.dk[oldId].da_xep++; refreshChip(oldId); }
					paintCell(td, oldId, false);
					toast(err && err.message && err.message.indexOf('JSON') < 0 ? err.message : 'Phiên làm việc đã hết hạn hoặc mất kết nối. Vui lòng tải lại trang.', 'error');
				});
		});
	}

	function apply(td, action, onlyDk) {
		var cur = +td.getAttribute('data-dk');
		if (action === 'erase') {
			if (!cur || (onlyDk && cur !== onlyDk)) return;
			if (cfg.dk[cur]) { cfg.dk[cur].da_xep--; refreshChip(cur); }
			paintCell(td, 0, false);
			send(td, 0, cur);
			return;
		}
		var id = painting ? painting.dk : brush;
		if (cur === id) return;
		var d = cfg.dk[id];
		if (d.da_xep >= d.so_gio) { toast(d.ten + ' đã xếp đủ ' + d.so_gio + ' giờ. Tăng số giờ đăng ký nếu cần xếp thêm.', 'error'); painting = null; return; }
		d.da_xep++; refreshChip(id);
		if (cur && cfg.dk[cur]) { cfg.dk[cur].da_xep--; refreshChip(cur); }
		paintCell(td, id, true);
		send(td, id, cur);
	}

	document.querySelectorAll('.lo-gv').forEach(function (b) {
		var id = +b.getAttribute('data-lo-gv');
		b.addEventListener('click', function () { if (cfg.canEdit) setBrush(brush === id ? 0 : id); });
		b.addEventListener('mouseenter', function () { if (brush <= 0) highlight(id); });
		b.addEventListener('mouseleave', function () { if (brush <= 0) highlight(0); });
	});
	document.querySelectorAll('.lo-tool').forEach(function (b) {
		b.addEventListener('click', function () { setBrush(+b.getAttribute('data-lo-brush')); });
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setBrush(0); });

	var grids = document.querySelectorAll('.lo-grid');
	grids.forEach(function (g) {
		g.addEventListener('mousedown', function (e) {
			var td = e.target.closest('.lo-cell');
			if (!td || e.button !== 0) return;
			var cur = +td.getAttribute('data-dk');
			if (!cfg.canEdit || brush === 0) {
				if (cur) { highlight(cur); var d = cfg.dk[cur]; toast(d.hoten + ' · ' + d.da_xep + '/' + d.so_gio + ' giờ ' + d.hang, 'info'); }
				else if (cfg.canEdit) toast('Chọn giáo viên ở bảng bên phải (hoặc Tẩy) trước khi bấm vào ô.', 'info');
				return;
			}
			e.preventDefault();
			if (brush === -1) painting = { action: 'erase', onlyDk: null };
			else if (cur === brush) painting = { action: 'erase', onlyDk: brush };
			else painting = { action: 'assign', dk: brush };
			apply(td, painting.action, painting.onlyDk);
		});
		g.addEventListener('mouseover', function (e) {
			if (!painting) return;
			var td = e.target.closest('.lo-cell');
			if (td) apply(td, painting.action, painting.onlyDk);
		});
	});
	document.addEventListener('mouseup', function () { painting = null; });

	var pq = document.querySelector('[data-lo-pq]'), pm = document.querySelector('[data-lo-pmiss]');
	function filterPanel() {
		var q = pq ? pq.value.trim().toLowerCase() : '';
		document.querySelectorAll('.lo-gv').forEach(function (b) {
			var d = cfg.dk[b.getAttribute('data-lo-gv')];
			var ok = (!q || b.getAttribute('data-q').indexOf(q) >= 0) && (!pm || !pm.checked || d.da_xep < d.so_gio);
			(b.closest('.lo-gv-row') || b).classList.toggle('d-none', !ok);
		});
		syncChecks();
	}
	if (pq) pq.addEventListener('input', filterPanel);
	if (pm) pm.addEventListener('change', filterPanel);

	/* Tích chọn GV để xóa lịch theo GV (menu "Xóa lịch"). */
	var checkAll = document.querySelector('[data-lo-check-all]');
	var clearGv = document.querySelector('[data-lo-clear-gv]');
	function checkedIds() {
		return Array.prototype.filter.call(document.querySelectorAll('[data-lo-check]'), function (c) { return c.checked; }).map(function (c) { return +c.value; });
	}
	function syncChecks() {
		var ids = checkedIds();
		document.querySelectorAll('[data-lo-check-count]').forEach(function (el) { el.textContent = ids.length; });
		document.querySelectorAll('[data-lo-check]').forEach(function (c) { c.closest('.lo-gv-row').classList.toggle('is-checked', c.checked); });
		if (checkAll) {
			var visible = Array.prototype.filter.call(document.querySelectorAll('[data-lo-check]'), function (c) { return !c.closest('.lo-gv-row').classList.contains('d-none'); });
			var n = visible.filter(function (c) { return c.checked; }).length;
			checkAll.checked = visible.length > 0 && n === visible.length;
			checkAll.indeterminate = n > 0 && n < visible.length;
		}
	}
	document.querySelectorAll('[data-lo-check]').forEach(function (c) { c.addEventListener('change', syncChecks); });
	if (checkAll) checkAll.addEventListener('change', function () {
		document.querySelectorAll('[data-lo-check]').forEach(function (c) { if (!c.closest('.lo-gv-row').classList.contains('d-none')) c.checked = checkAll.checked; });
		syncChecks();
	});
	if (clearGv) clearGv.addEventListener('submit', function (e) {
		var ids = checkedIds();
		if (!ids.length) { e.preventDefault(); toast('Tích chọn giáo viên ở bảng bên phải trước, rồi chọn "Xóa lịch → Lịch của GV đã tích".', 'error'); return; }
		var hours = 0, names = ids.map(function (id) { var d = cfg.dk[id]; hours += d ? d.da_xep : 0; return d ? d.ten : id; });
		if (!confirm('Xóa toàn bộ lịch đã xếp (' + hours + ' giờ) của ' + ids.length + ' giáo viên:\n' + names.join(', ') + '?')) { e.preventDefault(); return; }
		clearGv.querySelectorAll('input[name="dk[]"]').forEach(function (el) { el.remove(); });
		ids.forEach(function (id) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'dk[]'; i.value = id; clearGv.appendChild(i); });
	});
	filterPanel();

	if (cfg.focus && cfg.dk[cfg.focus]) {
		if (cfg.canEdit) setBrush(cfg.focus); else highlight(cfg.focus);
		var first = document.querySelector('.lo-cell[data-dk="' + cfg.focus + '"]');
		var chip = document.querySelector('[data-lo-gv="' + cfg.focus + '"]');
		if (first) first.scrollIntoView({ block: 'center' });
		else if (chip) chip.scrollIntoView({ block: 'center' });
	}
})();
