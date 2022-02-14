window.addEventListener("DOMContentLoaded", function () {


    document.querySelectorAll(".lightbox-combo").forEach((lightBoxAnchor) => {

        lightBoxAnchor.addEventListener("click", async function (event) {
            event.preventDefault();

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

            let html = `
<button type="button" class="lightbox-close-combo" data${namespace}-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>
<img src="${src}" alt="${alt}"/>
`
            lightBoxModel
                .resetIfBuild()
                .centered()
                .addDialogStyle("max-width", "fit-content")
                .addBody(html)
                .addBodyStyle("padding", "0")
                .noFooter()
                .show();
        });
    });

});