
window.addEventListener("DOMContentLoaded", function () {


        /**
         *
         * @type ComboModal modalManager
         */
        async function openMetaViewer(modalViewer, modalManager, pageId) {


            if (modalViewer.wasBuild()) {
                modalViewer.show();
                return;
            }

            const viewerCallEndpoint = "combo-meta-viewer";
            const combo = /** @type {import('combo.d.ts')} */ (window.combo);
            let viewerCall = combo
                .DokuUrl
                .createAjax(viewerCallEndpoint)
                .setProperty("id", pageId)
                .toRequest();
            let jsonFormMeta = await viewerCall.getJson();


            let formViewerId = combo.Html.toHtmlId(`combo-metadata-viewer-form-${pageId}`);
            let formHtmlElement = combo.Form.createFromJson(formViewerId, jsonFormMeta).toHtmlElement();

            /**
             * Submit Button
             */
            let submitButton = document.createElement("button");
            submitButton.classList.add("btn", "btn-primary");
            submitButton.setAttribute("type", "submit");
            submitButton.setAttribute("form", formHtmlElement.id);
            submitButton.innerText = "Submit";
            submitButton.addEventListener("click", async function (event) {
                event.preventDefault();
                let formData = new FormData(formHtmlElement);
                let response = await combo.DokuUrl
                    .createAjax(viewerCallEndpoint)
                    .toRequest()
                    .setMethod("post")
                    .sendFormDataAsJson(formData);
                modalViewer.reset();
                modalManager.reset();
                await processResponse(response, () => openMetaViewer(modalViewer, modalManager, pageId));
            });
            modalViewer
                .setCallBackOnClose(() => openMetadataManager(pageId))
                .setHeader("Metadata Viewer")
                .addBody(`<p>The metadata viewer shows you the content of the metadata file (ie all metadata managed by ComboStrap or not):</p>`)
                .addBody(formHtmlElement)
                .addFooterCloseButton()
                .addFooterButton(submitButton)
                .show();


        }


        const metaManagerCall = "combo-meta-manager";

        /**
         *
         * @param {Response} response
         * @param {function} callBack
         * @return {Promise<void>}
         */
        async function processResponse(response, callBack) {
            let modalMessage = [];
            if (response.status !== 200) {
                modalMessage.push(`Error, unable to save. (return code: ${response.status})`);
            }
            try {
                let json = await response.json();
                if (json !== null) {
                    if (json.hasOwnProperty("message")) {
                        let jsonMessage = json["message"];
                        if (Array.isArray(jsonMessage)) {
                            modalMessage = modalMessage.concat(jsonMessage);
                        } else {
                            modalMessage.push(jsonMessage)
                        }
                    }
                }
            } catch (/** @type Error */ e) {
                modalMessage.push(e.message)
            }
            const combo = /** @type {import('combo.d.ts')} */ (window.combo);
            combo.Modal
                .createTemporary()
                .setCallBackOnClose(callBack)
                .centered()
                .addBody(modalMessage.join("<br>"))
                .show();
        }

        let openMetadataManager = async function (pageId) {


            /**
             * The manager modal root
             * (used in button)
             */
            const combo = /** @type {import('combo.d.ts')} */ (window.combo);
            let modalManagerId = combo.Html.toHtmlId(`combo-meta-manager-page-${pageId}`);
            let managerModal = combo.Modal.getOrCreate(modalManagerId)
                .addDialogClass("modal-fullscreen-md-down");
            if (managerModal.wasBuild()) {
                managerModal.show();
                return;
            }

            /**
             * The viewer
             * We create it here because it needs to be reset if there is a submit on the manager.
             */
            let modalViewerId = combo.Html.toHtmlId(`combo-metadata-viewer-modal-${pageId}`);
            let modalViewer = combo.Modal.getOrCreate(modalViewerId)
                .addDialogClass("modal-fullscreen-md-down");

            /**
             * Creating the form
             */
            let formMetadata = await combo
                .DokuUrl
                .createAjax(metaManagerCall)
                .setProperty("id", pageId)
                .toRequest()
                .getJson();
            let formId = combo.Html.toHtmlId(`${modalManagerId}-form`);
            let form = combo.Form.createFromJson(formId, formMetadata);
            let htmlFormElement = form.toHtmlElement();


            /**
             * Viewer Button
             */
            let viewerButton = document.createElement("button");
            viewerButton.classList.add("btn", "btn-link", "text-primary", "text-decoration-bone", "fs-6", "text-muted");
            viewerButton.style.setProperty("font-weight", "300");
            viewerButton.textContent = "Open Metadata Viewer";
            viewerButton.addEventListener("click", async function () {
                managerModal.dismissHide();
                await openMetaViewer(modalViewer, managerModal, pageId);
            });

            /**
             * Submit Button
             */
            let submitButton = document.createElement("button");
            submitButton.classList.add("btn", "btn-primary");
            submitButton.setAttribute("type", "submit");
            submitButton.setAttribute("form", formId);
            submitButton.innerText = "Submit";
            submitButton.addEventListener("click", async function (event) {
                event.preventDefault();
                let formData = new FormData(htmlFormElement);
                let response = await combo.DokuUrl.createAjax(metaManagerCall)
                    .toRequest()
                    .setMethod("post")
                    .sendFormDataAsJson(formData);
                managerModal.reset();
                modalViewer.reset();

                /**
                 * Send a cron request to re-render if any
                 */
                fetch(combo.DokuUrl.createRunner().toString(), {method: "GET"})
                    .then((response) => {
                            if (response.status !== 200) {
                                console.error('Bad runner request, status Code is: ' + response.status);
                            }
                        }
                    );

                await processResponse(response, () => openMetadataManager(pageId));
            })

            /**
             * The modal
             */
            managerModal
                .resetIfBuild()
                .setHeader(`Metadata Manager for Page (${pageId})`)
                .addBody(htmlFormElement)
                .addFooterButton(viewerButton)
                .addFooterCloseButton()
                .addFooterButton(submitButton)
                .show();

        }

        document.querySelectorAll(".combo_metadata_item").forEach((metadataControlItem) => {

            metadataControlItem.addEventListener("click", function (event) {
                event.preventDefault();
                if(!('JSINFO' in window)){
                    throw new Error("JSINFO is not available")
                }
                const JSINFO = window.JSINFO;
                void openMetadataManager(JSINFO.id)
                    .catch(e => {
                        if (e instanceof Error) {
                            console.error(e.stack)
                        } else {
                            console.error(e.toString())
                        }
                    });
            });

        });
    }
)
;

