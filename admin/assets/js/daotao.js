/* Đào tạo: xác nhận xóa đẹp hơn (SweetAlert2), hoàn tác, loading overlay.
   Chỉ chạy trên trang có body.com-daotao. */
(function(){
	function init(){
		if(!document.body || !document.body.classList.contains('com-daotao')) return;
		var hasSwal = (typeof Swal !== 'undefined');

		// Move the persistent import result directly below the breadcrumb.
		var importNotice = document.getElementById('dt-import-notice');
		if(importNotice){
			var crumb = document.querySelector('.content-header');
			if(crumb) crumb.insertAdjacentElement('afterend', importNotice);
			requestAnimationFrame(function(){ importNotice.classList.add('is-visible'); });
			var closeNotice = importNotice.querySelector('.dt-import-notice-close');
			if(closeNotice) closeNotice.addEventListener('click', function(){
				importNotice.classList.remove('is-visible');
				importNotice.classList.add('is-dismissed');
				setTimeout(function(){ if(importNotice.parentNode) importNotice.parentNode.removeChild(importNotice); }, 240);
			});
		}

		// Loading overlay
		if(!document.getElementById('dt-loading')){
			var ov = document.createElement('div');
			ov.id = 'dt-loading';
			ov.innerHTML = '<div class="dt-spin"></div>';
			document.body.appendChild(ov);
		}
		function showLoading(){ var o = document.getElementById('dt-loading'); if(o) o.style.display = 'flex'; }
		function go(href){ showLoading(); window.location.href = href; }
		function isConfirmed(result){ return !!(result && (result.isConfirmed || result.value)); }

		// Chặn ở pha capture để thay thế confirm() gắn sẵn trên thẻ
		document.addEventListener('click', function(e){
			var a = e.target.closest ? e.target.closest('a') : null;
			if(!a) return;
			var href = a.getAttribute('href') || '';

			if(href.indexOf('act=crudDeleteAll') !== -1){
				e.preventDefault(); e.stopImmediatePropagation();
				if(!hasSwal){ if(confirm('Xóa toàn bộ? Thao tác không thể hoàn tác.')) go(href); return; }
				Swal.fire({
					title: 'Xóa toàn bộ?', icon: 'warning',
					html: 'Thao tác <b>không thể hoàn tác</b>. Hệ thống đã tự sao lưu trước khi xóa.<br>Gõ <b>XOA</b> để xác nhận.',
					input: 'text', inputPlaceholder: 'Gõ XOA',
					showCancelButton: true, confirmButtonText: 'Xóa toàn bộ', cancelButtonText: 'Hủy',
					confirmButtonColor: '#dc2626',
					preConfirm: function(v){ if((v||'').trim().toUpperCase() !== 'XOA') Swal.showValidationMessage('Gõ đúng chữ XOA để xác nhận'); return v; }
				}).then(function(r){ if(isConfirmed(r)) go(href); });
				return;
			}

			if(href.indexOf('act=crudDelete&') !== -1 || href.indexOf('act=khoaDelete') !== -1){
				e.preventDefault(); e.stopImmediatePropagation();
				var name = a.getAttribute('data-name') || '';
				if(!hasSwal){ if(confirm('Xóa' + (name ? ' "' + name + '"' : '') + '?')) go(href); return; }
				Swal.fire({
					title: 'Xóa' + (name ? ' "' + name + '"' : '') + '?',
					text: 'Bản ghi và dữ liệu liên quan sẽ bị xóa.', icon: 'warning',
					showCancelButton: true, confirmButtonText: 'Xóa', cancelButtonText: 'Hủy', confirmButtonColor: '#dc2626'
				}).then(function(r){ if(isConfirmed(r)) go(href); });
				return;
			}

			if(href.indexOf('act=gvResetPass') !== -1){
				e.preventDefault(); e.stopImmediatePropagation();
				if(!hasSwal){ if(confirm('Đặt lại mật khẩu về mặc định (= CCCD)?')) go(href); return; }
				Swal.fire({ title: 'Đặt lại mật khẩu?', text: 'Mật khẩu cổng giáo viên sẽ về mặc định (= CCCD).', icon: 'question',
					showCancelButton: true, confirmButtonText: 'Đặt lại', cancelButtonText: 'Hủy' })
					.then(function(r){ if(isConfirmed(r)) go(href); });
				return;
			}
		}, true);

		// Toast hoàn tác sau khi xóa 1 bản ghi
		try {
			var qs = new URLSearchParams(window.location.search);
			if(qs.get('undo') === '1' && hasSwal){
				Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Đã xóa',
					html: '<a href="index.php?com=daotao&act=crudUndo" style="color:#2b4fd6;font-weight:700;text-decoration:none;">↩ Hoàn tác</a>',
					showConfirmButton: false, timer: 6000, timerProgressBar: true });
			}
		} catch(err){}

		// Loading overlay khi submit form (lọc/import/lưu)
		document.addEventListener('submit', function(e){
			var form = e.target;
			if(!form) return;

			// Xác nhận khôi phục backup (ghi đè dữ liệu).
			if(form.classList && form.classList.contains('dt-restore-form') && !form.dataset.dtConfirmed){
				e.preventDefault(); e.stopImmediatePropagation();
				var fileName = (form.querySelector('input[name=file]') || {}).value || '';
				var submitRestore = function(){ form.dataset.dtConfirmed = '1'; showLoading(); form.submit(); };
				if(!hasSwal){ if(confirm('Khôi phục từ "' + fileName + '"? Dữ liệu hiện tại sẽ bị ghi đè.')) submitRestore(); return; }
				Swal.fire({
					title: 'Khôi phục dữ liệu?', icon: 'warning',
					html: 'Ghi đè dữ liệu hiện tại bằng bản sao lưu:<br><b>' + fileName + '</b><br><small>Hệ thống tự sao lưu hiện trạng trước khi khôi phục.</small>',
					showCancelButton: true, confirmButtonText: 'Khôi phục', cancelButtonText: 'Hủy', confirmButtonColor: '#d97706'
				}).then(function(r){ if(isConfirmed(r)) submitRestore(); });
				return;
			}

			if(form.hasAttribute('data-noload')) return;
			showLoading();
		}, true);

		// Chuẩn hóa CCCD khi dán/gõ: bỏ khoảng trắng, dấu chấm; chỉ giữ chữ số; cảnh báo độ dài.
		function normalizeCccdField(inp){
			var v = (inp.value || '').replace(/[\s.\-]/g, '').replace(/[^0-9]/g, '');
			if(v !== inp.value) inp.value = v;
			var ok = (v === '' || v.length === 9 || v.length === 11 || v.length === 12);
			inp.classList.toggle('is-invalid', !ok && v.length > 0);
			inp.setAttribute('title', ok ? '' : 'CCCD/CMND thường có 9, 11 hoặc 12 chữ số');
		}
		var cccdInputs = document.querySelectorAll('input[name*="cccd" i], input[name*="cmnd" i], input[data-cccd]');
		Array.prototype.forEach.call(cccdInputs, function(inp){
			inp.addEventListener('input', function(){ normalizeCccdField(inp); });
			inp.addEventListener('blur', function(){ normalizeCccdField(inp); });
		});
	}
	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();

/* Flash toast dùng chung cho các module khác (hóa đơn, xăng dầu…): định vị,
   tự ẩn và nút đóng cho #dt-import-notice khi KHÔNG ở trang đào tạo. */
(function(){
	function initFlash(){
		if(!document.body) return;
		var cl = document.body.classList;
		if(cl.contains('com-daotao')) return; // trang đào tạo đã tự xử lý
		var n = document.getElementById('dt-import-notice');
		if(!n) return;
		var crumb = document.querySelector('.content-header');
		if(crumb) crumb.insertAdjacentElement('afterend', n);
		requestAnimationFrame(function(){ n.classList.add('is-visible'); });
		var dismiss = function(){
			n.classList.remove('is-visible');
			n.classList.add('is-dismissed');
			setTimeout(function(){ if(n.parentNode) n.parentNode.removeChild(n); }, 240);
		};
		var btn = n.querySelector('.dt-import-notice-close');
		if(btn) btn.addEventListener('click', dismiss);
		if(n.classList.contains('alert-success')) setTimeout(dismiss, 6000);
	}
	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFlash);
	else initFlash();
})();
