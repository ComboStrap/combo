window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const svgObserver = lozad('.combo-lazy-svg-injection', {
        load: function (el) {
            // Custom implementation to load the svg element
            SVGInjector(el, {
                    each: function (svg) {
                        // Style copy (max width)
                        // If any error, svg is a string with the error
                        // Example: Unable to load SVG file: http://doku/_media/ui/preserveaspectratio.svg
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
