/* Bộ import dùng chung: tab lọc dòng xem trước (data-ik-tabs → bảng có tr[data-kind]). */
(function () {
    function applyFilter(group, filter) {
        var table = document.getElementById(group.getAttribute('data-ik-tabs'));
        if (!table) return;
        var shown = 0;
        Array.prototype.forEach.call(table.querySelectorAll('tbody tr[data-kind]'), function (row) {
            var kinds = (row.getAttribute('data-kind') || '').split(' ');
            var visible = filter === 'all' || kinds.indexOf(filter) !== -1;
            row.style.display = visible ? '' : 'none';
            if (visible) shown++;
        });
        var empty = table.querySelector('.ik-empty-filter');
        if (empty) empty.style.display = shown === 0 ? '' : 'none';
        Array.prototype.forEach.call(group.querySelectorAll('.ik-tab'), function (tab) {
            var active = tab.getAttribute('data-filter') === filter;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    document.addEventListener('click', function (event) {
        var tab = event.target.closest ? event.target.closest('.ik-tab') : null;
        if (!tab) return;
        var group = tab.closest('[data-ik-tabs]');
        if (group) applyFilter(group, tab.getAttribute('data-filter'));
    });

    /* Nút gửi form import: hiện trạng thái đang xử lý, tránh bấm hai lần. */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('.ik-confirm-bar, form[data-ik-busy]')) return;
        if (event.defaultPrevented) return;
        var button = form.querySelector('button[type="submit"], button:not([type])');
        if (!button) return;
        setTimeout(function () {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>Đang xử lý…';
        }, 0);
    });
})();
