/* Thanh thao tác nổi: khi tích chọn dòng trong danh sách, hiện lại các nút/ô chọn thao tác của form ở cuối màn hình để không phải cuộn lên. */
(function() {
	var SKIP_NAMES = { all: 1, delete_course: 1, delete_all: 1 };
	var bar, activeFormId = '';

	function itemSelector() { return '.dt-bulk-item[form], .xd-bulk-item[form]'; }
	function checkedFor(formId) {
		return document.querySelectorAll('.dt-bulk-item[form="' + formId + '"]:checked, .xd-bulk-item[form="' + formId + '"]:checked').length;
	}
	function controls(form) {
		var buttons = [], selects = [];
		Array.prototype.forEach.call(form.elements, function(el) {
			if (bar && bar.contains(el)) return;
			if (el.closest('table') || el.offsetParent === null) return;
			if (el.tagName === 'BUTTON' && el.type === 'submit' && !SKIP_NAMES[el.name]) buttons.push(el);
			else if (el.tagName === 'SELECT') selects.push(el);
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-bulk-form="' + form.id + '"]'), function(el) {
			if (!bar.contains(el) && el.offsetParent !== null) buttons.push(el);
		});
		return { buttons: buttons, selects: selects };
	}
	function ensureStyle() {
		if (document.getElementById('admin-bulk-bar-style')) return;
		var st = document.createElement('style');
		st.id = 'admin-bulk-bar-style';
		st.textContent = '#admin-bulk-bar{position:fixed;bottom:0;right:0;z-index:1031;background:#fff;border-top:2px solid #007bff;box-shadow:0 -2px 8px rgba(0,0,0,.15);padding:8px 16px;display:none;align-items:center;flex-wrap:wrap;gap:8px}' +
			'#admin-bulk-bar .abb-count{font-weight:600;margin-right:4px}';
		document.head.appendChild(st);
	}
	function render(formId) {
		var form = document.getElementById(formId);
		var n = checkedFor(formId);
		if (!form || !n) { if (bar) bar.style.display = 'none'; return; }
		ensureStyle();
		if (!bar) { bar = document.createElement('div'); bar.id = 'admin-bulk-bar'; document.body.appendChild(bar); }
		var c = controls(form);
		if (!c.buttons.length) { bar.style.display = 'none'; return; }
		bar.innerHTML = '';
		var count = document.createElement('span');
		count.className = 'abb-count';
		count.textContent = 'Đã chọn ' + n;
		bar.appendChild(count);
		var proxies = [];
		c.selects.forEach(function(sel) {
			var copy = sel.cloneNode(true);
			copy.removeAttribute('name'); copy.removeAttribute('form'); copy.removeAttribute('id');
			copy.value = sel.value;
			copy.addEventListener('change', function() { sel.value = copy.value; sel.dispatchEvent(new Event('change', { bubbles: true })); sync(); });
			bar.appendChild(copy);
			proxies.push({ copy: copy, sel: sel });
		});
		var btns = [];
		c.buttons.forEach(function(orig) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = orig.className;
			b.innerHTML = orig.innerHTML;
			b.addEventListener('click', function() { orig.click(); });
			bar.appendChild(b);
			btns.push({ proxy: b, orig: orig });
		});
		function sync() {
			var missing = proxies.some(function(p) { return p.sel.value === ''; });
			btns.forEach(function(x) { x.proxy.disabled = missing || x.orig.disabled; });
		}
		sync();
		var wrap = document.querySelector('.content-wrapper');
		bar.style.left = (wrap ? Math.max(0, wrap.getBoundingClientRect().left) : 0) + 'px';
		bar.style.display = 'flex';
	}
	function onChange(e) {
		var t = e.target;
		if (t.matches && t.matches('.dt-bulk-all, .xd-bulk-all')) activeFormId = t.getAttribute('data-form');
		else if (t.matches && t.matches(itemSelector())) activeFormId = t.getAttribute('form');
		else return;
		setTimeout(function() { render(activeFormId); }, 60);
	}
	document.addEventListener('change', onChange);
	window.addEventListener('resize', function() { if (activeFormId) render(activeFormId); });
})();
