/* global Glide */
window.addEventListener('load', function () {
    let glide = new Glide('.glide', {
        type: 'carousel',
        perView: 4
    });
    glide.mount();
});
