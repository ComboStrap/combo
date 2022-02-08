/* global Glide */
window.addEventListener('load', function () {

    let selector = '.glide';
    let element = document.querySelector(selector);
    let slideMinimalWidth = element.dataset.slideWidth;
    if(typeof slideMinimalWidth === undefined){
        slideMinimalWidth = 300;
    }
    let perView = Math.floor(element.offsetWidth / slideMinimalWidth);
    let glide = new Glide(selector, {
        type: 'carousel',
        perView: perView
    });
    glide.mount();

});
