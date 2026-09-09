(function () {
    'use strict';

    function init() {
        var root = document.getElementById('sltr-system-contact-settings');
        if (!root) return;

        var imageId = document.getElementById('sltr-system-contact-image-id');
        var imagePreview = document.getElementById('sltr-system-contact-image-preview');
        var replaceImage = document.getElementById('sltr-system-contact-replace-image');
        var useDefault = document.getElementById('sltr-system-contact-use-default');
        var detailsJson = document.getElementById('sltr-system-contact-details-json');
        var address = document.getElementById('sltr-system-contact-address');
        var detailRows = document.getElementById('sltr-system-contact-detail-rows');
        var socialRows = document.getElementById('sltr-system-contact-social-rows');

        function serializeDetails() {
            if (!detailsJson) return;
            var data = [];
            if (address && address.value.trim()) {
                data.push({type: 'address', value: address.value.trim()});
            }
            if (detailRows) {
                detailRows.querySelectorAll('.sltr-contact-detail-row').forEach(function (row) {
                    var label = row.querySelector('.sltr-contact-detail-label');
                    var value = row.querySelector('.sltr-contact-detail-value');
                    if ((label && label.value) || (value && value.value)) {
                        data.push({type: 'contact', label: label ? label.value : '', value: value ? value.value : ''});
                    }
                });
            }
            if (socialRows) {
                socialRows.querySelectorAll('.sltr-contact-social-row').forEach(function (row) {
                    var platform = row.querySelector('.sltr-contact-social-platform');
                    var url = row.querySelector('.sltr-contact-social-url');
                    if (url && url.value) {
                        data.push({type: 'social', platform: platform ? platform.value : 'instagram', url: url.value});
                    }
                });
            }
            detailsJson.value = JSON.stringify(data);
        }

        if (replaceImage) {
            replaceImage.addEventListener('click', function () {
                if (typeof wp === 'undefined' || !wp.media) return;
                var frame = wp.media({title: 'Select contact page image', button: {text: 'Use this image'}, multiple: false});
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    if (imageId) imageId.value = String(attachment.id || 0);
                    if (imagePreview) imagePreview.src = attachment.sizes && attachment.sizes.large ? attachment.sizes.large.url : attachment.url;
                });
                frame.open();
            });
        }

        if (useDefault) {
            useDefault.addEventListener('click', function () {
                if (imageId) imageId.value = '0';
                if (imagePreview) imagePreview.src = useDefault.getAttribute('data-default-url') || '';
            });
        }

        var addDetail = document.getElementById('sltr-system-contact-add-detail');
        if (addDetail && detailRows) {
            addDetail.addEventListener('click', function () {
                var row = document.createElement('div');
                row.className = 'sltr-contact-detail-row';
                row.innerHTML = '<input type="text" class="regular-text sltr-contact-detail-label" placeholder="Mobile, Office, Manager…"> <input type="text" class="regular-text sltr-contact-detail-value" placeholder="Phone number or contact detail"> <button type="button" class="button-link-delete sltr-system-contact-remove-detail">Remove</button>';
                detailRows.appendChild(row);
            });
            detailRows.addEventListener('click', function (event) {
                var remove = event.target.closest('.sltr-system-contact-remove-detail');
                if (!remove) return;
                var row = remove.closest('.sltr-contact-detail-row');
                if (row) row.remove();
                serializeDetails();
            });
            detailRows.addEventListener('input', serializeDetails);
        }

        var addSocial = document.getElementById('sltr-system-contact-add-social');
        if (addSocial && socialRows) {
            addSocial.addEventListener('click', function () {
                var row = document.createElement('div');
                row.className = 'sltr-contact-social-row';
                row.innerHTML = '<select class="sltr-contact-social-platform"><option value="instagram">Instagram</option><option value="facebook">Facebook</option><option value="linkedin">LinkedIn</option><option value="x">X (Twitter)</option><option value="youtube">YouTube</option><option value="tiktok">TikTok</option></select> <input type="url" class="regular-text sltr-contact-social-url" placeholder="https://"> <button type="button" class="button-link-delete sltr-system-contact-remove-social">Remove</button>';
                socialRows.appendChild(row);
            });
            socialRows.addEventListener('click', function (event) {
                var remove = event.target.closest('.sltr-system-contact-remove-social');
                if (!remove) return;
                var row = remove.closest('.sltr-contact-social-row');
                if (row) row.remove();
                serializeDetails();
            });
            socialRows.addEventListener('input', serializeDetails);
            socialRows.addEventListener('change', serializeDetails);
        }

        if (address) address.addEventListener('input', serializeDetails);
        var form = root.closest('form');
        if (form) form.addEventListener('submit', serializeDetails);
        serializeDetails();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
