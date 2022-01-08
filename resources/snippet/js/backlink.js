/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {


        document.querySelectorAll(".combo-backlink-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                let pageId = JSINFO.id;
                let modalBacklinkId = combo.toHtmlId(`combo-backlink-${pageId}`);
                let backlinkModal = combo.getOrCreateModal(modalBacklinkId)

                /**
                 * Creating the form
                 */
                let qualityCall = "combo-backlink";
                let html = await combo
                    .createDokuRequest(qualityCall)
                    .setProperty("id", pageId)
                    .getText();

                /**
                 * The modal
                 */
                backlinkModal
                    .resetIfBuild()
                    .setHeader(`Backlink (${pageId})`)
                    .addBody(html)
                    .show();
            });

        });
    }
);

