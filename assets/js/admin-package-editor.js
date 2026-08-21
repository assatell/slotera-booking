(function () {
    function sltrT(text) {
        if (typeof window !== 'undefined' && typeof window.sltrT === 'function') {
            return window.sltrT(text);
        }
        if (typeof window !== 'undefined' && window.sltrAdminI18n && window.sltrAdminI18n[text]) {
            return window.sltrAdminI18n[text];
        }
        return text;
    }


    function sltrSlugify(value) {
        value = String(value || '').trim().toLowerCase();
        if (!value) return '';
        if (value.normalize) value = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
        try {
            return value.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
        } catch (e) {
            return value.replace(/[^a-z0-9\u0370-\u03FF\u0400-\u04FF]+/g, '-').replace(/^-+|-+$/g, '');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('.sltr-generate-slug') : null;
        if (!button) return;

        var targetSelector = button.getAttribute('data-target') || '';
        var sourceSelector = button.getAttribute('data-source') || '';
        var target = targetSelector ? document.querySelector(targetSelector) : null;
        var source = sourceSelector ? document.querySelector(sourceSelector) : null;
        if (!target || !source) return;

        target.value = sltrSlugify(source.value);
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function uniq(values) {
        return values.filter(function (value, index, self) { return value && self.indexOf(value) === index; });
    }

    function parseIds(value) {
        return (value || '').split(',').map(function (id) { return id.trim(); }).filter(Boolean);
    }

    function parseFocus(value) {
        var parts = String(value || '50,50').split(',');
        return { x: Math.max(0, Math.min(100, parseInt(parts[0] || '50', 10))), y: Math.max(0, Math.min(100, parseInt(parts[1] || '50', 10))) };
    }

    function focusFor(preview, id) {
        var source = preview.getAttribute('data-focus-source') || '';
        var input = source ? document.querySelector(source) : null;
        if (!input) return {x:50,y:50};
        if (preview.getAttribute('data-focus-multiple') === '1') {
            try { var map = JSON.parse(input.value || '{}'); return parseFocus(map[id] || '50,50'); } catch (e) { return {x:50,y:50}; }
        }
        return parseFocus(input.value);
    }

    function saveFocus(preview, id, x, y) {
        var source = preview.getAttribute('data-focus-source') || '';
        var input = source ? document.querySelector(source) : null;
        if (!input) return;
        var value = Math.round(x) + ',' + Math.round(y);
        if (preview.getAttribute('data-focus-multiple') === '1') {
            var map = {}; try { map = JSON.parse(input.value || '{}') || {}; } catch (e) {}
            map[id] = value; input.value = JSON.stringify(map);
        } else { input.value = value; }
    }

    function focusIsDisabled(preview) {
        return preview && preview.getAttribute('data-focus-disabled') === '1';
    }

    function syncFocusState(preview) {
        if (!preview) return;
        var disabled = focusIsDisabled(preview);
        preview.classList.toggle('sltr-focus-disabled', disabled);
        preview.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    }

    function attachmentThumb(id, done) {
        if (typeof wp === 'undefined' || !wp.media) {
            done('');
            return;
        }
        var attachment = wp.media.attachment(id);
        attachment.fetch().then(function () {
            var data = attachment.toJSON() || {};
            var sizes = data.sizes || {};
            var url = (sizes.thumbnail && sizes.thumbnail.url) || (sizes.medium && sizes.medium.url) || data.icon || data.url || '';
            done(url);
        }).catch(function () {
            done('');
        });
    }

    function renderPreview(input) {
        if (!input) return;
        var selector = input.getAttribute('id') ? '#' + input.getAttribute('id') : '';
        var preview = selector ? document.querySelector('.sltr-media-preview[data-source="' + selector + '"]') : null;
        if (!preview) return;
        syncFocusState(preview);
        var ids = parseIds(input.value);
        preview.innerHTML = '';
        if (!ids.length) {
            preview.innerHTML = '<span class="sltr-media-preview-empty">No images selected.</span>';
            return;
        }
        ids.forEach(function (id) {
            var chip = document.createElement('span');
            chip.className = 'sltr-media-preview-chip';
            var ratio = preview.getAttribute('data-focus-ratio') || '';
            if (ratio) chip.style.aspectRatio = ratio;
            chip.setAttribute('data-id', id);
            chip.setAttribute('draggable', 'false');
            chip.innerHTML = '<span class="sltr-media-thumb-placeholder">#' + id + '</span><button type="button" class="button-link sltr-media-remove" aria-label="' + sltrT('Remove image') + '">×</button>';
            if (preview.classList.contains('sltr-focus-enabled')) {
                var f = focusFor(preview, id);
                chip.classList.toggle('has-custom-focus', f.x !== 50 || f.y !== 50);
                chip.insertAdjacentHTML('beforeend',
                    '<span class="sltr-focus-crop-guide" aria-hidden="true"></span>' +
                    '<span class="sltr-focus-marker" style="left:' + f.x + '%;top:' + f.y + '%" title="Drag to set focus"></span>' +
                    '<span class="sltr-focus-status">' + (f.x !== 50 || f.y !== 50 ? sltrT('Focus Point active') : sltrT('Focus Point centered')) + '</span>' +
                    '<button type="button" class="button-link sltr-focus-reset" aria-label="' + sltrT('Reset focus to center') + '">' + sltrT('Reset to center') + '</button>'
                );
            }
            if (preview.getAttribute('data-focus-multiple') === '1') {
                chip.insertAdjacentHTML('beforeend', '<span class="sltr-media-drag-handle" draggable="true" title="' + sltrT('Drag to reorder') + '" aria-label="' + sltrT('Drag to reorder') + '">⋮⋮</span>');
            }
            preview.appendChild(chip);
            attachmentThumb(id, function (url) {
                var holder = chip.querySelector('.sltr-media-thumb-placeholder');
                if (!holder) return;
                if (url) {
                    var focus = focusFor(preview, id); holder.outerHTML = '<img class="sltr-media-thumb" src="' + url.replace(/"/g, '&quot;') + '" alt="" style="object-position:' + focus.x + '% ' + focus.y + '%">';
                }
            });
        });
    }

    function insertAtCursor(textarea, text) {
        if (!textarea) return;
        var value = textarea.value || '';
        var start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : value.length;
        var end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : value.length;
        var before = value.slice(0, start).replace(/\s*$/, '');
        var after = value.slice(end).replace(/^\s*/, '');
        var insert = (before ? '\n\n' : '') + text + (after ? '\n\n' : '');
        textarea.value = before + insert + after;
        textarea.focus();
    }

    function initSoloMediaInstances() {
        var mediaJson = document.getElementById('sltr-package-solo-media-json');
        var mediaField = document.getElementById('sltr-package-solo-content');
        var mediaSettings = document.getElementById('sltr-package-media-settings');
        var mediaIds = document.getElementById('sltr-package-media-ids');
        var mediaFocus = document.getElementById('sltr-package-media-focus');
        var mediaSpeed = document.getElementById('sltr-package-media-speed');
        var mediaType = document.getElementById('sltr-package-media-type');
        var mediaVideoId = document.getElementById('sltr-package-media-video-id');
        var imageSettings = document.getElementById('sltr-package-image-settings');
        var videoSettings = document.getElementById('sltr-package-video-settings');
        var videoAutoplay = document.getElementById('sltr-package-media-video-autoplay');
        var activateImages = document.getElementById('sltr-activate-package-images');
        var activateVideo = document.getElementById('sltr-activate-package-video');

        function showMediaType(type) {
            type = type === 'video' ? 'video' : 'images';
            if (mediaType) mediaType.value = type;
            if (imageSettings) imageSettings.classList.toggle('sltr-admin-hidden', type === 'video');
            if (videoSettings) videoSettings.classList.toggle('sltr-admin-hidden', type !== 'video');
        }

        function updateExclusiveState() {
            var hasImages = !!(mediaIds && (mediaIds.value || '').trim());
            var hasVideo = !!(mediaVideoId && parseInt(mediaVideoId.value || '0', 10) > 0);
            if (activateImages) { activateImages.disabled = hasVideo; activateImages.setAttribute('aria-disabled', hasVideo ? 'true' : 'false'); }
            if (activateVideo) { activateVideo.disabled = hasImages; activateVideo.setAttribute('aria-disabled', hasImages ? 'true' : 'false'); }
        }

        function serializeMedia() {
            if (!mediaJson || !mediaIds) return;
            mediaJson.value = JSON.stringify({
                media: {
                    type: mediaType && mediaType.value === 'video' ? 'video' : 'images',
                    ids: mediaIds.value || '',
                    focus: mediaFocus ? mediaFocus.value || '{}' : '{}',
                    speed: mediaSpeed ? parseInt(mediaSpeed.value || '4000', 10) : 4000,
                    video_id: mediaVideoId ? parseInt(mediaVideoId.value || '0', 10) : 0,
                    autoplay: !!(videoAutoplay && videoAutoplay.checked)
                }
            });
            updateExclusiveState();
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-activate-package-media');
            if (!button || !mediaField) return;
            if (mediaVideoId && parseInt(mediaVideoId.value || '0', 10) > 0) return;
            if ((mediaField.value || '').indexOf('[slotera_package_media') === -1) {
                mediaField.value = '[slotera_package_media id="media"]';
            }
            showMediaType('images');
            if (mediaSettings) mediaSettings.classList.remove('sltr-admin-hidden');
            serializeMedia();
        });
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-activate-package-video');
            if (!button || !mediaField) return;
            if (mediaIds && (mediaIds.value || '').trim()) return;
            if ((mediaField.value || '').indexOf('[slotera_package_media') === -1) {
                mediaField.value = '[slotera_package_media id="media"]';
            }
            showMediaType('video');
            if (mediaSettings) mediaSettings.classList.remove('sltr-admin-hidden');
            serializeMedia();
            var selectVideo = document.querySelector('.sltr-video-select[data-target="#sltr-package-media-video-id"]');
            if (selectVideo && !(mediaVideoId && mediaVideoId.value)) selectVideo.click();
        });
        [mediaIds, mediaFocus, mediaSpeed, mediaType, mediaVideoId, videoAutoplay].filter(Boolean).forEach(function (field) {
            field.addEventListener('input', serializeMedia);
            field.addEventListener('change', serializeMedia);
        });

        var contactField = document.getElementById('sltr-package-solo-down-content');
        var contactSettings = document.getElementById('sltr-contact-block-settings');
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-activate-contact-block');
            if (!button || !contactField) return;
            contactField.value = '[slotera_contact]';
            if (contactSettings) contactSettings.classList.remove('sltr-admin-hidden');
        });

        var detailsJson = document.getElementById('sltr-contact-details-json');
        var address = document.getElementById('sltr-contact-address');
        var rows = document.getElementById('sltr-contact-details-rows');
        var socialRows = document.getElementById('sltr-contact-social-rows');
        function serializeDetails() {
            if (!detailsJson) return;
            var data = [];
            if (address && address.value.trim()) {
                data.push({type: 'address', value: address.value.trim()});
            }
            if (rows) {
                rows.querySelectorAll('.sltr-contact-detail-row').forEach(function (row) {
                    var label = row.querySelector('.sltr-contact-detail-label');
                    var value = row.querySelector('.sltr-contact-detail-value');
                    if ((label && label.value) || (value && value.value)) data.push({type: 'contact', label: label ? label.value : '', value: value ? value.value : ''});
                });
            }
            if (socialRows) {
                socialRows.querySelectorAll('.sltr-contact-social-row').forEach(function (row) {
                    var platform = row.querySelector('.sltr-contact-social-platform');
                    var url = row.querySelector('.sltr-contact-social-url');
                    if (url && url.value) data.push({type: 'social', platform: platform ? platform.value : 'instagram', url: url.value});
                });
            }
            detailsJson.value = JSON.stringify(data);
        }
        var add = document.getElementById('sltr-add-contact-detail');
        if (add && rows) add.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'sltr-contact-detail-row';
            row.innerHTML = '<input type="text" class="regular-text sltr-contact-detail-label" placeholder="Mobile, Office, Manager…"> <input type="text" class="regular-text sltr-contact-detail-value" placeholder="Phone number or contact detail"> <button type="button" class="button-link-delete sltr-remove-contact-detail">Remove</button>';
            rows.appendChild(row);
        });
        if (address) {
            address.addEventListener('input', serializeDetails);
        }
        if (rows) {
            rows.addEventListener('click', function (event) {
                var remove = event.target.closest('.sltr-remove-contact-detail');
                if (!remove) return;
                var row = remove.closest('.sltr-contact-detail-row');
                if (row) row.remove();
                serializeDetails();
            });
            rows.addEventListener('input', serializeDetails);
        }
        var addSocial = document.getElementById('sltr-add-contact-social');
        if (addSocial && socialRows) addSocial.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'sltr-contact-social-row';
            row.innerHTML = '<select class="sltr-contact-social-platform"><option value="instagram">Instagram</option><option value="facebook">Facebook</option><option value="linkedin">LinkedIn</option><option value="x">X (Twitter)</option><option value="youtube">YouTube</option><option value="tiktok">TikTok</option></select> <input type="url" class="regular-text sltr-contact-social-url" placeholder="https://"> <button type="button" class="button-link-delete sltr-remove-contact-social">Remove</button>';
            socialRows.appendChild(row);
        });
        if (socialRows) {
            socialRows.addEventListener('click', function (event) {
                var remove = event.target.closest('.sltr-remove-contact-social');
                if (!remove) return;
                var row = remove.closest('.sltr-contact-social-row');
                if (row) row.remove();
                serializeDetails();
            });
            socialRows.addEventListener('input', serializeDetails);
            socialRows.addEventListener('change', serializeDetails);
        }
        var form = mediaJson ? mediaJson.closest('form') : null;
        if (form) form.addEventListener('submit', function () { serializeMedia(); serializeDetails(); });
        serializeMedia();
        serializeDetails();
    }

    function initRightContentTools() {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-activate-text-block');
            if (!button) return;
            var field = document.querySelector(button.getAttribute('data-target') || '');
            var active = document.getElementById('sltr-package-solo-top-active');
            var status = document.getElementById('sltr-package-solo-top-status');
            if (field) field.value = '[slotera_package_text_block]';
            if (active) active.value = '1';
            if (status) status.textContent = 'Title/text block is active for this package.';
        });
    }

    function renderVideoPreview(input) {
        if (!input) return;
        var preview = document.querySelector('.sltr-package-video-admin-preview[data-source="#' + input.id + '"]');
        if (!preview) return;
        preview.innerHTML = '';
        var id = parseInt(input.value || '0', 10);
        if (!id || typeof wp === 'undefined' || !wp.media) {
            preview.textContent = sltrT('No video selected.');
            return;
        }
        var attachment = wp.media.attachment(id);
        attachment.fetch().then(function () {
            var data = attachment.toJSON() || {};
            if (!data.url) { preview.textContent = sltrT('No video selected.'); return; }
            var video = document.createElement('video');
            video.controls = true;
            video.preload = 'metadata';
            video.src = data.url;
            preview.appendChild(video);
            var meta = document.createElement('p');
            meta.className = 'description';
            meta.textContent = data.filename || data.title || '';
            preview.appendChild(meta);
        }).catch(function () { preview.textContent = sltrT('No video selected.'); });
    }

    function initPackageMediaFields() {
        if (typeof wp === 'undefined' || !wp.media) return;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-replace-contact-image');
            if (!button) return;

            var input = document.getElementById('sltr-contact-image-id');
            var preview = document.getElementById('sltr-contact-image-preview-img');
            if (!input || !preview) return;

            var frame = wp.media({
                title: 'Replace contact image',
                button: { text: 'Use this image' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').first();
                if (!selection) return;
                var attachment = selection.toJSON();
                input.value = attachment.id || '';
                preview.src = (attachment.sizes && attachment.sizes.large && attachment.sizes.large.url)
                    ? attachment.sizes.large.url
                    : (attachment.url || '');
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            frame.open();
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.sltr-use-default-contact-image');
            if (!button) return;

            var input = document.getElementById('sltr-contact-image-id');
            var preview = document.getElementById('sltr-contact-image-preview-img');
            if (!input || !preview) return;

            input.value = '0';
            preview.src = button.getAttribute('data-default-url') || '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        document.querySelectorAll('.sltr-media-ids').forEach(renderPreview);
        document.querySelectorAll('.sltr-package-video-admin-preview').forEach(function (preview) {
            var source = preview.getAttribute('data-source') || '';
            var input = source ? document.querySelector(source) : null;
            if (input) renderVideoPreview(input);
        });
        document.querySelectorAll('.sltr-focus-enabled').forEach(syncFocusState);


        document.addEventListener('dragstart', function (event) {
            var handle = event.target.closest('.sltr-media-drag-handle');
            var chip = handle ? handle.closest('.sltr-media-preview-chip') : null;
            if (!chip) { event.preventDefault(); return; }
            chip.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', chip.getAttribute('data-id') || '');
        });

        document.addEventListener('dragend', function (event) {
            var chip = event.target.closest('.sltr-media-preview-chip');
            if (chip) chip.classList.remove('is-dragging');
            document.querySelectorAll('.sltr-media-preview-chip.is-drag-over').forEach(function (item) {
                item.classList.remove('is-drag-over');
            });
        });

        document.addEventListener('dragover', function (event) {
            var chip = event.target.closest('.sltr-media-preview-chip');
            if (!chip) return;
            event.preventDefault();
            chip.classList.add('is-drag-over');
        });

        document.addEventListener('dragleave', function (event) {
            var chip = event.target.closest('.sltr-media-preview-chip');
            if (chip) chip.classList.remove('is-drag-over');
        });

        document.addEventListener('drop', function (event) {
            var targetChip = event.target.closest('.sltr-media-preview-chip');
            if (!targetChip) return;
            var preview = targetChip.closest('.sltr-media-preview');
            var source = preview ? preview.getAttribute('data-source') : '';
            var input = source ? document.querySelector(source) : null;
            var draggedChip = preview ? preview.querySelector('.sltr-media-preview-chip.is-dragging') : null;
            if (!input || !draggedChip || draggedChip === targetChip) return;
            event.preventDefault();
            var ids = parseIds(input.value);
            var draggedId = draggedChip.getAttribute('data-id');
            var targetId = targetChip.getAttribute('data-id');
            var draggedIndex = ids.indexOf(draggedId);
            var targetIndex = ids.indexOf(targetId);
            if (draggedIndex < 0 || targetIndex < 0) return;
            ids.splice(draggedIndex, 1);
            ids.splice(targetIndex, 0, draggedId);
            input.value = ids.join(',');
            renderPreview(input);
        });

        document.addEventListener('click', function (event) {
            var reset = event.target.closest('.sltr-focus-reset');
            if (reset) {
                event.preventDefault();
                event.stopPropagation();
                var resetChip = reset.closest('.sltr-media-preview-chip');
                var resetPreview = resetChip ? resetChip.closest('.sltr-media-preview') : null;
                if (resetChip && resetPreview && !focusIsDisabled(resetPreview)) updateFocusUi(resetChip, resetPreview, 50, 50);
                return;
            }
            var select = event.target.closest('.sltr-media-select');
            var clear = event.target.closest('.sltr-media-clear');
            var remove = event.target.closest('.sltr-media-remove');

            if (remove) {
                var preview = remove.closest('.sltr-media-preview');
                var source = preview ? preview.getAttribute('data-source') : '';
                var input = source ? document.querySelector(source) : null;
                var chip = remove.closest('.sltr-media-preview-chip');
                var id = chip ? chip.getAttribute('data-id') : '';
                if (input && id) {
                    input.value = parseIds(input.value).filter(function (value) { return value !== id; }).join(',');
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    renderPreview(input);
                }
                return;
            }

            if (clear) {
                var clearTarget = document.querySelector(clear.getAttribute('data-target') || '');
                if (clearTarget) {
                    clearTarget.value = '';
                    clearTarget.dispatchEvent(new Event('input', { bubbles: true }));
                    clearTarget.dispatchEvent(new Event('change', { bubbles: true }));
                    renderPreview(clearTarget);
                }
                return;
            }

            var videoSelect = event.target.closest('.sltr-video-select');
            var videoClear = event.target.closest('.sltr-video-clear');
            if (videoClear) {
                var clearVideoTarget = document.querySelector(videoClear.getAttribute('data-target') || '');
                if (clearVideoTarget) {
                    clearVideoTarget.value = '';
                    clearVideoTarget.dispatchEvent(new Event('change', { bubbles: true }));
                    renderVideoPreview(clearVideoTarget);
                }
                return;
            }
            if (videoSelect) {
                var videoInput = document.querySelector(videoSelect.getAttribute('data-target') || '');
                if (!videoInput) return;
                var videoFrame = wp.media({
                    title: sltrT('Select video'),
                    button: { text: sltrT('Use this video') },
                    library: { type: 'video' },
                    multiple: false
                });
                videoFrame.on('select', function () {
                    var selection = videoFrame.state().get('selection').first();
                    if (!selection) return;
                    var item = selection.toJSON() || {};
                    var mime = String(item.mime || '').toLowerCase();
                    var allowedVideoMimes = ['video/mp4', 'video/webm', 'video/ogg'];
                    if (allowedVideoMimes.indexOf(mime) === -1) {
                        window.alert(sltrT('Unsupported video format. Use MP4, WebM or Ogg. For the widest browser compatibility, use MP4 with H.264 video and AAC audio.'));
                        return;
                    }
                    videoInput.value = item.id || '';
                    videoInput.dispatchEvent(new Event('input', { bubbles: true }));
                    videoInput.dispatchEvent(new Event('change', { bubbles: true }));
                    renderVideoPreview(videoInput);
                });
                videoFrame.open();
                return;
            }

            if (!select) return;

            var input = document.querySelector(select.getAttribute('data-target') || '');
            if (!input) return;
            var max = parseInt(select.getAttribute('data-max') || input.getAttribute('data-max') || '20', 10);
            var multiple = select.getAttribute('data-multiple') === '1';

            var frame = wp.media({
                title: sltrT('Select images'),
                button: { text: sltrT('Add selected images') },
                library: { type: 'image' },
                multiple: multiple ? 'add' : false
            });

            frame.on('open', function () {
                var selection = frame.state().get('selection');
                parseIds(input.value).forEach(function (id) {
                    var attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                });
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').toJSON();
                var validation = document.getElementById('sltr-package-media-validation');
                var isSliderTarget = input.id === 'sltr-package-media-ids';
                var allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                var invalidType = 0;
                var invalidSize = 0;
                var accepted = selection.filter(function (item) {
                    if (!isSliderTarget) return true;
                    var mime = String(item.mime || '').toLowerCase();
                    if (mime && allowedImageMimes.indexOf(mime) === -1) {
                        invalidType += 1;
                        return false;
                    }
                    var width = parseInt(item.width || '0', 10);
                    var height = parseInt(item.height || '0', 10);
                    if (width > 0 && height > 0 && (width < 300 || height < 300)) {
                        invalidSize += 1;
                        return false;
                    }
                    return true;
                });

                if (validation && isSliderTarget) {
                    if (invalidType > 0) {
                        validation.textContent = sltrT('Unsupported image type. Use JPG, PNG, GIF or WebP.');
                    } else if (invalidSize > 0) {
                        validation.textContent = sltrT('Image is too small for the slider. Minimum size: 300 × 300 px. Recommended: 1600 × 900 px.');
                    } else {
                        validation.textContent = '';
                    }
                }

                var selected = accepted.map(function (item) { return String(item.id); }).filter(Boolean);
                if (!selected.length && selection.length) return;
                var ids;
                if (multiple) {
                    var existing = parseIds(input.value);
                    ids = uniq(existing.concat(selected)).slice(0, Math.max(1, max));
                } else {
                    ids = selected.length ? [selected[selected.length - 1]] : [];
                }
                input.value = ids.join(',');
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                renderPreview(input);
            });

            frame.open();
        });
    }



    function initMarketingPreview() {
        var modal = document.querySelector('.sltr-marketing-preview-modal');
        if (!modal) return;
        var iframe = modal.querySelector('iframe');
        var emailInput = document.getElementById('sltr_marketing_preview_email');
        var openButton = document.querySelector('.sltr-marketing-preview-open');
        var closeButtons = modal.querySelectorAll('.sltr-marketing-preview-close, .sltr-marketing-preview-backdrop');
        var testForm = document.querySelector('.sltr-marketing-test-form');
        var hiddenEmail = document.querySelector('.sltr-marketing-test-email-hidden');

        function currentEmail() {
            return emailInput ? String(emailInput.value || '').trim() : '';
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (iframe) iframe.setAttribute('src', 'about:blank');
            document.body.classList.remove('sltr-preview-modal-open');
        }

        if (openButton) {
            openButton.addEventListener('click', function () {
                var base = openButton.getAttribute('data-preview-url') || '';
                if (!base || !iframe) return;
                var separator = base.indexOf('?') === -1 ? '?' : '&';
                iframe.setAttribute('src', base + separator + 'preview_email=' + encodeURIComponent(currentEmail()));
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('sltr-preview-modal-open');
            });
        }

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });

        if (testForm && hiddenEmail) {
            testForm.addEventListener('submit', function () {
                hiddenEmail.value = currentEmail();
            });
        }
    }

    function init() {
        initRightContentTools();
        initPackageMediaFields();
        initSoloMediaInstances();
        initMarketingPreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
