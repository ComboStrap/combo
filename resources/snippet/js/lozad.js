window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const observer = lozad('.lozad', {
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('combo-loaded');
        }
    });
    observer.observe();
});

window.addEventListener("load", function (event) {
    // lazy loads elements with default selector as '.lozad'
    const svgObserver = lozad('.lozad-svg', {
        load: function (el) {
            // Custom implementation to load the svg element
            SVGInjector(el, {
                    each: function (svg) {
                        // Callback after each SVG is injected, to update the element
                        el = svg;
                    }
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
