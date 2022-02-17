/* global Glide */
window.addEventListener('load', function () {

    let selector = '.glide';
    const sliders = [...document.querySelectorAll(selector)];

    sliders.forEach(slider => {
        let perView = 1;
        let elementMinimalWidth = slider.dataset.elementWidth;
        if (typeof elementMinimalWidth !== 'undefined') {
            let offsetWidth = slider.offsetWidth;
            perView = Math.floor(offsetWidth / elementMinimalWidth);
        }
        let glide = new Glide(slider, {
            type: 'carousel',
            perView: perView
        });
        glide.mount();
        /**
         * To be able to set percentage height value on the child elements.
         */
        let glideSlideElement = slider.querySelector(".glide__slides");
        glideSlideElement.style.height = `${glideSlideElement.offsetHeight}px`;
    });

});
