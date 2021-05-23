window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const svgObserver = lozad('.combo-lazy-svg', {
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    svgObserver.observe();
});
