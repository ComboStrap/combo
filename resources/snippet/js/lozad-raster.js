window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const observer = lozad('.combo-lazy-raster', {
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    observer.observe();
});

