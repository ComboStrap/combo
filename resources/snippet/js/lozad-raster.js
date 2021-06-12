window.addEventListener("load", function (event) {
    // lazy loads elements with the below selector
    const observer = lozad('.lazy-raster-combo', {
        load: function (el) {
            el.classList.add('lazy-fade-combo')
            if (el.hasAttribute("data-srcset")) {
                el.srcset = el.dataset.srcset;
            }
            if (el.hasAttribute("data-src")) {
                el.src = el.dataset.src;
            }
        },
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('loaded-combo');
        }
    });
    observer.observe();
});

