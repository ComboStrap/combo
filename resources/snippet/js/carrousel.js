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
            // we can't set the height of the container to have same height component
            // because this is a grid and in small mobile screen, the height would be double
            [...carrousel.childNodes].forEach(child => {
                if (child.nodeType === Node.ELEMENT_NODE) {
                    // wrap it in a col
                    child.classList.remove("glide__slide")
                    let cellElement = document.createElement("div");
                    cellElement.classList.add("col", "col-12", "col-sm-6", "col-md-4");
                    cellElement.appendChild(child);
                    carrousel.appendChild(cellElement);
                }
            });
        }

    });

});
