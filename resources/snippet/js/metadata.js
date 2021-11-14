/* global combo */
// noinspection JSUnresolvedVariable

window.addEventListener("DOMContentLoaded", function () {

    const metaManagerCall = "combo-meta-manager";

    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager, pageId) {


        let viewerCall = combo
            .createDokuRequest(metaManagerCall)
            .setProperty("id", pageId)
            .setProperty("type", "viewer");
        let jsonFormMeta = await viewerCall.getJson();


        let modalViewerId = combo.toHtmlId(`combo-metadata-viewer-modal-${pageId}`);
        // noinspection JSVoidFunctionReturnValueUsed
        let modal = combo.getModal(modalViewerId);
        if (modal === undefined) {

            let formViewerId = combo.toHtmlId(`combo-metadata-viewer-form-${pageId}`);
            let form = combo.createFormFromJson(formViewerId, jsonFormMeta).toHtmlElement();

            modal = combo.createModal(modalViewerId)
                .setParent(modalManager)
                .setHeader("Metadata Viewer")
                .addBody(`<p>The metadata viewer shows you the content of the metadadata file (ie all metadata managed by ComboStrap or not):</p>`)
                .addBody(form);
        }
        modal.show();


    }


    /**
     *
     * @param {ComboModal} managerModal
     * @param formMetadata
     * @param pageId
     * @return {ComboModal}
     */
    function buildMetadataManager(managerModal, formMetadata, pageId) {

        /**
         * Header
         */
        managerModal.setHeader(`Metadata Manager for Page (${pageId})`);

        /**
         * Adding the form
         */
        let formId = `${managerModal.getId()}-form`;
        let form = combo.createFormFromJson(formId, formMetadata);
        let htmlFormElement = form.toHtmlElement();
        managerModal.addBody(htmlFormElement);

        /**
         * Footer
         */
        let viewerButton = document.createElement("button");
        viewerButton.classList.add("btn", "btn-link", "text-primary", "text-decoration-bone", "fs-6", "text-muted");
        viewerButton.style.setProperty("font-weight", "300");
        viewerButton.textContent = "Viewer";
        viewerButton.addEventListener("click", async function () {
            managerModal.dismissHide();
            await openMetaViewer(managerModal, pageId);
        });
        managerModal.addFooterButton(viewerButton);
        managerModal.addFooterCloseButton();
        let submitButton = document.createElement("button");
        submitButton.classList.add("btn", "btn-primary");
        submitButton.setAttribute("type", "submit");
        submitButton.setAttribute("form", formId);
        submitButton.innerText = "Submit";
        submitButton.addEventListener("click", async function (event) {
            event.preventDefault();
            let formData = new FormData(htmlFormElement);
            console.log("Submitted");
            let response = await combo.createDokuRequest(metaManagerCall)
                .setMethod("post")
                .sendFormDataAsJson(formData);
            let modalMessage = [];
            if (response.status !== 200) {
                modalMessage.push(`Error, unable to save. (return code: ${response.status})`);
            }
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
            combo.getOrCreateChildModal(managerModal)
                .centered()
                .addBody(modalMessage.join("<br>"))
                .show();
        })
        managerModal.addFooterButton(submitButton);

        return managerModal;
    }


    let openMetadataManager = async function (pageId) {

        let modalManagerId = combo.toHtmlId(`combo-metadata-manager-page-${pageId}`);
        let managerModal = combo.getModal(modalManagerId);

        if (managerModal === undefined) {
            managerModal = combo.createModal(modalManagerId);
            let call = combo
                .createDokuRequest(metaManagerCall)
                .setProperty("id", pageId);
            let formMetadata = await call.getJson();
            managerModal = buildMetadataManager(managerModal, formMetadata, pageId);
        }
        managerModal.show();


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
});

