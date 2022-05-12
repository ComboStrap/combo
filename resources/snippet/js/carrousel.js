/* global Glide */
document.addEventListener('DOMContentLoaded', function () {

    let selector = '.carrousel-combo';
    const carrousels = [...document.querySelectorAll(selector)];

    let carrouselGlideType = "glide";
    let carrouselGridType = "grid";

    carrousels.forEach(carrousel => {

        let elementMinimalWidth = carrousel.dataset.elementWidth;
        let glideCarrousel = carrouselGlideType;
        let elementChildNodes = [...carrousel.childNodes].filter(child => {
            if (child.nodeType === Node.ELEMENT_NODE) {
                return child;
            }
        });
        let childrenCount = elementChildNodes.length;
        if (elementMinimalWidth !== undefined) {
            let elementsMin = carrousel.dataset.elementsMin;
            if (childrenCount < elementsMin) {
                glideCarrousel = carrouselGridType;
            }
        }
        switch (glideCarrousel) {
            case carrouselGridType:
                // we can't set the height of the container to have same height component
                // because this is a grid and in small mobile screen, the height would be double
                carrousel.classList.add("row", `row-cols-${childrenCount}`, "justify-content-center");
                elementChildNodes.forEach(element => {
                    let gridColContainer = document.createElement("div");
                    gridColContainer.classList.add("col");
                    gridColContainer.appendChild(element);
                    carrousel.appendChild(gridColContainer);
                });
                break;
            case carrouselGlideType:

                /**
                 * Slides structure
                 */
                carrousel.classList.add("glide", "glide--ltr", "glide--carousel", "glide--swipeable");
                let glideTrackContainer = document.createElement("div");
                glideTrackContainer.classList.add("glide__track");
                glideTrackContainer.dataset.glideEl = "track";
                carrousel.appendChild(glideTrackContainer);
                let glideSlidesContainer = document.createElement("div");
                glideSlidesContainer.classList.add("glide__slides");
                glideTrackContainer.appendChild(glideSlidesContainer);
                elementChildNodes.forEach(element => {
                    glideSlidesContainer.appendChild(element);
                    element.classList.add("glide__slide");
                    if (element.localName === "a") {
                        // to center the image inside the link
                        element.classList.add("justify-content-center", "align-items-center", "d-flex");
                    }
                });

                /**
                 * Control structure
                 */
                let control = carrousel.dataset.control;
                if (control !== "none") {
                    // move per view |< and |>
                    // https://github.com/glidejs/glide/issues/346#issuecomment-1046137773
                    let controlArrows = `
<div class="d-none d-sm-block" data-glide-el="controls">
    <button class="glide__arrow glide__arrow--left" data-glide-dir="|<">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M0 12l10.975 11 2.848-2.828-6.176-6.176H24v-3.992H7.646l6.176-6.176L10.975 1 0 12z"></path></svg>
    </button>
    <button class="glide__arrow glide__arrow--right" data-glide-dir="|>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"></path></svg>
    </button>
</div>`;
                    carrousel.insertAdjacentHTML('beforeend', controlArrows);

                    let controlBulletContainer = document.createElement("div");
                    carrousel.insertAdjacentElement('beforeend', controlBulletContainer);
                    controlBulletContainer.classList.add("glide__bullets", "d-none", "d-sm-block");
                    controlBulletContainer.dataset.glideEl = "controls[nav]";
                    for (let i = 0; i < childrenCount; i++) {
                        let controlBullet = document.createElement("button");
                        controlBullet.classList.add("glide__bullet");
                        controlBullet.dataset.glideDir = `=${i}`;
                        if (i === 0) {
                            controlBullet.classList.add("glide__bullet--activeClass");
                        }
                        controlBulletContainer.appendChild(controlBullet);
                    }
                }


                window.requestAnimationFrame(function () {

                    let perView = 1;

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
                    glideSlidesContainer.style.height = `${glideSlidesContainer.offsetHeight}px`;

                });
                break;
        }

    });

});
