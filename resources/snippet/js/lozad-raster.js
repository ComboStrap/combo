window.addEventListener("load", function (event) {
    // lazy loads elements with the below selector
    const observer = lozad('.combo-lazy-raster', {
        load: function (el) {
            el.classList.add('combo-lazy-fade')
            if (el.hasAttribute("data-srcset")) {
                el.srcset = el.dataset.srcset;
            }
            if (el.hasAttribute("data-src")) {
                el.src = el.dataset.src;
            }
        },
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    observer.observe();
});

