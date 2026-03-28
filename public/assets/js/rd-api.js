/**
 * Build API URLs so the app works at domain root or in a subdirectory.
 * Optional override: <meta name="renewdesk-api-base" content="/my-subfolder">
 */
(function () {
    function baseDir() {
        const meta = document.querySelector('meta[name="renewdesk-api-base"]');
        if (meta && meta.content.trim() !== '') {
            return meta.content.trim().replace(/\/+$/, '') + '/';
        }
        const pathname = location.pathname;
        const segments = pathname.split('/').filter(function (s) {
            return s.length > 0;
        });
        if (segments.length === 0) {
            return '/';
        }
        const last = segments[segments.length - 1];
        if (last.indexOf('.') !== -1) {
            segments.pop();
        }
        return segments.length ? '/' + segments.join('/') + '/' : '/';
    }

    window.rdApi = function (path) {
        const p = String(path).replace(/^\/+/, '');
        const dir = baseDir();
        if (dir === '/') {
            return dir + p;
        }
        return dir + p;
    };
})();
