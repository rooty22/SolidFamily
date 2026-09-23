/**
 * Live Translate - Translate mode.
 *
 * Toggle "Live translate" in the dashboard header (or the floating "Translate" button on the site), hover any text
 * (highlighted), click it: a popup shows the text in every language and saves it where it is stored. The popup lives
 * in a shadow root so no site or dashboard stylesheet can change how it looks.
 *
 * The dashboard button also decides whether the site shows its floating button: closed = hidden, opened = shown
 * (shared by every tab of the browser through localStorage).
 */
(function () {
    'use strict';

    const config = window.LT_EDITOR;
    const runtime = window.LTRuntime;
    if (!config || !runtime) {
        return;
    }

    const strings = config.strings || {};
    const STORAGE_KEY = 'lt-active';
    // Shared by every tab of the browser: the dashboard button decides whether the site shows its Translate button.
    const BUTTON_KEY = 'lt-button';
    const ATTRIBUTE_TARGETS = [
        {selector: 'input[placeholder],textarea[placeholder]', attribute: 'placeholder'},
        {selector: 'input[type="submit"],input[type="button"],input[type="reset"]', attribute: 'value'},
        {selector: 'img[alt]', attribute: 'alt'}
    ];

    let active = false;
    let hoverTarget = null;
    let popupOpen = false;
    let frame = 0;
    let host, root, highlight, toastTimer, topButton;

    /* ------------------------------------------------------------------ helpers */

    function h(tag, attributes) {
        const element = document.createElement(tag);
        Object.keys(attributes || {}).forEach(function (key) {
            if (key === 'text') {
                element.textContent = attributes[key];
            } else if (key === 'class') {
                element.className = attributes[key];
            } else if (attributes[key] !== null && attributes[key] !== undefined) {
                element.setAttribute(key, attributes[key]);
            }
        });
        for (let i = 2; i < arguments.length; i++) {
            if (arguments[i]) {
                element.appendChild(typeof arguments[i] === 'string' ? document.createTextNode(arguments[i]) : arguments[i]);
            }
        }
        return element;
    }

    function hasLetters(text) {
        return /\p{L}/u.test(text);
    }

    /** Language a text without a known source is written in: the page language, unless the script says otherwise. */
    function guessLanguage(text) {
        const rtlText = /[֐-ࣿיִ-﷿ﹰ-﻿]/.test(text);
        const compatible = config.languages.filter(function (language) { return !!language.rtl === rtlText; });
        if (!compatible.length) {
            return config.lang;
        }
        const preferred = [config.lang, config.defaultLang].filter(function (code) {
            return compatible.some(function (language) { return language.code === code; });
        });
        return preferred[0] || compatible[0].code;
    }

    function pageDirection() {
        return document.documentElement.getAttribute('dir') || (config.rtl ? 'rtl' : 'ltr');
    }

    function insideUi(event) {
        const path = event.composedPath ? event.composedPath() : [];
        // The dashboard button (and anything marked data-lt-skip) keeps working as a button while the mode is on.
        return path.indexOf(host) !== -1 || (event.target && event.target.closest && !!event.target.closest('[data-lt-skip]'));
    }

    /* ------------------------------------------------------------------ finding the text under the pointer */

    function caretNode(x, y) {
        if (document.caretPositionFromPoint) {
            const position = document.caretPositionFromPoint(x, y);
            return position ? position.offsetNode : null;
        }
        if (document.caretRangeFromPoint) {
            const range = document.caretRangeFromPoint(x, y);
            return range ? range.startContainer : null;
        }
        return null;
    }

    function pointIn(rect, x, y) {
        return x >= rect.left - 2 && x <= rect.right + 2 && y >= rect.top - 2 && y <= rect.bottom + 2;
    }

    /** @return {{kind:string,text:string,rect:DOMRect,node?:Node,element?:Element,attribute?:string}|null} */
    function locate(x, y) {
        const element = document.elementFromPoint(x, y);
        if (!element || runtime.isSkipped(element)) {
            return null;
        }

        const node = caretNode(x, y);
        if (node && node.nodeType === 3 && hasLetters(node.nodeValue) && !runtime.isSkipped(node.parentElement)) {
            const range = document.createRange();
            range.selectNodeContents(node);
            const rects = Array.prototype.slice.call(range.getClientRects());
            if (rects.some(function (rect) { return pointIn(rect, x, y); })) {
                return {kind: 'text', text: node.nodeValue.trim(), rect: range.getBoundingClientRect(), node: node};
            }
        }

        for (let i = 0; i < ATTRIBUTE_TARGETS.length; i++) {
            const candidate = ATTRIBUTE_TARGETS[i];
            const owner = element.closest(candidate.selector);
            if (owner) {
                const value = candidate.attribute === 'value' ? owner.value : owner.getAttribute(candidate.attribute);
                if (value && hasLetters(value)) {
                    return {kind: 'attribute', text: value.trim(), rect: owner.getBoundingClientRect(), element: owner, attribute: candidate.attribute};
                }
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------ UI shell (shadow root) */

    const CSS = `
        :host { all: initial; }
        * { box-sizing: border-box; }
        .hl { position: fixed; z-index: 1; pointer-events: none; border: 2px solid #2563eb; background: rgba(37,99,235,.12); border-radius: 4px; display: none; }
        .hl span { position: absolute; top: -24px; inset-inline-start: -2px; background: #2563eb; color: #fff; font: 600 11px/1 system-ui, Tahoma, sans-serif; padding: 5px 8px; border-radius: 4px 4px 4px 0; white-space: nowrap; }
        .fab { position: fixed; z-index: 2; bottom: 18px; inset-inline-start: 18px; pointer-events: auto; border: 0; border-radius: 999px; padding: 11px 16px; font: 600 13px/1 system-ui, Tahoma, sans-serif; background: #1f2937; color: #fff; cursor: pointer; box-shadow: 0 6px 20px rgba(0,0,0,.25); }
        .fab.on { background: #2563eb; }
        .toast { position: fixed; z-index: 4; bottom: 28px; left: 50%; transform: translateX(-50%); background: #111827; color: #fff; font: 500 13px/1.5 system-ui, Tahoma, sans-serif; padding: 10px 16px; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.3); max-width: min(92vw, 520px); pointer-events: none; }
        .backdrop { position: fixed; inset: 0; z-index: 3; background: rgba(15,23,42,.45); display: flex; align-items: center; justify-content: center; padding: 16px; pointer-events: auto; }
        .dialog { width: min(560px, 100%); max-height: 92vh; display: flex; flex-direction: column; background: #fff; color: #111827; border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,.35); font: 400 14px/1.5 system-ui, Tahoma, sans-serif; overflow: hidden; }
        header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
        header h2 { margin: 0; font-size: 16px; font-weight: 700; }
        .x { border: 0; background: transparent; font-size: 22px; line-height: 1; cursor: pointer; color: #6b7280; padding: 2px 6px; border-radius: 6px; }
        .x:hover { background: #f3f4f6; color: #111827; }
        .body { padding: 16px 20px; overflow: auto; display: grid; gap: 14px; }
        .label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px; }
        .source { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; max-height: 96px; overflow: auto; white-space: pre-wrap; word-break: break-word; }
        .field label { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        textarea { width: 100%; min-height: 40px; resize: vertical; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 10px; font: 400 14px/1.5 system-ui, Tahoma, sans-serif; color: inherit; background: #fff; }
        textarea:focus { outline: 2px solid #2563eb; outline-offset: 0; border-color: #2563eb; }
        .where { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; display: grid; gap: 8px; }
        .where label.row { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; cursor: pointer; }
        .where .type { background: #eff6ff; color: #1d4ed8; border-radius: 999px; padding: 1px 8px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .where a { color: #2563eb; font-size: 12px; text-decoration: none; margin-inline-start: auto; white-space: nowrap; }
        .note { font-size: 13px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 9px 12px; }
        .error { font-size: 13px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 9px 12px; }
        footer { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-top: 1px solid #e5e7eb; background: #f9fafb; }
        footer .hint { margin-inline-end: auto; font-size: 12px; color: #6b7280; }
        button.btn { border: 1px solid #d1d5db; background: #fff; color: #111827; border-radius: 10px; padding: 9px 16px; font: 600 13px/1 system-ui, Tahoma, sans-serif; cursor: pointer; }
        button.btn.primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        button.btn:disabled { opacity: .6; cursor: default; }
        .loading { padding: 28px 20px; text-align: center; color: #6b7280; }
        @media (prefers-color-scheme: dark) {
            .dialog { background: #111827; color: #f3f4f6; }
            header, footer { border-color: #1f2937; }
            footer { background: #0b1220; }
            .source { background: #0b1220; border-color: #1f2937; }
            textarea { background: #0b1220; border-color: #374151; }
            .where { border-color: #1f2937; }
            .x:hover { background: #1f2937; color: #fff; }
            button.btn { background: #1f2937; border-color: #374151; color: #f3f4f6; }
            button.btn.primary { background: #2563eb; border-color: #2563eb; }
        }
    `;

    function buildShell() {
        host = h('div', {id: 'lt-host', 'data-lt-skip': ''});
        // The site's AI assistant sits at the end of the page, so the floating button takes the start side.
        // ':host { all: initial }' resets the direction, so it is set here (an inline style beats :host rules).
        host.style.cssText = 'position:fixed;inset:0;z-index:2147483646;pointer-events:none;direction:' + pageDirection() + ';';
        root = host.attachShadow({mode: 'open'});
        highlight = h('div', {class: 'hl'}, h('span', {text: strings.clickToTranslate || 'Translate'}));
        root.appendChild(h('style', {text: CSS}));
        root.appendChild(highlight);
        document.body.appendChild(host);

        mountToggle();
    }

    /**
     * Where the Translate button lives: the button in the dashboard header (printed by the layout), or a floating
     * button (the site, and a dashboard page without the header button).
     */
    function mountToggle() {
        const button = document.getElementById('lt-admin-toggle');
        if (button) {
            topButton = button;
            button.addEventListener('click', toggleFromButton);
            document.head.appendChild(h('style', {text: '#lt-admin-toggle.lt-on{background:#2563eb!important;border-color:#2563eb!important;color:#fff!important}'}));
            button.classList.toggle('lt-on', active);
            return;
        }

        const fab = h('button', {class: 'fab', type: 'button', text: strings.toggle || 'Translate'});
        fab.addEventListener('click', toggleFromButton);
        root.appendChild(fab);
        root.fab = fab;
        fab.classList.toggle('on', active);
        applyButtonVisibility();
    }

    /** Site only: the button is hidden ('0') from the dashboard, and shown again when the dashboard button is turned on. */
    function buttonEnabled() {
        try {
            return localStorage.getItem(BUTTON_KEY) !== '0';
        } catch (e) {
            return true;
        }
    }

    function saveButtonEnabled(on) {
        try {
            localStorage.setItem(BUTTON_KEY, on ? '1' : '0');
        } catch (e) { /* storage may be blocked */ }
    }

    function applyButtonVisibility() {
        if (config.isAdmin) {
            return;
        }
        const enabled = buttonEnabled();
        if (root && root.fab) {
            root.fab.style.display = enabled ? '' : 'none';
        }
        if (!enabled) {
            setActive(false);
        }
    }

    /** The dashboard button and the floating button both end up here. */
    function toggleFromButton() {
        const on = !active;
        if (config.isAdmin) {
            saveButtonEnabled(on);
        }
        setActive(on);
    }

    function toast(message, duration) {
        if (toastTimer) {
            clearTimeout(toastTimer);
        }
        const previous = root.querySelector('.toast');
        if (previous) {
            previous.remove();
        }
        const element = h('div', {class: 'toast', role: 'status', text: message});
        root.appendChild(element);
        toastTimer = setTimeout(function () { element.remove(); }, duration || 3200);
    }

    function showHighlight(target) {
        if (!target) {
            highlight.style.display = 'none';
            return;
        }
        const rect = target.rect;
        highlight.style.cssText = 'display:block;left:' + (rect.left - 3) + 'px;top:' + (rect.top - 2) + 'px;width:' + (rect.width + 6) + 'px;height:' + (rect.height + 4) + 'px;';
    }

    /* ------------------------------------------------------------------ Translate mode */

    function onMove(event) {
        if (popupOpen || insideUi(event)) {
            return;
        }
        const x = event.clientX;
        const y = event.clientY;
        if (frame) {
            return;
        }
        frame = requestAnimationFrame(function () {
            frame = 0;
            hoverTarget = locate(x, y);
            showHighlight(hoverTarget);
        });
    }

    function onClick(event) {
        if (popupOpen || insideUi(event) || event.ctrlKey || event.metaKey || event.button !== 0) {
            return;
        }
        const target = locate(event.clientX, event.clientY);
        if (!target) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        openPopup(target);
    }

    function swallow(event) {
        // Keep buttons/links from starting their own action while a text is being picked.
        if (!popupOpen && !insideUi(event) && !event.ctrlKey && !event.metaKey && locate(event.clientX, event.clientY)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }

    function onKey(event) {
        if (event.key === 'Escape' && !popupOpen) {
            setActive(false);
        }
    }

    function setActive(on) {
        if (on === active) {
            return;
        }
        active = on;
        document.documentElement.classList.toggle('lt-active', on);
        try {
            sessionStorage.setItem(STORAGE_KEY, on ? '1' : '0');
        } catch (e) { /* storage may be blocked */ }

        const method = on ? 'addEventListener' : 'removeEventListener';
        document[method]('mousemove', onMove, {capture: true, passive: true});
        document[method]('click', onClick, true);
        document[method]('mousedown', swallow, true);
        document[method]('mouseup', swallow, true);
        document[method]('keydown', onKey, true);
        window[method]('scroll', hideHighlight, true);

        if (root && root.fab) {
            root.fab.classList.toggle('on', on);
        }
        if (topButton) {
            topButton.classList.toggle('lt-on', on);
        }
        if (!on) {
            hoverTarget = null;
            showHighlight(null);
        }
        toast(on ? strings.toggleOn : strings.toggleOff, on ? 4200 : 2000);
    }

    function hideHighlight() {
        hoverTarget = null;
        showHighlight(null);
    }

    /* ------------------------------------------------------------------ popup */

    function closePopup() {
        const backdrop = root.querySelector('.backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        popupOpen = false;
    }

    function post(action, data) {
        const body = new URLSearchParams();
        body.set('lang', config.lang);
        body.set('path', location.pathname);
        Object.keys(data).forEach(function (key) {
            const value = data[key];
            if (Array.isArray(value)) {
                value.forEach(function (item) { body.append(key + '[]', item); });
            } else if (value && typeof value === 'object') {
                Object.keys(value).forEach(function (code) { body.set(key + '[' + code + ']', value[code]); });
            } else {
                body.set(key, value);
            }
        });
        return fetch(config.endpoint + '/' + action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-CSRF-Token': config.csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
            body: body
        }).then(function (response) { return response.json(); });
    }

    function defaultChecked(candidates) {
        const own = candidates.filter(function (candidate) { return candidate.current; });
        if (own.length) {
            return own;
        }
        // Several unrelated places can share a text (a menu label "Services" and a page called "Services"):
        // only a single, unambiguous place is preselected, otherwise the user chooses.
        return candidates.length === 1 ? candidates : [];
    }

    function openPopup(target) {
        popupOpen = true;
        hideHighlight();

        const dialog = h('div', {class: 'dialog', role: 'dialog', 'aria-modal': 'true', dir: pageDirection()},
            h('header', {}, h('h2', {text: strings.title}), h('button', {class: 'x', type: 'button', 'aria-label': strings.cancel, text: '×'})),
            h('div', {class: 'loading', text: strings.loading})
        );
        const backdrop = h('div', {class: 'backdrop'}, dialog);
        backdrop.addEventListener('mousedown', function (event) {
            if (event.target === backdrop) {
                closePopup();
            }
        });
        dialog.querySelector('.x').addEventListener('click', closePopup);
        root.appendChild(backdrop);

        post('resolve', {text: target.text})
            .then(function (response) {
                if (!response.success) {
                    throw new Error('resolve');
                }
                renderForm(dialog, target, response.data);
            })
            .catch(function () {
                closePopup();
                toast(strings.error, 4000);
            });
    }

    function renderForm(dialog, target, data) {
        const candidates = data.candidates || [];
        const known = candidates[0] ? candidates[0].values : (data.dictionary ? data.dictionary.values : null);
        const preselected = defaultChecked(candidates).map(function (candidate) { return candidate.ref; });
        const fields = {};

        const fieldsBox = h('div', {class: 'body-fields', style: 'display:grid;gap:12px'});
        config.languages.forEach(function (language) {
            const input = h('textarea', {rows: 1, dir: language.rtl ? 'rtl' : 'ltr', lang: language.code, 'data-lang': language.code, placeholder: strings.empty || ''});
            input.value = known && known[language.code] !== undefined ? known[language.code] : (language.code === guessLanguage(target.text) ? target.text : '');
            fields[language.code] = input;
            fieldsBox.appendChild(h('div', {class: 'field'},
                h('label', {}, language.flag ? h('span', {text: language.flag}) : null, language.name + ' (' + language.code + ')'),
                input));
        });

        const checks = {};
        let where = null;
        let dictionaryBox = null;
        if (candidates.length) {
            const all = candidates.length > 1 ? h('a', {href: '#', text: strings.selectAll}) : null;
            if (all) {
                all.addEventListener('click', function (event) {
                    event.preventDefault();
                    Object.keys(checks).forEach(function (ref) { checks[ref].checked = true; });
                });
            }
            where = h('div', {class: 'where'}, h('span', {class: 'label', text: strings.where + ' (' + candidates.length + ')'}), all);
            candidates.forEach(function (candidate) {
                const checkbox = h('input', {type: 'checkbox'});
                checkbox.checked = preselected.indexOf(candidate.ref) !== -1;
                checks[candidate.ref] = checkbox;
                const link = candidate.edit_url ? h('a', {href: candidate.edit_url, target: '_blank', rel: 'noopener', text: strings.openEditor}) : null;
                where.appendChild(h('label', {class: 'row'}, checkbox,
                    h('span', {class: 'type', text: strings['type_' + candidate.kind] || candidate.kind}),
                    h('span', {text: candidate.label}), link));
            });
            dictionaryBox = h('input', {type: 'checkbox'});
            where.appendChild(h('label', {class: 'row'}, dictionaryBox, h('span', {text: strings.alsoDictionary})));
        }

        const error = h('div', {class: 'error', hidden: ''});
        const save = h('button', {class: 'btn primary', type: 'button', text: strings.save});
        const cancel = h('button', {class: 'btn', type: 'button', text: strings.cancel});

        while (dialog.children.length > 1) {
            dialog.removeChild(dialog.lastChild);
        }
        dialog.appendChild(h('div', {class: 'body'},
            h('div', {}, h('span', {class: 'label', text: strings.original}), h('div', {class: 'source', dir: 'auto', text: target.text})),
            h('div', {}, h('span', {class: 'label', text: strings.languages}), fieldsBox),
            where,
            candidates.length ? null : h('div', {class: 'note', text: strings.noSource}),
            error));
        dialog.appendChild(h('footer', {}, h('span', {class: 'hint', text: strings.hint}), cancel, save));

        function showError(message) {
            error.textContent = message;
            error.hidden = false;
        }

        function submit() {
            const values = {};
            Object.keys(fields).forEach(function (code) { values[code] = fields[code].value; });
            const refs = Object.keys(checks).filter(function (ref) { return checks[ref].checked; });
            const dictionary = !candidates.length || (dictionaryBox && dictionaryBox.checked);
            const filled = Object.keys(values).some(function (code) { return values[code].trim() !== ''; });
            if (!filled || (!refs.length && !dictionary)) {
                showError(strings.nothingToSave);
                return;
            }

            error.hidden = true;
            save.disabled = true;
            save.textContent = strings.saving;
            post('save', {text: target.text, values: values, targets: refs, dictionary: dictionary ? '1' : '0'})
                .then(function (response) {
                    if (!response.success) {
                        const failed = ((response.data && response.data.results) || []).filter(function (r) { return !r.ok; });
                        throw new Error(failed.length ? failed[0].message : strings.error);
                    }
                    const result = response.data;
                    if (result.replacement) {
                        runtime.replace(target.text, result.replacement);
                    }
                    if (result.runtime) {
                        runtime.add(result.runtime);
                    }
                    const failed = result.results.filter(function (r) { return !r.ok; });
                    closePopup();
                    toast(failed.length ? strings.saved + ' ' + failed[0].message : strings.saved, failed.length ? 6000 : 2600);
                })
                .catch(function (problem) {
                    save.disabled = false;
                    save.textContent = strings.save;
                    showError(problem && problem.message && problem.message !== 'Failed to fetch' ? problem.message : strings.error);
                });
        }

        save.addEventListener('click', submit);
        cancel.addEventListener('click', closePopup);
        dialog.querySelector('.x').addEventListener('click', closePopup);
        dialog.addEventListener('keydown', function (event) {
            event.stopPropagation();
            if (event.key === 'Escape') {
                closePopup();
            } else if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                submit();
            }
        });

        const firstEmpty = config.languages.map(function (language) { return fields[language.code]; }).filter(function (input) { return input.value.trim() === ''; })[0];
        (firstEmpty || fields[config.languages[0].code]).focus();
    }

    /* ------------------------------------------------------------------ start */

    function init() {
        buildShell();

        // Closed from the dashboard in another tab: the site drops its button (and the mode) right away.
        window.addEventListener('storage', function (event) {
            if (event.key === BUTTON_KEY) {
                applyButtonVisibility();
            }
        });
        applyButtonVisibility();

        try {
            if (sessionStorage.getItem(STORAGE_KEY) === '1' && (config.isAdmin || buttonEnabled())) {
                if (config.isAdmin) {
                    saveButtonEnabled(true);
                }
                setActive(true);
            }
        } catch (e) { /* storage may be blocked */ }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
