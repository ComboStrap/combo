
window.addEventListener("DOMContentLoaded", function () {


        document.querySelectorAll(".combo-quality-item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", async function (event) {
                event.preventDefault();

                const combo = /** @type {import('combo.d.ts')} */ (window.combo);
                if(!('JSINFO' in window)){
                    throw new Error("JSINFO is not available")
                }
                const JSINFO = window.JSINFO;
                let pageId = JSINFO.id;
                let modalQualityMessageId = combo.Html.toHtmlId(`combo-quality-message-page-${pageId}`);
                let qualityMessageModal = combo.Modal.getOrCreate(modalQualityMessageId)
                    .addDialogClass("modal-fullscreen-md-down");

                /**
                 * Creating the form
                 */
                let qualityCall = "combo-quality-message";
                let html = await combo
                    .DokuUrl
                    .createAjax(qualityCall)
                    .setProperty("id", pageId)
                    .toRequest()
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

