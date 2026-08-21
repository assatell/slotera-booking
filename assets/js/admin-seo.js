(function () {
    'use strict';

    function charLength(value) {
        if (!value) return 0;
        return Array.from(String(value)).length;
    }

    function updateSeoMeter(field) {
        if (!field || !field.id) return;
        var min = parseInt(field.getAttribute('data-sltr-seo-min') || '0', 10);
        var max = parseInt(field.getAttribute('data-sltr-seo-max') || '0', 10);
        var len = charLength(field.value || '');
        var meter = document.querySelector('[data-sltr-seo-meter-for="' + field.id + '"]');
        var text = document.querySelector('[data-sltr-seo-text-for="' + field.id + '"]');
        var bar = meter ? meter.querySelector('.sltr-seo-meter-bar') : null;
        var pct = max > 0 ? Math.min(100, Math.round((len / max) * 100)) : 0;
        var state = 'empty';
        if (len > 0 && len < min) state = 'short';
        if (len >= min && len <= max) state = 'good';
        if (max > 0 && len > max) state = 'long';
        if (bar) {
            bar.style.width = pct + '%';
        }
        if (meter) {
            meter.setAttribute('data-sltr-seo-state', state);
        }
        if (text) {
            var remaining = Math.max(0, max - len);
            if (state === 'good') {
                text.textContent = len + '/' + max + ' — optimal';
            } else if (state === 'long') {
                text.textContent = len + '/' + max + ' — ' + (len - max) + ' over recommended length';
            } else if (state === 'short') {
                text.textContent = len + '/' + max + ' — add about ' + (min - len) + ' more characters';
            } else {
                text.textContent = '0/' + max + ' — recommended ' + min + '–' + max + ' characters';
            }
            text.setAttribute('data-sltr-seo-state', state);
            text.setAttribute('aria-live', 'polite');
        }
    }

    function initSeoMeters() {
        document.querySelectorAll('.sltr-seo-meter-field').forEach(function (field) {
            updateSeoMeter(field);
            field.addEventListener('input', function () { updateSeoMeter(field); });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSeoMeters);
    } else {
        initSeoMeters();
    }
}());

/* v1.0.334 SEO preview, slug helper and OG image uploader */
(function () {
    'use strict';

    function one(selector, root) { return (root || document).querySelector(selector); }
    function val(selector, root) { var el = one(selector, root); return el ? String(el.value || '').trim() : ''; }
    function textLenTrim(value, max) {
        value = String(value || '').replace(/\s+/g, ' ').trim();
        if (max && Array.from(value).length > max) return Array.from(value).slice(0, max - 1).join('') + '…';
        return value;
    }
    function slugify(value) {
        value = String(value || '').toLowerCase().trim();
        if (!value) return '';
        if (value.normalize) value = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
        try {
            return value.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
        } catch (e) {
            return value.replace(/[^a-z0-9\u0370-\u03FF\u0400-\u04FF]+/g, '-').replace(/^-+|-+$/g, '');
        }
    }
    function composedTitle(title, site, position) {
        title = title || site || '';
        if (!site || title === site) return title;
        return position === 'left' ? site + ' | ' + title : title + ' | ' + site;
    }
    function updateSlugPreview(input) {
        if (!input) return;
        var slug = slugify(input.value || val(input.getAttribute('data-sltr-slug-source')) || '');
        document.querySelectorAll('.sltr-slug-preview[data-source="#' + input.id + '"]').forEach(function (preview) {
            preview.textContent = slug || 'auto-generated';
        });
        document.querySelectorAll('[data-sltr-seo-preview]').forEach(updateSeoPreview);
    }
    function updateSeoPreview(box) {
        if (!box || !box.getAttribute) return;
        var title = val(box.getAttribute('data-title-source')) || val(box.getAttribute('data-fallback-title-source')) || 'Untitled';
        var desc = val(box.getAttribute('data-description-source')) || val(box.getAttribute('data-fallback-description-source')) || '';
        var site = box.getAttribute('data-site-title') || '';
        var position = val(box.getAttribute('data-position-source')) || 'right';
        var slugField = one('.sltr-slug-field');
        var slug = slugField ? slugify(slugField.value || val(slugField.getAttribute('data-sltr-slug-source'))) : '';
        var titleEl = one('[data-sltr-preview-title]', box);
        var descEl = one('[data-sltr-preview-description]', box);
        var slugEl = one('[data-sltr-preview-slug]', box);
        if (titleEl) titleEl.textContent = textLenTrim(composedTitle(title, site, position), 70);
        if (descEl) descEl.textContent = textLenTrim(desc || 'Meta description fallback will be generated from the saved content.', 160);
        if (slugEl) slugEl.textContent = slug ? slug : 'auto-generated';
    }
    function renderOgPreview(input) {
        if (!input || !input.id) return;
        document.querySelectorAll('.sltr-seo-og-image-preview[data-source="#' + input.id + '"]').forEach(function (wrap) {
            var url = String(input.value || '').trim();
            wrap.innerHTML = url ? '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" />' : '';
        });
    }
    function initSeoUi() {
        document.querySelectorAll('.sltr-slug-field').forEach(function (input) {
            updateSlugPreview(input);
            input.addEventListener('input', function () { updateSlugPreview(input); });
            var source = one(input.getAttribute('data-sltr-slug-source') || '');
            if (source && !input.value) source.addEventListener('input', function () { updateSlugPreview(input); });
        });
        document.addEventListener('click', function (event) {
            var gen = event.target.closest('.sltr-generate-slug');
            if (gen) {
                var target = one(gen.getAttribute('data-target') || '');
                var source = one(gen.getAttribute('data-source') || '');
                if (target && source) { target.value = slugify(source.value); target.dispatchEvent(new Event('input', { bubbles: true })); }
                return;
            }
            var clear = event.target.closest('.sltr-seo-og-image-clear');
            if (clear) {
                var clearTarget = one(clear.getAttribute('data-target') || '');
                if (clearTarget) { clearTarget.value = ''; renderOgPreview(clearTarget); }
                return;
            }
            var upload = event.target.closest('.sltr-seo-og-image-upload');
            if (upload) {
                var input = one(upload.getAttribute('data-target') || '');
                if (!input || typeof wp === 'undefined' || !wp.media) return;
                var frame = wp.media({ title: 'Select OpenGraph image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
                frame.on('select', function () {
                    var item = frame.state().get('selection').first().toJSON();
                    input.value = item.url || '';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    renderOgPreview(input);
                });
                frame.open();
            }
        });
        document.querySelectorAll('.sltr-seo-og-image-field').forEach(function (input) {
            renderOgPreview(input);
            input.addEventListener('input', function () { renderOgPreview(input); });
        });
        document.querySelectorAll('[data-sltr-seo-preview]').forEach(function (box) {
            updateSeoPreview(box);
            ['data-title-source','data-fallback-title-source','data-description-source','data-fallback-description-source','data-position-source'].forEach(function (attr) {
                var el = one(box.getAttribute(attr) || '');
                if (el) el.addEventListener('input', function () { updateSeoPreview(box); });
                if (el) el.addEventListener('change', function () { updateSeoPreview(box); });
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSeoUi);
    else initSeoUi();
}());


/* v1.0.339 lightweight SEO hints */
(function () {
    'use strict';
    function one(selector, root) { return selector ? (root || document).querySelector(selector) : null; }
    function value(selector, root) { var el = one(selector, root); return el ? String(el.value || '').trim() : ''; }
    function length(value) { return Array.from(String(value || '').replace(/\s+/g, ' ').trim()).length; }
    function words(value) { value = String(value || '').replace(/\s+/g, ' ').trim(); return value ? value.split(' ').length : 0; }
    function addHint(list, level, text) {
        var li = document.createElement('li');
        li.setAttribute('data-sltr-hint-level', level);
        li.textContent = text;
        list.appendChild(li);
    }
    function longestSentenceWords(text) {
        text = String(text || '').replace(/\s+/g, ' ').trim();
        if (!text) return 0;
        return text.split(/[.!?]+/).reduce(function (max, sentence) {
            return Math.max(max, words(sentence));
        }, 0);
    }
    function looksAllCaps(text) {
        text = String(text || '').replace(/[^A-Za-zА-Яа-яЁёÕÄÖÜŠŽõäöüšž]/g, '');
        return text.length >= 12 && text === text.toUpperCase() && text !== text.toLowerCase();
    }
    function updateHints(box) {
        var list = one('[data-sltr-seo-hints-list]', box);
        if (!list) return;
        list.innerHTML = '';
        var seoTitle = value(box.getAttribute('data-title-source'));
        var fallbackTitle = value(box.getAttribute('data-fallback-title-source'));
        var desc = value(box.getAttribute('data-description-source'));
        var fallbackDesc = value(box.getAttribute('data-fallback-description-source'));
        var ogTitle = value(box.getAttribute('data-og-title-source'));
        var ogDesc = value(box.getAttribute('data-og-description-source'));
        var og = value(box.getAttribute('data-og-image-source'));
        var canonical = value(box.getAttribute('data-canonical-source'));
        var robots = value(box.getAttribute('data-robots-source'));
        var slug = value(box.getAttribute('data-slug-source'));
        var effectiveTitle = seoTitle || fallbackTitle;
        var effectiveDesc = desc || fallbackDesc;
        var titleLen = length(effectiveTitle);
        var descLen = length(effectiveDesc);
        var descWords = words(effectiveDesc);

        if (!seoTitle && fallbackTitle) addHint(list, 'warning', 'Missing SEO title: Slotera will use the automatic fallback title. Add a custom title if this page targets search traffic.');
        else if (!seoTitle && !fallbackTitle) addHint(list, 'warning', 'Missing SEO title: add a clear page title.');
        else if (titleLen > 60) addHint(list, 'warning', 'SEO title is longer than the recommended 60 characters.');
        else if (titleLen > 0 && titleLen < 40) addHint(list, 'warning', 'SEO title is short. A more descriptive title may perform better.');
        else addHint(list, 'ok', 'SEO title length looks good.');

        if (!desc && fallbackDesc) addHint(list, 'warning', 'Missing meta description: Slotera will use a fallback from the page description. A custom search summary is usually better.');
        else if (!desc && !fallbackDesc) addHint(list, 'warning', 'Missing meta description: add a short summary for search results.');
        else if (descLen > 160) addHint(list, 'warning', 'Meta description is longer than the recommended 160 characters.');
        else if (descLen > 0 && descLen < 120) addHint(list, 'warning', 'Meta description is short. Aim for a more complete summary.');
        else addHint(list, 'ok', 'Meta description length looks good.');

        if (!ogTitle) addHint(list, 'warning', 'Missing OpenGraph title: social previews will fall back to the SEO title.');
        if (!ogDesc) addHint(list, 'warning', 'Missing OpenGraph description: social previews will fall back to the meta description.');
        if (!og) addHint(list, 'warning', 'Missing OpenGraph image: add one for better social sharing previews.');
        else addHint(list, 'ok', 'OpenGraph image is set.');

        if (!canonical) addHint(list, 'warning', 'Canonical URL is empty; Slotera will generate it automatically.');
        else addHint(list, 'ok', 'Canonical URL is set manually.');

        if (!slug) addHint(list, 'warning', 'Slug is missing. Generate or enter a readable URL slug.');
        else if (length(slug) > 75) addHint(list, 'warning', 'Slug is quite long. A shorter URL is easier to read and share.');
        else addHint(list, 'ok', 'Slug length looks good.');

        if (robots && robots.indexOf('noindex') !== -1) addHint(list, 'warning', 'Robots is set to noindex. Search engines may not index this page.');

        if (descWords > 0 && descWords < 18) addHint(list, 'warning', 'Readability: description is very short. Add one or two natural sentences.');
        if (longestSentenceWords(effectiveDesc) > 28) addHint(list, 'warning', 'Readability: one sentence is quite long. Consider splitting it into shorter sentences.');
        if (/!!!|\?\?\?|\.\.\./.test(effectiveDesc)) addHint(list, 'warning', 'Readability: avoid repeated punctuation in SEO descriptions.');
        if (looksAllCaps(effectiveTitle) || looksAllCaps(effectiveDesc)) addHint(list, 'warning', 'Readability: avoid all-caps text in SEO titles or descriptions.');
        if (effectiveDesc && !/[.!?…]$/.test(effectiveDesc)) addHint(list, 'info', 'Readability: consider ending the meta description with a complete sentence.');

        if (list.children.length === 0) addHint(list, 'ok', 'No SEO hints right now.');
    }
    function initHints() {
        document.querySelectorAll('[data-sltr-seo-hints]').forEach(function (box) {
            updateHints(box);
            ['data-title-source','data-fallback-title-source','data-description-source','data-fallback-description-source','data-og-title-source','data-og-description-source','data-og-image-source','data-canonical-source','data-robots-source','data-slug-source'].forEach(function (attr) {
                var el = one(box.getAttribute(attr));
                if (el) {
                    el.addEventListener('input', function () { updateHints(box); });
                    el.addEventListener('change', function () { updateHints(box); });
                }
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initHints);
    else initHints();
}());

/* v1.0.860 Individual SEO select-driven editors */
(function () {
    'use strict';

    function activate(select, value) {
        var groupName = select.getAttribute('data-panel-group');
        var group = document.querySelector('.sltr-seo-editor-group[data-panel-group="' + groupName + '"]');
        if (!group) return;
        group.querySelectorAll('.sltr-seo-item-editor').forEach(function (editor) {
            var active = value && editor.id === value;
            editor.classList.toggle('is-active', !!active);
            if (editor.tagName.toLowerCase() === 'details') editor.open = !!active;
        });
        group.classList.toggle('is-active', !!value && !!group.querySelector('.sltr-seo-item-editor.is-active'));
    }

    function initIndividualSeoSelectors() {
        var focus = new URLSearchParams(window.location.search).get('sltr_focus') || '';
        document.querySelectorAll('.sltr-seo-editor-select').forEach(function (select) {
            if (focus && Array.from(select.options).some(function (option) { return option.value === focus; })) {
                select.value = focus;
            }
            activate(select, select.value);
            select.addEventListener('change', function () { activate(select, select.value); });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initIndividualSeoSelectors);
    else initIndividualSeoSelectors();
}());
