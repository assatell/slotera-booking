(function () {
    'use strict';

    var workspace = document.querySelector('[data-sltr-translation-workspace]');
    var form = document.querySelector('[data-sltr-translation-form]');
    if (!workspace || !form) {
        return;
    }

    var rows = Array.prototype.slice.call(form.querySelectorAll('[data-sltr-translation-row]'));
    var fields = Array.prototype.slice.call(form.querySelectorAll('[data-sltr-translation-key]'));
    var filters = Array.prototype.slice.call(workspace.querySelectorAll('[data-sltr-filter]'));
    var visibleCount = workspace.querySelector('[data-sltr-visible-count]');
    var unsaved = workspace.querySelector('[data-sltr-unsaved]');
    var unsavedButton = workspace.querySelector('[data-sltr-filter="unsaved"]');
    var saveState = form.querySelector('[data-sltr-save-state]');
    var filterInput = form.querySelector('[data-sltr-workspace-filter]');
    var activeFilter = workspace.dataset.initialFilter || 'all';
    var initial = new Map();
    var isSubmitting = false;

    fields.forEach(function (field) {
        initial.set(field, field.value);
    });

    function rowField(row) {
        return row.querySelector('[data-sltr-translation-key]');
    }

    function isModified(row) {
        var field = rowField(row);
        return field ? field.value !== initial.get(field) : false;
    }

    function refreshState() {
        var modified = 0;
        rows.forEach(function (row) {
            var field = rowField(row);
            var missing = !field || field.value.trim() === '';
            row.dataset.missing = missing ? '1' : '0';
            row.classList.toggle('is-modified', isModified(row));
            if (isModified(row)) {
                modified++;
            }
        });

        unsavedButton.textContent = 'Unsaved (' + modified + ')';
        unsavedButton.disabled = modified === 0;
        unsaved.hidden = modified === 0;
        if (saveState) {
            saveState.textContent = modified ? modified + ' unsaved change' + (modified === 1 ? '' : 's') : '';
        }
        applyFilter(activeFilter);
    }

    function matches(row, filter) {
        if (filter === 'missing') return row.dataset.missing === '1';
        if (filter === 'translated') return row.dataset.missing !== '1';
        if (filter === 'duplicates') return row.dataset.duplicate === '1';
        if (filter === 'quality') return row.dataset.quality === '1';
        if (filter === 'overrides') return row.dataset.override === '1';
        if (filter === 'unsaved') return isModified(row);
        return true;
    }

    function applyFilter(filter) {
        activeFilter = filter;
        if (filterInput) filterInput.value = filter;
        var count = 0;
        rows.forEach(function (row) {
            var show = matches(row, filter);
            row.hidden = !show;
            if (show) count++;
        });
        filters.forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.sltrFilter === filter);
        });
        visibleCount.textContent = count + ' visible';
    }

    filters.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!button.disabled) applyFilter(button.dataset.sltrFilter || 'all');
        });
    });

    form.addEventListener('input', function (event) {
        if (event.target.matches('[data-sltr-translation-key]')) refreshState();
    });

    function refreshQualityFilter() {
        var qualityButton = workspace.querySelector('[data-sltr-filter="quality"]');
        if (!qualityButton) return;
        var count = rows.filter(function (row) { return row.dataset.quality === '1'; }).length;
        qualityButton.textContent = 'Quality (' + count + ')';
        qualityButton.disabled = count === 0;
        if (count === 0 && activeFilter === 'quality') {
            applyFilter('all');
        }
    }

    form.addEventListener('click', function (event) {
        var apply = event.target.closest('[data-sltr-apply-suggestion]');
        if (apply) {
            var applyRow = apply.closest('[data-sltr-translation-row]');
            var applyTarget = applyRow && rowField(applyRow);
            var suggestion = apply.dataset.suggestion || '';
            if (!applyTarget || !suggestion) return;
            applyTarget.value = suggestion;
            applyRow.dataset.quality = '0';
            var hint = apply.closest('.sltr-quality-hint');
            if (hint) hint.remove();
            applyTarget.dispatchEvent(new Event('input', { bubbles: true }));
            refreshQualityFilter();
            applyTarget.focus();
            return;
        }

        var copy = event.target.closest('[data-sltr-copy-source]');
        if (!copy) return;
        var row = copy.closest('[data-sltr-translation-row]');
        var source = row && row.querySelector('[data-sltr-source]');
        var target = row && rowField(row);
        if (!source || !target) return;
        target.value = source.value;
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.focus();
    });

    window.addEventListener('beforeunload', function (event) {
        if (isSubmitting) return;
        var changed = fields.some(function (field) { return field.value !== initial.get(field); });
        if (!changed) return;
        event.preventDefault();
        event.returnValue = '';
    });

    form.addEventListener('submit', function () {
        isSubmitting = true;
        fields.forEach(function (field) {
            initial.set(field, field.value);
        });
    });

    var initialButton = workspace.querySelector('[data-sltr-filter="' + activeFilter + '"]');
    if (!initialButton || initialButton.disabled) {
        activeFilter = 'all';
        if (filterInput) filterInput.value = activeFilter;
    }
    refreshState();
}());
