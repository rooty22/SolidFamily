<?php
use App\Core\Auth;
use App\Models\Admin;

// Main admins can do everything: nothing to lock.
$lockAdmin = Auth::admin();
if (!$lockAdmin || !empty($lockAdmin['is_super'])) {
    return;
}

// What this admin may do per admin-portal path segment (e.g. 'share-requests' => 'view'); the page script uses it to
// disable every control that would be refused by the server anyway (Middleware::adminAuth stays the real guard).
$lockLevels = [];
foreach (Admin::sections() as $key => $def) {
    $granted = Admin::permissionsOf($lockAdmin)[$key] ?? 'none';
    foreach ($def[2] as $segment) {
        $lockLevels[$segment] = $granted;
    }
}
// Endpoints lent to other sections (same list the server uses), with the level this admin has in those sections.
$lockShared = [];
foreach (Admin::sharedEndpoints() as $pattern => $sections) {
    $lockShared[] = [
        're' => $pattern,
        'manage' => (bool) array_filter($sections, fn($s) => Admin::can($lockAdmin, $s, Admin::LEVEL_MANAGE)),
    ];
}
$lockTitle = is_rtl() ? 'ليست لديك صلاحية لهذا الإجراء' : 'You do not have permission for this action';
?>
<style>
    .perm-locked, .perm-locked:hover { opacity: .45 !important; cursor: not-allowed !important; filter: grayscale(.35); box-shadow: none !important; transform: none !important; }
    a.perm-locked { pointer-events: auto; text-decoration: none; }
    /* Plain text links (member names ...) keep their look, they just do not navigate */
    a.perm-soft-locked { cursor: not-allowed !important; text-decoration: none !important; }
</style>
<script>
(function () {
    var LEVELS = <?= json_encode($lockLevels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var TITLE = <?= json_encode($lockTitle, JSON_UNESCAPED_UNICODE) ?>;

    var SHARED = <?= json_encode($lockShared, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // True when a data-changing endpoint belongs to another section but is lent to one this admin fully controls.
    function lentToMe(path) {
        return SHARED.some(function (s) { return s.manage && new RegExp(s.re.replace(/^\^/, '')).test(path); });
    }

    // 'none' < 'view' < 'manage'. Returns true when the admin may NOT use a target of the given kind.
    function blocked(path, needsManage) {
        var m = path.match(/\/admin\/([^\/?#]+)/);
        if (!m || !(m[1] in LEVELS)) { return false; }   // logout, language switch, ... are not permissioned sections
        var level = LEVELS[m[1]];
        if (level === 'manage') { return false; }
        if (needsManage && lentToMe(path)) { return false; }
        if (level === 'view') { return needsManage; }
        return true;                                      // 'none': not even viewing
    }

    function lockElement(el) {
        el.classList.add('perm-locked');
        el.setAttribute('aria-disabled', 'true');
        el.setAttribute('title', TITLE);
        if ('disabled' in el) { el.disabled = true; }
        if (el.tagName === 'A') { el.setAttribute('tabindex', '-1'); }
    }

    function pathOf(url) {
        try { return new URL(url, window.location.href).pathname; } catch (e) { return ''; }
    }

    function apply() {
        // 1. Forms that change data: lock every control inside, and never let them submit.
        document.querySelectorAll('form').forEach(function (form) {
            if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') { return; }
            if (!blocked(pathOf(form.getAttribute('action') || window.location.href), true)) { return; }
            form.setAttribute('data-perm-locked', '1');
            form.querySelectorAll('button, input:not([type=hidden]), select, textarea').forEach(lockElement);
            // Buttons that sit outside the form but submit it (form="id").
            if (form.id) {
                document.querySelectorAll('[form="' + form.id + '"]').forEach(lockElement);
            }
        });

        // 2. Links: add / edit pages need manage, everything else needs at least view.
        document.querySelectorAll('a[href]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) { return; }
            var path = pathOf(href);
            var needsManage = /\/(create|edit)$/.test(path) || /\/admin\/content\/home$/.test(path);
            if (blocked(path, needsManage)) {
                if (/btn/.test(a.className)) { lockElement(a); } else {
                    a.classList.add('perm-soft-locked');
                    a.setAttribute('aria-disabled', 'true');
                    a.setAttribute('title', TITLE);
                    a.setAttribute('tabindex', '-1');
                }
            }
        });

        // 3. Buttons that only open an edit-only popup / panel (it holds locked forms and nothing to read).
        document.querySelectorAll('[data-bs-toggle="modal"], [data-bs-toggle="collapse"]').forEach(function (trigger) {
            var sel = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
            if (!sel || sel.charAt(0) !== '#') { return; }
            var target;
            try { target = document.querySelector(sel); } catch (e) { return; }
            if (!target) { return; }
            var forms = target.querySelectorAll('form');
            if (forms.length === 0 || target.querySelector('table')) { return; }
            var allLocked = Array.prototype.every.call(forms, function (f) { return f.hasAttribute('data-perm-locked'); });
            if (allLocked) { lockElement(trigger); }
        });
    }

    // Stop clicks / submits on locked controls before any other handler (confirm dialogs, modals, ...) sees them.
    document.addEventListener('click', function (e) {
        var el = e.target.closest('.perm-locked, .perm-soft-locked');
        if (el) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);
    document.addEventListener('submit', function (e) {
        if (e.target.closest && e.target.closest('[data-perm-locked]')) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', apply); } else { apply(); }
})();
</script>
