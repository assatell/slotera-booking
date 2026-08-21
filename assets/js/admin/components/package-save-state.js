/* Slotera admin package form component. Extracted in v1.0.522. */
/* v1.0.117 package save UX + robust duration checkbox persistence */
(function(){
    function syncShowDurationFallbacks(){
        // v1.0.119: show_duration now posts directly as mode_config[mode][show_duration].
        // Kept as a no-op for compatibility with cached admin JS hooks.
    }
    function initPackageSaveReturn(){
        var form = document.querySelector('form input[name="action"][value="sltr_save_package"]');
        form = form ? form.closest('form') : null;
        if (!form) { return; }
        var idInput = form.querySelector('input[name="id"]');
        var packageId = idInput && idInput.value ? idInput.value : 'new';
        var key = 'sltr_package_scroll_' + packageId;
        if (window.location.search.indexOf('sltr_message=saved') !== -1) {
            var saved = sessionStorage.getItem(key) || sessionStorage.getItem('sltr_package_scroll_new');
            if (saved !== null) {
                setTimeout(function(){ window.scrollTo(0, Math.max(0, parseInt(saved || '0', 10))); }, 80);
                sessionStorage.removeItem(key);
                sessionStorage.removeItem('sltr_package_scroll_new');
            }
        }
        form.addEventListener('submit', function(){
            syncShowDurationFallbacks();
            sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0));
            sessionStorage.setItem('sltr_package_scroll_new', String(window.scrollY || window.pageYOffset || 0));
        });
        document.addEventListener('change', function(event){
            if (event.target && event.target.classList && event.target.classList.contains('sltr-show-duration-checkbox')) {
                syncShowDurationFallbacks();
            }
        });
        syncShowDurationFallbacks();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPackageSaveReturn);
    } else {
        initPackageSaveReturn();
    }

    function initDashboardFilters() {
        const cards = document.querySelectorAll('[data-sltr-dashboard-filter]');
        const sections = document.querySelectorAll('[data-sltr-dashboard-section]');
        if (!cards.length || !sections.length) {
            return;
        }

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                const target = card.getAttribute('data-sltr-dashboard-filter');
                if (!target) {
                    return;
                }

                cards.forEach(function (item) {
                    const active = item === card;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                sections.forEach(function (section) {
                    const active = section.getAttribute('data-sltr-dashboard-section') === target;
                    section.classList.toggle('is-active', active);
                    section.hidden = !active;
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardFilters);
    } else {
        initDashboardFilters();
    }

})();
