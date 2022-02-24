window.addEventListener("DOMContentLoaded", function () {


    document.querySelectorAll(".lightbox-combo").forEach((lightBoxAnchor) => {

        let drag = false;

        lightBoxAnchor.addEventListener('mousedown', () => drag = false);
        lightBoxAnchor.addEventListener('mousemove', () => drag = true);

        /**
         * Click is an event that appears after mouseup
         */
        let startX;
        let startY;
        let delta = 6;
        lightBoxAnchor.addEventListener("click", async function (event) {
            // we open the lightbox on mouseup
            event.preventDefault();
        });
        lightBoxAnchor.addEventListener("mousedown", async function (event) {
            // capture the position to see if it's a drag or a click
            startX = event.pageX;
            startY = event.pageY;
        });

        lightBoxAnchor.addEventListener("mouseup", event => {
            const diffX = Math.abs(event.pageX - startX);
            const diffY = Math.abs(event.pageY - startY);
            if (diffX < delta && diffY < delta) {
                // A click
                openLightbox();
            }
        });
        let openLightbox = function () {
            let lightBoxId = combo.toHtmlId(`combo-lightbox`);

            let lightBoxModel = combo.getOrCreateModal(lightBoxId);
            let src = lightBoxAnchor.getAttribute("href");
            let img = lightBoxAnchor.querySelector("img");
            let alt = "Image";
            if (img !== null && img.hasAttribute("alt")) {
                alt = img.getAttribute("alt");
            }
            let namespace = "-bs"
            let bsVersion = parseInt(bootstrap.Modal.VERSION.substr(0, 1), 10);
            if (bsVersion < 5) {
                namespace = "";
            }

            let svgStyle = "max-height:95vh;max-width:95vw";
            if (src.match(/svg/i) !== null) {
                // a svg does not show without width
                // because the intrinsic svg can be really small, we put a min with
                svgStyle += ';width: 100%;min-width: 75vw'
            }
            let html = `
<button type="button" class="lightbox-close-combo" data${namespace}-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>
<img src="${src}" alt="${alt}" style="${svgStyle}"/>
`
            lightBoxModel
                .resetIfBuild()
                .centered()
                .addDialogStyle("max-width", "fit-content")
                .addBody(html)
                .addBodyStyle("padding", "0")
                .noFooter()
                .show();
        }
    });

});
