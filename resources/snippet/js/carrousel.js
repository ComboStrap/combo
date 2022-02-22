/* global Glide */
window.addEventListener('load', function () {

    let selector = '.carrousel-combo';
    const carrousels = [...document.querySelectorAll(selector)];

    carrousels.forEach(carrousel => {
        if (carrousel.classList.contains("glide")) {
            let perView = 1;
            let elementMinimalWidth = carrousel.dataset.elementWidth;
            if (typeof elementMinimalWidth !== 'undefined') {
                let offsetWidth = carrousel.offsetWidth;
                perView = Math.floor(offsetWidth / elementMinimalWidth);
                perView += 0.5; // mobile to show that there is elements
            }
            let glide = new Glide(carrousel, {
                type: 'carousel',
                perView: perView
            });
            glide.mount();
            /**
             * To be able to set percentage height value on the child elements.
             */
            let glideSlideElement = carrousel.querySelector(".glide__slides");
            glideSlideElement.style.height = `${glideSlideElement.offsetHeight}px`;
        } else {
            [...carrousel.childNodes].forEach(child => {
                if (typeof child.classList !== 'undefined') {
                    // not a text node
                    // m-2 and p-0 to add gutter without extra div
                    // not sure how we can transform a width to a `col` class based on media width, fix for now
                    child.classList.add("col", "col-12", "col-sm-6", "col-md-4", "m-2", "p-0");
                }
            });
            carrousel.style.height = `${carrousel.offsetHeight}px`;
        }

    });

});
