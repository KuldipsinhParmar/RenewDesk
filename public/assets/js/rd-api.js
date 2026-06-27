/**
 * Build API URLs so the app works at domain root or in a subdirectory.
 * Optional override: <meta name="renewdesk-api-base" content="/my-subfolder">
 */
(function () {
    function baseDir() {
        var meta = document.querySelector('meta[name="renewdesk-api-base"]');
        if (meta && meta.content.trim() !== '') {
            return meta.content.trim().replace(/\/+$/, '') + '/';
        }
        var segments = location.pathname.split('/').filter(function (s) { return s.length > 0; });
        if (segments.length === 0) return '/';
        var last = segments[segments.length - 1];
        if (last.indexOf('.') !== -1) segments.pop();
        return segments.length ? '/' + segments.join('/') + '/' : '/';
    }

    window.rdApi = function (path) {
        return baseDir() + String(path).replace(/^\/+/, '');
    };

    /* Global error toast — call rdError('message') from any page */
    window.rdError = function (msg) {
        var el = document.getElementById('rd-error-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'rd-error-toast';
            el.style.cssText = [
                'position:fixed;top:18px;right:18px;z-index:9999',
                'padding:13px 18px;border-radius:12px',
                'background:var(--bad-soft);color:var(--bad)',
                'border:1px solid rgba(223,67,63,.25)',
                'font-size:14px;font-weight:600;max-width:360px',
                'box-shadow:0 4px 20px rgba(0,0,0,.12)',
                'display:none;align-items:center;gap:10px'
            ].join(';');
            el.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg><span id="rd-error-text"></span>';
            document.body.appendChild(el);
        }
        document.getElementById('rd-error-text').textContent = msg;
        el.style.display = 'flex';
        clearTimeout(el._t);
        el._t = setTimeout(function () { el.style.display = 'none'; }, 5000);
    };

    /* Consistent date formatter — avoids timezone shift from new Date('YYYY-MM-DD') */
    window.rdFormatDate = function (d) {
        if (!d) return '—';
        var parts = String(d).split('T')[0].split('-');
        if (parts.length !== 3) return d;
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    };
})();
