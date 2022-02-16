/* global Glide */
window.addEventListener('load', function () {

    let selector = '.glide';
    const sliders = [...document.querySelectorAll(selector)];

    sliders.forEach(slider => {
        let slideMinimalWidth = slider.dataset.slideWidth;
        if (typeof slideMinimalWidth === 'undefined') {
            slideMinimalWidth = 250;
        }
        let offsetWidth = slider.offsetWidth;
        let perView = Math.floor(offsetWidth / slideMinimalWidth);
        let glide = new Glide(slider, {
            type: 'carousel',
            perView: perView
        });
        glide.mount();
        /**
         * To be able to set percentage height value on the slide (child)
         */
        let glideSlideElement = slider.querySelector(".glide__slides");
        glideSlideElement.style.height = `${glideSlideElement.offsetHeight}px`;
    });

});
