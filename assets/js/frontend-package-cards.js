(function () {
    function keepTooltipOnTop(wrapper) {
        if (!wrapper) return;
        wrapper.classList.remove('sltr-tooltip-bottom', 'sltr-tooltip-left', 'sltr-tooltip-right');
        wrapper.classList.add('sltr-tooltip-top');
    }

    function initTopTooltips() {
        document.querySelectorAll('.sltr-package-tooltip').forEach(keepTooltipOnTop);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTopTooltips);
    } else {
        initTopTooltips();
    }
}());

/* v1.0.54 package cards: whole-card navigation disabled. Only explicit links/buttons navigate. */

/* v1.0.215 package slider + animated lightbox / zoom
 * Robust delegated handler: works for shortcode output injected after page load,
 * avoids theme/form click handlers, and falls back to the visible image URL if data-full is missing.
 */
(function () {
    function qsa(root, selector) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function closest(el, selector) {
        while (el && el !== document && el.nodeType === 1) {
            if (el.matches && el.matches(selector)) return el;
            el = el.parentElement;
        }
        return null;
    }

    function closeLightbox() {
        var existing = document.querySelector('.sltr-gallery-lightbox');
        if (existing) {
            existing.classList.add('is-closing');
            window.setTimeout(function () {
                if (existing.parentNode) existing.parentNode.removeChild(existing);
            }, 180);
        }
        if (document.body) document.body.classList.remove('sltr-gallery-lightbox-open');
    }

    function translate(text) {
        if (typeof window.sltrT === 'function') return window.sltrT(text);
        if (window.sltr_ajax && window.sltr_ajax.i18n && window.sltr_ajax.i18n[text]) return window.sltr_ajax.i18n[text];
        return text;
    }

    function openLightbox(src, alt) {
        if (!src) return;
        var old = document.querySelector('.sltr-gallery-lightbox');
        if (old && old.parentNode) old.parentNode.removeChild(old);

        var overlay = document.createElement('div');
        overlay.className = 'sltr-gallery-lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        var imageWrap = document.createElement('div');
        imageWrap.className = 'sltr-gallery-lightbox-frame';

        var image = document.createElement('img');
        image.src = src;
        image.alt = alt || '';

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'sltr-gallery-lightbox-close';
        close.setAttribute('aria-label', translate('Close image preview'));
        close.innerHTML = '&times;';

        imageWrap.appendChild(image);
        overlay.appendChild(imageWrap);
        overlay.appendChild(close);
        document.body.appendChild(overlay);
        document.body.classList.add('sltr-gallery-lightbox-open');
        window.requestAnimationFrame(function () { overlay.classList.add('is-open'); });
    }

    function getActiveIndex(slides) {
        for (var i = 0; i < slides.length; i += 1) {
            if (slides[i].classList.contains('is-active')) return i;
        }
        return 0;
    }

    function showSlide(slider, nextIndex) {
        var slides = qsa(slider, '.sltr-package-slider-slide');
        if (!slides.length) return;
        var index = ((nextIndex % slides.length) + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
            slide.classList.toggle('is-active', i === index);
        });
        qsa(slider, '.sltr-package-slider-dot').forEach(function (dot, i) {
            var active = i === index;
            dot.classList.toggle('is-active', active);
            if (active) dot.setAttribute('aria-current', 'true');
            else dot.removeAttribute('aria-current');
        });
        slider.setAttribute('data-current', String(index));
    }

    function initSlider(slider) {
        if (!slider || slider.getAttribute('data-sltr-ready') === '1') return;
        slider.setAttribute('data-sltr-ready', '1');
        var slides = qsa(slider, '.sltr-package-slider-slide');
        if (slides.length <= 1) return;
        var speed = parseInt(slider.getAttribute('data-speed') || '4000', 10);
        speed = Math.max(1000, Math.min(30000, isNaN(speed) ? 4000 : speed));
        var paused = false;
        var manualPauseUntil = 0;
        var timer = null;
        var next = function () { showSlide(slider, getActiveIndex(slides) + 1); };
        var start = function () {
            if (timer) window.clearInterval(timer);
            timer = window.setInterval(function () {
                if (!paused && Date.now() >= manualPauseUntil) next();
            }, speed);
        };
        slider.__sltrSliderManual = function (index) {
            manualPauseUntil = Date.now() + Math.max(5000, speed);
            showSlide(slider, index);
            start();
        };
        slider.addEventListener('mouseenter', function () { paused = true; });
        slider.addEventListener('mouseleave', function () { paused = false; });
        slider.addEventListener('focusin', function () { paused = true; });
        slider.addEventListener('focusout', function () { paused = false; });
        start();
    }

    function initAllSliders(root) {
        qsa(root || document, '.sltr-package-slider').forEach(initSlider);
    }

    function handleSliderDotClick(event) {
        var target = event.target && event.target.nodeType === 1 ? event.target : event.target.parentElement;
        if (!target) return false;
        var dot = closest(target, '.sltr-package-slider-dot');
        if (!dot) return false;
        var dotSlider = closest(dot, '.sltr-package-slider');
        if (!dotSlider) return false;
        var dotIndex = parseInt(dot.getAttribute('data-slide') || '0', 10);
        if (isNaN(dotIndex)) return false;
        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        if (typeof dotSlider.__sltrSliderManual === 'function') {
            dotSlider.__sltrSliderManual(dotIndex);
        } else {
            showSlide(dotSlider, dotIndex);
        }
        return true;
    }

    document.addEventListener('click', function (event) {
        handleSliderDotClick(event);
    }, true);

    function mediaSrc(mediaItem) {
        var img = mediaItem ? mediaItem.querySelector('img') : null;
        return mediaItem.getAttribute('data-full') || mediaItem.getAttribute('data-src') || (img ? (img.currentSrc || img.src) : '');
    }

    function handleMediaPreviewEvent(event) {
        var target = event.target && event.target.nodeType === 1 ? event.target : event.target.parentElement;
        if (!target) return false;

        if (closest(target, '.sltr-package-slider-dot, .sltr-package-slider-arrow, .sltr-gallery-lightbox-close')) {
            return false;
        }

        var mediaItem = closest(target, '.sltr-package-gallery-item, .sltr-package-slider-slide');
        if (!mediaItem) return false;

        var img = mediaItem.querySelector('img');
        var src = mediaSrc(mediaItem);
        if (!src) return false;

        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        openLightbox(src, img ? img.getAttribute('alt') : '');
        return true;
    }

    document.addEventListener('pointerup', function (event) {
        handleMediaPreviewEvent(event);
    }, true);

    document.addEventListener('click', function (event) {
        var target = event.target && event.target.nodeType === 1 ? event.target : event.target.parentElement;
        if (!target) return;

        var dot = closest(target, '.sltr-package-slider-dot');
        if (dot) {
            var dotSlider = closest(dot, '.sltr-package-slider');
            var dotIndex = parseInt(dot.getAttribute('data-slide') || '0', 10);
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) event.stopImmediatePropagation();
            if (dotSlider && !isNaN(dotIndex)) {
                if (typeof dotSlider.__sltrSliderManual === 'function') {
                    dotSlider.__sltrSliderManual(dotIndex);
                } else {
                    showSlide(dotSlider, dotIndex);
                }
            }
            return;
        }

        if (handleMediaPreviewEvent(event)) {
            return;
        }

        if (closest(target, '.sltr-gallery-lightbox-close') || target.classList.contains('sltr-gallery-lightbox')) {
            event.preventDefault();
            event.stopPropagation();
            closeLightbox();
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeLightbox();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAllSliders(document); });
    } else {
        initAllSliders(document);
    }

    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                qsa(mutation.target, '.sltr-package-slider').forEach(initSlider);
            });
        }).observe(document.documentElement, { childList: true, subtree: true });
    }
}());

