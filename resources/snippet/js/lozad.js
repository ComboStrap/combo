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
            console.log('loading svg element');
            // Custom implementation to load an element
            SVGInjector(el, {
                    each: function (svg) {
                        // Callback after each SVG is injected
                        console.log('SVG injected: ' + svg.getAttribute('id'));
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
