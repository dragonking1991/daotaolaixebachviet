(function($) {
	'use strict';
	$(function() {
		if(!document.body.matches('.com-daotao, .com-xangdau')) return;
		if(!$.fn.select2) {
			console.error('Không tải được Select2 cho bộ chọn khóa.');
			return;
		}
		function normalize(text) {
			return String(text).normalize('NFD').replace(/[\u0300-\u036f]/g, '')
				.replace(/[đĐ]/g, 'd').toLowerCase().replace(/\s+/g, ' ').trim();
		}
		function matcher(params, data) {
			var term = normalize(params.term || '');
			if(!term) return data;
			if(data.children) {
				var children = data.children.map(function(child) { return matcher(params, child); }).filter(Boolean);
				return children.length ? $.extend({}, data, {children:children}) : null;
			}
			return normalize(data.text).indexOf(term) !== -1 ? data : null;
		}
		$('select').filter(function() {
			return ['id_khoa', 'data[id_khoa]', 'khoa', 'source_khoa', 'ky_khoa'].indexOf(this.name) !== -1;
		}).each(function() {
			var select = $(this);
			if(select.data('select2')) return;
			select.select2({
				width: Math.min(Math.max(select.outerWidth() || 280, 240), 420) + 'px',
				minimumInputLength: 0,
				minimumResultsForSearch: 0,
				matcher: matcher,
				language: {
					noResults: function() { return 'Không tìm thấy khóa phù hợp'; },
					searching: function() { return 'Đang tìm khóa…'; }
				}
			});
			select.next('.select2').css({maxWidth:'100%', marginRight:'0.5rem'});
			select.on('select2:open', function() {
				var search = document.querySelector('.select2-container--open .select2-search__field');
				if(search) {
					search.placeholder = 'Tìm mã hoặc tên khóa (có/không dấu)';
					search.setAttribute('aria-label', 'Tìm mã hoặc tên khóa');
					$(search).val('').trigger('input');
					search.focus();
				}
			});
		});
	});
})(jQuery);
