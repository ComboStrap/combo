window.addEventListener("load", function (event) {
    // lazy loads elements with the below selector
    const observer = lozad('.combo-lazy-raster', {
        load: function (el) {
            el.onload = function () {
                el.classList.add('combo-lazy-fade')
            };
            el.srcset = el.dataset.srcset;
        },
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    observer.observe();
});

