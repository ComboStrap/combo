/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {


        document.querySelectorAll(".combo-quality-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                let pageId = JSINFO.id;
                let modalQualityMessageId = combo.toHtmlId(`combo-quality-message-page-${pageId}`);
                let qualityMessageModal = combo.getOrCreateModal(modalQualityMessageId)

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
                debugger;
                qualityMessageModal
                    .setHeader(`Quality Message for Page (${pageId})`)
                    .addBody(html)
                    .show();
            });

        });
    }
)
;

