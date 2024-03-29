window.addEventListener("load", function () {
    // lazy loads elements with the below selector
    const observer = lozad('.lazy-raster-cs', {
        load: function (el) {
            el.classList.add('lazy-fade-cs')
            if (el.hasAttribute("data-srcset")) {
                if (el.hasAttribute("data-sizes")) {
                    el.sizes = el.dataset.sizes;
                }
                el.srcset = el.dataset.srcset;
            }
            if (el.hasAttribute("data-src")) {
                el.src = el.dataset.src;
            }
        },
        loaded: function (el) {
            // Custom implementation on a loaded element
            el.classList.add('loaded-cs');
            // Masonry (Not really needed because the placeholder has the good aspect ratio)
            // but who knows
            if(window.Masonry) {
                document.querySelectorAll('.masonry').forEach(element=>{
                    let masonry = Masonry.data(element);
                    masonry.layout();
                })
            }
        }
    });
    observer.observe();
});

