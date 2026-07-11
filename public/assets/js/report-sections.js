/**
 * Maintenance report section catalog — shared by report-edit.html and
 * report-view.html. Mirrors the 16-section Site Maintenance Report
 * template 1:1, plus an unlimited "custom" section for anything the
 * template doesn't cover.
 */
window.RD_REPORT_SECTIONS = [
    { key: 'loading_speed', title: 'Site Loading Speed', fields: [
        { name: 'performance_score', label: 'Performance Score (0-100)', type: 'number', band: 'score' },
        { name: 'evidence_screenshot', label: 'Visual Evidence', type: 'image', wide: false }
    ]},
    { key: 'image_optimization', title: 'Image Size Optimized', fields: [
        { name: 'status', label: 'Status', type: 'select', options: ['Yes', 'No'], band: 'yesno' },
        { name: 'plugin_name', label: 'Plugin Used', type: 'text', placeholder: 'e.g. ShortPixel, TinyPNG, Smush' },
        { name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'e.g. All images resized/compressed using TinyPNG before upload.' }
    ]},
    { key: 'traffic_analytics', title: 'Traffic & Analytics', fields: [
        { name: 'evidence_screenshots', label: 'Visual Evidence (Analytics screenshots)', type: 'images' }
    ]},
    { key: 'plugins', title: 'Plugins Used', fields: [
        { name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'e.g. Elementor Pro and Yoast SEO Premium are paid plugins (license cost applies).' }
    ], repeatable: { name: 'rows', label: 'Plugins', columns: [
        { name: 'plugin_name', label: 'Plugin' }, { name: 'version', label: 'Version' }, { name: 'reason', label: 'Why Used' }
    ]}},
    { key: 'caching', title: 'Caching Mechanism Used', fields: [
        { name: 'description', label: 'Description', type: 'textarea' }
    ]},
    { key: 'cms_version', title: 'WordPress Version Used', fields: [
        { name: 'current_version', label: 'Current Version', type: 'text', placeholder: 'e.g. WP 6.8.1' },
        { name: 'theme_version', label: 'Theme Version', type: 'text' },
        { name: 'php_mysql_compat_checked', label: 'PHP / MySQL Compatibility Checked', type: 'select', options: ['Yes', 'No'], band: 'yesno' }
    ]},
    { key: 'backup_status', title: 'Backup Status', fields: [], repeatable: { name: 'rows', label: 'Backups', columns: [
        { name: 'backup_type', label: 'Backup Type' }, { name: 'frequency', label: 'Frequency' }, { name: 'storage_location', label: 'Storage Location' },
        { name: 'last_backup_date', label: 'Last Backup Date', type: 'date' }, { name: 'status', label: 'Status' }
    ], seed: [
        { backup_type: 'Full Site (Files + DB)', frequency: '', storage_location: '', last_backup_date: '', status: 'OK' },
        { backup_type: 'Database Only', frequency: '', storage_location: '', last_backup_date: '', status: 'OK' }
    ]}},
    { key: 'security_checks', title: 'Security Checks', fields: [
        { name: 'ssl_valid', label: 'SSL certificate valid and active', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'malware_scan', label: 'Malware / vulnerability scan completed', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'login_attempts_monitored', label: 'Login attempts monitored / limited', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'admin_username_non_default', label: 'Admin username is non-default', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'firewall_active', label: 'Firewall / security plugin active', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'file_permissions_reviewed', label: 'File permissions reviewed', type: 'select', options: ['Yes', 'No'], band: 'yesno', wide: true },
        { name: 'evidence_screenshot', label: 'Visual Evidence (security scan screenshot)', type: 'image', wide: true }
    ]},
    { key: 'basic_seo', title: 'Basic SEO Setup', fields: [
        { name: 'status', label: 'Status', type: 'select', options: ['Yes', 'No'], band: 'yesno' },
        { name: 'notes', label: 'Notes', type: 'textarea' }
    ]},
    { key: 'unnecessary_links_removed', title: 'Unnecessary Links Removed', fields: [
        { name: 'status', label: 'Status', type: 'select', options: ['Yes', 'No'], band: 'yesno' },
        { name: 'notes', label: 'Notes', type: 'textarea' }
    ]},
    { key: 'broken_links', title: 'Broken Links / 404 Check', fields: [], repeatable: { name: 'rows', label: 'Broken Links', columns: [
        { name: 'url', label: 'URL / Page' }, { name: 'issue_found', label: 'Issue Found' }, { name: 'resolution', label: 'Resolution' }
    ]}},
    { key: 'sitemap_robots', title: 'Sitemap / Robots.txt', fields: [
        { name: 'sitemap_url', label: 'Sitemap URL', type: 'text' },
        { name: 'robots_url', label: 'Robots.txt URL', type: 'text' }
    ]},
    { key: 'hardcoded_design', title: 'Hardcoded Design or Structure', fields: [
        { name: 'status', label: 'Status', type: 'select', options: ['Yes', 'No'], band: 'yesno' },
        { name: 'notes', label: 'Notes', type: 'textarea' }
    ]},
    { key: 'summary_recommendations', title: 'Summary & Recommendations', fields: [
        { name: 'summary', label: 'Summary', type: 'textarea', placeholder: 'Short narrative summary of this period\'s maintenance activity, key wins, and any outstanding issues.' },
        { name: 'next_steps', label: 'Recommended Next Steps', type: 'textarea' },
        { name: 'next_maintenance_date', label: 'Next Scheduled Maintenance Date', type: 'date' }
    ]},
    { key: 'custom', title: 'Custom Section', unlimited: true, fields: [
        { name: 'content', label: 'Content', type: 'textarea' }
    ]}
];

