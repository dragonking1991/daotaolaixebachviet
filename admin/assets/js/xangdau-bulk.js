(function() {
	function items(formId) {
		return Array.prototype.slice.call(document.querySelectorAll('.xd-bulk-item[form="' + formId + '"]'));
	}
	document.querySelectorAll('.xd-bulk-all').forEach(function(all) {
		var formId = all.getAttribute('data-form');
		all.addEventListener('change', function() {
			items(formId).forEach(function(cb) { cb.checked = all.checked; });
		});
		items(formId).forEach(function(cb) {
			cb.addEventListener('change', function() {
				var list = items(formId);
				all.checked = list.length > 0 && list.every(function(x) { return x.checked; });
			});
		});
	});
	document.querySelectorAll('.xd-bulk-delete').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			var formId = btn.getAttribute('form');
			var n = items(formId).filter(function(cb) { return cb.checked; }).length;
			if (n === 0) {
				e.preventDefault();
				alert('Chưa chọn ' + btn.getAttribute('data-label') + ' nào.');
				return;
			}
			var msg = 'Xóa ' + n + ' ' + btn.getAttribute('data-label') + ' đã chọn?';
			if (btn.getAttribute('data-note')) msg += '\n\n' + btn.getAttribute('data-note');
			if (!confirm(msg)) e.preventDefault();
		});
	});
})();
