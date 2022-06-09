window.addEventListener("load", function () {
    // lazy loads elements with default selector as '.lozad'
    const svgObserver = lozad('.lazy-svg-cs', {
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('loaded-cs');
        }
    });
    svgObserver.observe();
});
