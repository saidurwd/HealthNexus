/*
 * Every dropdown (<select>) in HealthNexus becomes searchable: the user types to find the item. One script, loaded by every layout, so no screen needs its own code.
 *
 *   - Opt out of one dropdown with  data-plain   (or put data-plain on a wrapper to opt out all inside it).
 *   - A first empty option (value "") is the placeholder; a dropdown that is not required also gets a clear (×) button.
 *   - Options that other scripts add or remove later, and disabled/enabled, are picked up by themselves.
 *   - The original <select> stays in the page and stays in step, so forms, validation, `change` handlers and
 *     `select.value = …` keep working.
 *   - A required dropdown left empty stops the form and shows the usual red outline on the control.
 *   - Many dropdowns on one page (a long table) are prepared a few at a time so the page stays responsive.
 */
(function () {
    'use strict';

    if (typeof window.TomSelect === 'undefined' || window.__hnSearchableSelect) { return; }
    window.__hnSearchableSelect = true;

    var WORDS = {
        en: { none: 'No matches found', search: 'Type to search' },
        bn: { none: 'কোনো মিল পাওয়া যায়নি', search: 'খুঁজতে লিখুন' }
    };
    var lang = ((document.documentElement.getAttribute('lang') || 'en').slice(0, 2).toLowerCase());
    var words = WORDS[lang] || WORDS.en;
    var CHUNK = 12;          // dropdowns prepared per turn when there are many
    var MAX_OPTIONS = 500;   // rendered at once; typing finds the rest

    function plain(el) {
        return el.hasAttribute('data-plain') || el.closest('[data-plain]') !== null;
    }

    function eligible(el) {
        if (!(el instanceof HTMLSelectElement) || el.tomselect) { return false; }
        if (plain(el)) { return false; }
        if (el.size > 1) { return false; }                          // a list box, not a dropdown
        if (el.closest('.ts-wrapper, template')) { return false; }
        if (el.hasAttribute('v-model') || el.hasAttribute('x-model') || el.hasAttribute('wire:model')) { return false; }   // owned by a framework
        return true;
    }

    function emptyOption(el) {
        for (var i = 0; i < el.options.length; i++) { if (el.options[i].value === '') { return el.options[i]; } }
        return null;
    }

    function enhance(el) {
        if (!eligible(el)) { return; }
        var blank = emptyOption(el);
        var required = el.required;
        var plugins = [];
        if (el.multiple) { plugins.push('remove_button'); }
        if (el.multiple || (blank && !required)) { plugins.push('clear_button'); }

        var settings = {
                allowEmptyOption: true,
                maxItems: el.multiple ? null : 1,
                maxOptions: MAX_OPTIONS,
                create: false,
                openOnFocus: true,
                refreshThrottle: 0,    // filter the list as each letter is typed, not 300 ms after the last one
                placeholder: el.getAttribute('data-placeholder') || (blank ? blank.text : '') || el.getAttribute('placeholder') || words.search,
                plugins: plugins,
                dropdownParent: 'body',
                sortField: [{ field: '$order' }],
                // the empty "Choose…" option is a prompt, not an answer: it does not turn up while someone is searching
                score: function (search) {
                    var base = this.getScoreFunction(search);
                    return function (item) { return item.value === '' ? 0 : base(item); };
                },
                render: { no_results: function () { return '<div class="no-results">' + words.none + '</div>'; } }
            };
        var remote = el.getAttribute('data-remote');
        if (remote) { useRemote(settings, el, remote); }
        var ts;
        try {
            ts = new window.TomSelect(el, settings);
        } catch (e) {
            return;   // never break the page because of a dropdown
        }
        dependOn(el, ts);
        if (required) { el.setAttribute('data-ts-required', '1'); }
        if (!el.multiple) { follow(el, ts); }
        watch(el, ts);
    }

    // A list too long to hold in the page (patients, items): data-remote="/url" asks the server as the user types. The server answers [{value, text}], twenty or so at a time, and decides what matches.
    function useRemote(settings, el, url) {
        var min = parseInt(el.getAttribute('data-remote-min') || '1', 10);
        settings.valueField = 'value';
        settings.labelField = 'text';
        settings.searchField = ['text'];   // the score below accepts everything the server returned
        settings.hideSelected = true;        // the current choice does not crowd the results: Enter takes the first real match
        settings.preload = 'focus';          // the first few are shown as soon as it is opened
        settings.loadThrottle = 250;
        settings.shouldLoad = function (q) { return q.length === 0 || q.length >= min; };
        settings.score = function () { return function () { return 1; }; };   // the server already filtered
        // data-remote-depends="[name=patient_id]": the list narrows to what the other dropdown holds (a patient's visits), and waits until that one is chosen.
        var parent = el.getAttribute('data-remote-depends') ? document.querySelector(el.getAttribute('data-remote-depends')) : null;
        var param = el.getAttribute('data-remote-param') || (parent && parent.name) || 'patient_id';
        settings.load = function (query, callback) {
            var self = this;
            var narrow = parent && parent.value ? '&' + encodeURIComponent(param) + '=' + encodeURIComponent(parent.value) : '';
            if (parent && !parent.value) { callback(); return; }
            fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(query) + narrow, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (rows) { self.clearOptions(); callback(rows); })
                .catch(function () { callback(); });
        };
    }

    // A dropdown that depends on another (a visit on its patient): when the other changes, what was chosen no longer applies; it stays shut until the other has a value.
    function dependOn(el, ts) {
        var sel = el.getAttribute('data-remote-depends');
        var parent = sel ? document.querySelector(sel) : null;
        if (!parent) { return; }
        var sync = function (reset) {
            if (reset) { ts.clear(true); ts.clearOptions(); ts.loadedSearches = {}; }
            parent.value ? el.removeAttribute('data-ts-blocked') : el.setAttribute('data-ts-blocked', '1');
            parent.value ? ts.unlock() : ts.lock();
        };
        parent.addEventListener('change', function () { sync(true); });
        sync(false);
    }

    // Older screens set a dropdown with `select.value = 'x'`; show that in the searchable control too.
    function follow(el, ts) {
        var d = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        Object.defineProperty(el, 'value', {
            configurable: true,
            get: function () { return d.get.call(el); },
            set: function (v) {
                d.set.call(el, v);
                try { ts.setValue(d.get.call(el), true); } catch (e) { /* the page's own value is already set */ }
            }
        });
    }

    // What the dropdown offers, ignoring order and which one is selected: Tom Select itself rewrites the hidden <select> when a choice is made, and that must not look like a script changing the list.
    function offered(el) {
        return Array.prototype.map.call(el.options, function (o) { return o.value + '\u0000' + o.text; }).sort().join('\u0001');
    }

    // Other scripts fill, empty, enable and disable dropdowns; keep the searchable one in step.
    function watch(el, ts) {
        var known = offered(el);
        var queued = false;
        var mo = new MutationObserver(function () {
            if (queued) { return; }
            queued = true;
            window.setTimeout(function () {
                queued = false;
                var now = offered(el);
                if (now !== known && !el.hasAttribute('data-remote')) {   // a remote dropdown manages its own options
                    known = now;
                    var value = el.multiple ? Array.prototype.map.call(el.selectedOptions, function (o) { return o.value; }) : el.value;
                    ts.clear(true);
                    ts.clearOptions();
                    ts.sync();
                    ts.setValue(value, true);
                    known = offered(el);
                }
                el.disabled ? ts.disable() : ts.enable();
            }, 0);
        });
        mo.observe(el, { childList: true, attributes: true, attributeFilter: ['disabled'] });
    }

    function emptyValue(el) {
        if (el.multiple) { return el.selectedOptions.length === 0; }
        return el.value === '';
    }

    // A required dropdown that is still empty stops the form, like any required field.
    document.addEventListener('submit', function (e) {
        var form = e.target, first = null;
        if (!form || !form.querySelectorAll) { return; }
        form.querySelectorAll('select[data-ts-required]').forEach(function (s) {
            var wrap = s.tomselect && s.tomselect.wrapper;
            if (!s.disabled && emptyValue(s)) {
                wrap && wrap.classList.add('is-invalid');
                first = first || s;
            } else {
                wrap && wrap.classList.remove('is-invalid');
            }
        });
        if (first) {
            e.preventDefault();
            e.stopPropagation();
            first.tomselect.focus();
        }
    }, true);

    // A script that changes the dropdown and then announces it with a `change` event is followed too.
    document.addEventListener('change', function (e) {
        var s = e.target;
        if (!s || !s.tomselect || s.multiple) { return; }
        if (String(s.tomselect.getValue()) !== String(s.value)) { s.tomselect.setValue(s.value, true); }
    }, true);

    document.addEventListener('change', function (e) {
        var s = e.target;
        if (s && s.tomselect && s.hasAttribute && s.hasAttribute('data-ts-required') && !emptyValue(s)) { s.tomselect.wrapper.classList.remove('is-invalid'); }
    }, true);

    function enhanceAll(root) {
        var found = [];
        if (root instanceof HTMLSelectElement) { found.push(root); }
        else if (root.querySelectorAll) { found = Array.prototype.slice.call(root.querySelectorAll('select')); }
        found = found.filter(eligible);
        if (found.length <= CHUNK) { found.forEach(enhance); return; }
        (function next() {
            found.splice(0, CHUNK).forEach(enhance);
            if (found.length) { window.setTimeout(next, 16); }
        })();
    }

    function start() {
        enhanceAll(document);
        // dropdowns added to the page later (a modal, a row added to a table, a section loaded by a script)
        new MutationObserver(function (list) {
            list.forEach(function (m) {
                m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { enhanceAll(n); } });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
