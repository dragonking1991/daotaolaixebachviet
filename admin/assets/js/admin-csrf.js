/* Gắn token chống CSRF vào form/link của module Đào tạo và Xăng dầu (xem libraries/admin_csrf.php). */
(function () {
    var config = window.ADMIN_CSRF;
    if (!config || !config.token || !config.readOnly) return;

    function target(url) {
        try {
            var parsed = new URL(url || window.location.href, window.location.href);
            if (parsed.origin !== window.location.origin) return null;
            var com = parsed.searchParams.get('com') || '';
            if (!Object.prototype.hasOwnProperty.call(config.readOnly, com)) return null;
            return { url: parsed, com: com, act: parsed.searchParams.get('act') || '' };
        } catch (error) {
            return null;
        }
    }

    function isWrite(info) {
        return info && config.readOnly[info.com].indexOf(info.act) === -1;
    }

    function protectLink(link) {
        var info = target(link.getAttribute('href'));
        if (!isWrite(info) || info.url.searchParams.get('_csrf') === config.token) return;
        info.url.searchParams.set('_csrf', config.token);
        link.setAttribute('href', info.url.href);
    }

    function protectForm(form) {
        var post = (form.getAttribute('method') || 'get').toLowerCase() === 'post';
        var info = target(form.getAttribute('action'));
        if (!info) return;
        if (!post) {
            var actField = form.querySelector('[name="act"]');
            if (actField) info.act = actField.value || '';
            if (!isWrite(info)) return;
        }
        var field = form.querySelector('input[name="_csrf"]');
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_csrf';
            form.appendChild(field);
        }
        field.value = config.token;
    }

    function protect(root) {
        if (!root || !root.querySelectorAll) return;
        if (root.matches && root.matches('a[href]')) protectLink(root);
        if (root.matches && root.matches('form')) protectForm(root);
        Array.prototype.forEach.call(root.querySelectorAll('a[href]'), protectLink);
        Array.prototype.forEach.call(root.querySelectorAll('form'), protectForm);
    }

    function start() {
        protect(document);
        /* Form/link sinh động (modal, bảng tải lại) cũng được gắn token. */
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, protect);
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
        /* Nút formaction gửi tới module khác action của form vẫn cần token. */
        document.addEventListener('submit', function (event) {
            var form = event.target;
            var submitter = event.submitter;
            if (submitter && submitter.getAttribute('formaction')) {
                var info = target(submitter.getAttribute('formaction'));
                if (info) {
                    var field = form.querySelector('input[name="_csrf"]');
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = '_csrf';
                        form.appendChild(field);
                    }
                    field.value = config.token;
                    return;
                }
            }
            protectForm(form);
        }, true);
        if (window.jQuery) {
            window.jQuery.ajaxPrefilter(function (options) {
                if (target(options.url)) {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-Token'] = config.token;
                }
            });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