(function () {
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var input = document.createElement('textarea');
            input.value = text;
            input.setAttribute('readonly', 'readonly');
            input.style.position = 'fixed';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();
            try {
                document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
            } catch (error) {
                reject(error);
            } finally {
                document.body.removeChild(input);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-sltr-copy-link]') : null;
        if (!button) return;
        event.preventDefault();
        copyText(button.getAttribute('data-sltr-copy-link') || window.location.href).then(function () {
            var original = button.getAttribute('aria-label') || '';
            button.setAttribute('aria-label', sltrT('Copied'));
            button.classList.add('sltr-share-copied');
            window.setTimeout(function () {
                if (original) button.setAttribute('aria-label', original);
                button.classList.remove('sltr-share-copied');
            }, 1200);
        }).catch(function () {});
    });
}());

/* v1.0.287 slider dot manual navigation hardening */
(function () {
    function qsa(root, selector) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }
    function closest(el, selector) {
        while (el && el !== document && el.nodeType === 1) {
            if (el.matches && el.matches(selector)) return el;
            el = el.parentElement;
        }
        return null;
    }
    function show(slider, index) {
        var slides = qsa(slider, '.sltr-package-slider-slide');
        if (!slides.length) return;
        var next = ((index % slides.length) + slides.length) % slides.length;
        slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === next); });
        qsa(slider, '.sltr-package-slider-dot').forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === next);
            if (i === next) dot.setAttribute('aria-current', 'true');
            else dot.removeAttribute('aria-current');
        });
        slider.setAttribute('data-current', String(next));
    }
    window.sltrPackageSliderGo = function (button, index) {
        var slider = closest(button, '.sltr-package-slider');
        if (!slider) return false;
        if (typeof slider.__sltrSliderManual === 'function') slider.__sltrSliderManual(index);
        else show(slider, index);
        return false;
    };
    function bind(root) {
        qsa(root || document, '.sltr-package-slider-dot').forEach(function (dot) {
            if (dot.getAttribute('data-sltr-dot-bound') === '1') return;
            dot.setAttribute('data-sltr-dot-bound', '1');
            dot.addEventListener('click', function (event) {
                var index = parseInt(dot.getAttribute('data-slide') || '0', 10);
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                window.sltrPackageSliderGo(dot, isNaN(index) ? 0 : index);
            }, true);
            dot.addEventListener('pointerdown', function (event) {
                event.stopPropagation();
            }, true);
        });
    }
    document.addEventListener('click', function (event) {
        var dot = closest(event.target && event.target.nodeType === 1 ? event.target : event.target.parentElement, '.sltr-package-slider-dot');
        if (!dot) return;
        var index = parseInt(dot.getAttribute('data-slide') || '0', 10);
        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        window.sltrPackageSliderGo(dot, isNaN(index) ? 0 : index);
    }, true);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { bind(document); });
    else bind(document);
    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) { bind(mutation.target); });
        }).observe(document.documentElement, { childList: true, subtree: true });
    }
}());


