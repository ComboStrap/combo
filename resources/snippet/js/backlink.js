/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {

        document.querySelectorAll(".combo-backlink-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                let pageId = JSINFO.id;
                let modalBacklinkId = combo.toHtmlId(`combo-backlink-${pageId}`);
                let backlinkModal = combo.getOrCreateModal(modalBacklinkId)
                    .addDialogClass("modal-fullscreen-md-down");

                /**
                 * Creating the form
                 */
                let qualityCall = "combo-backlink";
                let html = await combo
                    .createDokuRequest(qualityCall)
                    .setProperty("id", pageId)
                    .getText();
                html = `<p>List of pages that link back to the page (${pageId}).</p>${html}`;

                let wikiBacklinkButton = document.createElement("a");
                wikiBacklinkButton.classList.add("btn", "btn-secondary")
                wikiBacklinkButton.setAttribute("role", "button")
                wikiBacklinkButton.setAttribute("title", "Go to the original backlinks page")
                wikiBacklinkButton.innerHTML = "Original Backlinks Page";
                wikiBacklinkButton.setAttribute("href", JSINFO["whref"] + "?do=backlink")

                /**
                 * The modal
                 */
                backlinkModal
                    .resetIfBuild()
                    .setCentered(true)
                    .setHeader(`Backlinks for the page (${pageId})`)
                    .addBody(html)
                    .addFooterButton(wikiBacklinkButton)
                    .addFooterCloseButton()
                    .show();
            });

        });
    }
);

