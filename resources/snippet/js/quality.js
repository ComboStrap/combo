/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {


        document.querySelectorAll(".combo-quality-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                let pageId = JSINFO.id;
                let modalQualityMessageId = combo.toHtmlId(`combo-quality-message-page-${pageId}`);
                let qualityMessageModal = combo.getOrCreateModal(modalQualityMessageId)
                    .addDialogClass("modal-fullscreen-md-down");

                /**
                 * Creating the form
                 */
                let qualityCall = "combo-quality-message";
                let html = await combo
                    .createDokuRequest(qualityCall)
                    .setProperty("id", pageId)
                    .getText();

                /**
                 * The modal
                 */
                qualityMessageModal
                    .resetIfBuild()
                    .setHeader(`Quality for Page (${pageId})`)
                    .addBody(html)
                    .show();
            });

        });
    }
)
;