window.RD_REPORT = (function () {
    function findCatalog(key) {
        return window.RD_REPORT_SECTIONS.find(function (s) { return s.key === key; });
    }

    function bandClass(value, band) {
        if (!value && value !== 0) return 'rd-badge-neutral';
        if (band === 'yesno') {
            var v = String(value).trim().toLowerCase();
            return v === 'yes' ? 'rd-badge-good' : (v === 'no' ? 'rd-badge-bad' : 'rd-badge-neutral');
        }
        if (band === 'score') {
            var n = parseFloat(value);
            if (isNaN(n)) return 'rd-badge-neutral';
            if (n >= 90) return 'rd-badge-good';
            if (n >= 50) return 'rd-badge-warn';
            return 'rd-badge-bad';
        }
        if (band === 'uptime') {
            var u = parseFloat(value);
            if (isNaN(u)) return 'rd-badge-neutral';
            if (u >= 99.9) return 'rd-badge-good';
            if (u >= 98) return 'rd-badge-warn';
            return 'rd-badge-bad';
        }
        return 'rd-badge-neutral';
    }

    function newInstance(key) {
        var cat = findCatalog(key);
        if (!cat) return null;
        var fields = {};
        cat.fields.forEach(function (f) { fields[f.name] = f.type === 'images' ? [] : ''; });
        if (cat.repeatable) {
            fields[cat.repeatable.name] = cat.repeatable.seed ? JSON.parse(JSON.stringify(cat.repeatable.seed)) : [];
        }
        return {
            instance_id: key + '-' + Math.random().toString(36).slice(2, 9),
            key: key,
            title: cat.title,
            enabled: true,
            fields: fields
        };
    }

    function duplicateInstance(instance) {
        var clone = JSON.parse(JSON.stringify(instance));
        clone.instance_id = instance.key + '-' + Math.random().toString(36).slice(2, 9);
        clone.title = instance.title + ' (copy)';
        return clone;
    }

    return { findCatalog: findCatalog, bandClass: bandClass, newInstance: newInstance, duplicateInstance: duplicateInstance };
})();
