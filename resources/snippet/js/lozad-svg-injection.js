window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const svgObserver = lozad('.combo-lazy-svg-injection', {
        load: function (el) {
            // Custom implementation to load the svg element
            SVGInjector(el, {
                    each: function (svg) {
                        // Style copy (max width)
                        svg.style.cssText = el.style.cssText;
                        el.dataset.class.split(" ").forEach(e => svg.classList.add(e));
                    },
                }
            )
        },
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    svgObserver.observe();
});
