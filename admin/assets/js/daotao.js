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
				}).then(function(r){ if(r.isConfirmed) go(href); });
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
				}).then(function(r){ if(r.isConfirmed) go(href); });
				return;
			}

			if(href.indexOf('act=gvResetPass') !== -1){
				e.preventDefault(); e.stopImmediatePropagation();
				if(!hasSwal){ if(confirm('Đặt lại mật khẩu về mặc định (= CCCD)?')) go(href); return; }
				Swal.fire({ title: 'Đặt lại mật khẩu?', text: 'Mật khẩu cổng giáo viên sẽ về mặc định (= CCCD).', icon: 'question',
					showCancelButton: true, confirmButtonText: 'Đặt lại', cancelButtonText: 'Hủy' })
					.then(function(r){ if(r.isConfirmed) go(href); });
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
			if(e.target && e.target.hasAttribute('data-noload')) return;
			showLoading();
		}, true);
	}
	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
