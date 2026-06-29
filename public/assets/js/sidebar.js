/* RenewDesk v2 – shared sidebar renderer */
(function () {
    var _badges = {};

    var ICONS = {
        dashboard:   '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        clients:     '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        projects:    '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        domains:     '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.6 2.6 2.6 15.4 0 18M12 3c-2.6 2.6-2.6 15.4 0 18"/>',
        hosting:     '<rect x="3" y="4" width="18" height="7" rx="1.8"/><rect x="3" y="13" width="18" height="7" rx="1.8"/><path d="M7 7.5h.01M7 16.5h.01"/>',
        maintenance: '<path d="M14.7 6.3a3.7 3.7 0 0 0-5 4.9L4 17v3h3l5.8-5.7a3.7 3.7 0 0 0 4.9-5l-2.5 2.5-2.2-2.2z"/>',
        backups:     '<path d="M7 18a4 4 0 0 1-.5-7.97 6 6 0 0 1 11.6 1.4A3.5 3.5 0 0 1 17 18z"/>',
        tasks:       '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.3l2.4 2.4 4.6-5"/>',
        settings:    '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        countries:   '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c1.7 1.7 2.6 4.2 2.6 9S13.7 19.3 12 21M12 3c-1.7 1.7-2.6 4.2-2.6 9s.9 7.3 2.6 9"/>'
    };

    function svg(w, h, paths) {
        return '<svg width="' + w + '" height="' + h + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + paths + '</svg>';
    }

    function navLink(id, href, label, active) {
        var cls = 'rd-nav-item' + (active === id ? ' active' : '');
        var badge = (_badges[id] && _badges[id] > 0) ? '<span class="rd-nav-badge">' + _badges[id] + '</span>' : '';
        return '<a href="' + href + '" class="' + cls + '">' + svg(18, 18, ICONS[id]) + label + badge + '</a>';
    }

    var REFRESH_ICON = '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/>';

    function buildHTML(active) {
        var fallbackIcon = '<div class="rd-logo-icon" id="rd-logo-fallback">' + svg(21, 21, REFRESH_ICON) + '</div>';
        var customImg = '<img src="assets/images/logo.png" class="rd-logo-img" alt="Logo"'
            + ' onerror="this.style.display=\'none\';document.getElementById(\'rd-logo-fallback\').style.display=\'grid\'"'
            + ' onload="document.getElementById(\'rd-logo-fallback\').style.display=\'none\'">';

        return [
            '<div class="rd-sidebar-logo">',
              customImg,
              fallbackIcon,
              '<span class="rd-logo-text">RenewDesk</span>',
            '</div>',
            '<div class="rd-nav-label">Workspace</div>',
            '<nav class="rd-nav">',
              navLink('dashboard', 'dashboard.html', 'Dashboard', active),
              navLink('clients',   'clients.html',   'Clients',   active),
              navLink('projects',  'projects.html',  'Projects',  active),
            '</nav>',
            '<div class="rd-nav-label">Assets</div>',
            '<nav class="rd-nav">',
              navLink('domains',      'domains.html',      'Domains',     active),
              navLink('hosting',      'hosting.html',      'Hosting',     active),
              navLink('maintenance',  'maintenance.html',  'Maintenance', active),
              navLink('backups',      'backups.html',      'Backups',     active),
              navLink('tasks',        'tasks.html',        'Tasks',       active),
            '</nav>',
            '<div class="rd-nav-label">System</div>',
            '<nav class="rd-nav">',
              navLink('countries', 'countries.html', 'Countries', active),
              navLink('settings',  'settings.html',  'Settings',  active),
            '</nav>',
            '<div class="rd-sidebar-footer">',
              '<div class="rd-user-row">',
                '<div class="rd-user-avatar">AD</div>',
                '<div class="rd-user-info">',
                  '<div class="rd-user-name">Admin</div>',
                  '<div class="rd-user-role">IT Manager</div>',
                '</div>',
                '<button id="logoutBtn" class="rd-logout-btn" title="Sign out">',
                  svg(16, 16, '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>'),
                '</button>',
              '</div>',
            '</div>',
            /* Mobile close overlay */
            '<div id="rd-overlay" onclick="rdSidebar.close()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:39"></div>'
        ].join('');
    }

    function attachLogout() {
        var btn = document.getElementById('logoutBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var base = typeof rdApi === 'function' ? rdApi('api/auth/logout.php') : 'api/auth/logout.php';
            fetch(base, { method: 'POST', credentials: 'same-origin' })
                .finally(function () { window.location.href = 'login.html'; });
        });
    }

    window.rdSidebar = {
        init: function (active) {
            var el = document.getElementById('rd-sidebar');
            if (!el) return;
            el.innerHTML = buildHTML(active);
            attachLogout();
        },
        /* Set alert badge on a nav item by its id key */
        setBadge: function (id, count) {
            _badges[id] = count;
            var links = document.querySelectorAll('#rd-sidebar .rd-nav-item');
            links.forEach(function (a) {
                var href = (a.getAttribute('href') || '').replace('.html', '');
                if (href !== id) return;
                var existing = a.querySelector('.rd-nav-badge');
                if (count > 0) {
                    if (existing) { existing.textContent = count; }
                    else {
                        var badge = document.createElement('span');
                        badge.className = 'rd-nav-badge';
                        badge.textContent = count;
                        a.appendChild(badge);
                    }
                } else if (existing) {
                    existing.remove();
                }
            });
        },
        open: function () {
            var el = document.getElementById('rd-sidebar');
            var ov = document.getElementById('rd-overlay');
            if (el) el.classList.add('open');
            if (ov) ov.style.display = 'block';
        },
        close: function () {
            var el = document.getElementById('rd-sidebar');
            var ov = document.getElementById('rd-overlay');
            if (el) el.classList.remove('open');
            if (ov) ov.style.display = 'none';
        }
    };
})();
