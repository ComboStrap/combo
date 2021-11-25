/* global combo */
// noinspection JSUnresolvedVariable

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
            let viewerCall = combo
                .createDokuRequest(viewerCallEndpoint)
                .setProperty("id", pageId)
            let jsonFormMeta = await viewerCall.getJson();


            let formViewerId = combo.toHtmlId(`combo-metadata-viewer-form-${pageId}`);
            let formHtmlElement = combo.createFormFromJson(formViewerId, jsonFormMeta).toHtmlElement();

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
                let response = await combo.createDokuRequest(viewerCallEndpoint)
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

            combo.createTemporaryModal()
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
            let modalManagerId = combo.toHtmlId(`combo-meta-manager-page-${pageId}`);
            let managerModal = combo.getOrCreateModal(modalManagerId)
            if (managerModal.wasBuild()) {
                managerModal.show();
                return;
            }

            /**
             * The viewer
             * We create it here because it needs to be reset if there is a submit on the manager.
             */
            let modalViewerId = combo.toHtmlId(`combo-metadata-viewer-modal-${pageId}`);
            let modalViewer = combo.getOrCreateModal(modalViewerId);

            /**
             * Creating the form
             */
            let formMetadata = await combo
                .createDokuRequest(metaManagerCall)
                .setProperty("id", pageId)
                .getJson();
            /**
             * Add the page runtime cache metadata field
             */
            let cachePageInfo = document.querySelector('script[type="application/combo+cache+json"]');
            if (cachePageInfo !== null) {
                let cachePageJsonString = cachePageInfo
                    .innerText
                    .trim()
                    .slice("/*<![CDATA[*/".length)
                    .slice(0, -("/*!]]>*/".length));
                let cachePageJson = JSON.parse(cachePageJsonString);
                for (let slot in cachePageJson) {
                    if (!cachePageJson.hasOwnProperty(slot)) {
                        continue;
                    }

                    let formatResults = cachePageJson[slot];
                    for (let formatResult in formatResults) {
                        if (!formatResults.hasOwnProperty(formatResult)) {
                            continue;
                        }
                        let name = `cache_slot_${slot}_${formatResult}`;
                        let result = formatResults[formatResult];
                        let styledFormatResult;
                        if (formatResult === "i") {
                            styledFormatResult = "Parse Instructions"
                        } else {
                            styledFormatResult = formatResult.charAt(0).toUpperCase() + formatResult.slice(1);
                        }
                        formMetadata["fields"][name] =
                            combo.createFormMetaField(name)
                                .setMutable(false)
                                .setLabel(`Cache Hit for ${styledFormatResult} ${slot}`)
                                .setType("text")
                                .setDescription(`${styledFormatResult} Slot Cache Information for the slot (${slot})`)
                                .setTab("cache")
                                .addValue(`${result["result"]} (${result["mtime"]})`)
                                .toJavascriptObject();
                    }
                }
            }
            let formId = combo.toHtmlId(`${modalManagerId}-form`);
            let form = combo.createFormFromJson(formId, formMetadata);
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
                let response = await combo.createDokuRequest(metaManagerCall)
                    .setMethod("post")
                    .sendFormDataAsJson(formData);
                managerModal.reset();
                modalViewer.reset();
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

