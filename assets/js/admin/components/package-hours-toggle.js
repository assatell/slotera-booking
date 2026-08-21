/* Slotera admin package form component. Extracted in v1.0.522. */
(function(){
    function sltrToggleOpen247Hours(){
        var checkbox = document.getElementById('sltr-open-247');
        var table = document.querySelector('.sltr-package-hours-table');
        if (!checkbox || !table) { return; }
        var disabled = checkbox.checked;
        table.querySelectorAll('input').forEach(function(input){ input.disabled = disabled; });
        table.style.opacity = disabled ? '0.55' : '';
    }
    document.addEventListener('DOMContentLoaded', function(){
        var checkbox = document.getElementById('sltr-open-247');
        if (!checkbox) { return; }
        checkbox.addEventListener('change', sltrToggleOpen247Hours);
        sltrToggleOpen247Hours();
    });
})();