/* v1.0.288 package slider external arrow navigation */
(function () {
    function closest(el, selector) {
        while (el && el !== document && el.nodeType === 1) {
            if (el.matches && el.matches(selector)) return el;
            el = el.parentElement;
        }
        return null;
    }
    function qsa(root, selector) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }
    function activeIndex(slider) {
        var slides = qsa(slider, '.sltr-package-slider-slide');
        for (var i = 0; i < slides.length; i += 1) {
            if (slides[i].classList.contains('is-active')) return i;
        }
        var current = parseInt(slider.getAttribute('data-current') || '0', 10);
        return isNaN(current) ? 0 : current;
    }
    function goRelative(button, direction) {
        var slider = closest(button, '.sltr-package-slider');
        if (!slider) return false;
        var slides = qsa(slider, '.sltr-package-slider-slide');
        if (slides.length <= 1) return false;
        var next = activeIndex(slider) + (direction === 'prev' ? -1 : 1);
        next = ((next % slides.length) + slides.length) % slides.length;
        if (typeof slider.__sltrSliderManual === 'function') {
            slider.__sltrSliderManual(next);
        } else if (typeof window.sltrPackageSliderGo === 'function') {
            window.sltrPackageSliderGo(button, next);
        } else {
            slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === next); });
            qsa(slider, '.sltr-package-slider-dot').forEach(function (dot, i) { dot.classList.toggle('is-active', i === next); });
            slider.setAttribute('data-current', String(next));
        }
        return false;
    }
    document.addEventListener('click', function (event) {
        var target = event.target && event.target.nodeType === 1 ? event.target : event.target.parentElement;
        var button = closest(target, '.sltr-package-slider-arrow');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        goRelative(button, button.getAttribute('data-sltr-slider-direction') === 'prev' ? 'prev' : 'next');
    }, true);
}());
