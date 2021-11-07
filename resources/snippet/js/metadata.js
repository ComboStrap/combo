window.addEventListener("DOMContentLoaded", function () {

    const metaManagerCall = "combo-meta-manager";

    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager, pageId) {
        let modalViewerId = combo.toHtmlId(`combo-metadata-viewer-${pageId}`);
        let modalViewer = combo.getModal(modalViewerId);
        if (modalViewer === undefined) {
            modalViewer = combo.createModal(modalViewerId);
            modalViewer.setHeader("Metadata Viewer");
            let viewerCall = combo
                .createDokuRequest(metaManagerCall)
                .setProperty("id", pageId)
                .setProperty("type", "viewer");
            let json = JSON.stringify(await viewerCall.getJson(), null, 2);

            modalViewer.addBody(`
<p>The metadata viewer shows you the content of the metadadata file (ie all metadata managed by ComboStrap or not):</p>
<pre>${json}</pre>
`);
            let closeButton = modalViewer.addFooterCloseButton("Return to Metadata Manager");
            closeButton.addEventListener("click", function () {
                modalManager.show();
            });
        }
        modalViewer.show();

    }


    /**
     *
     * @param {ComboModal} managerModal
     * @param formMetadata
     * @param pageId
     * @return {Promise<*>}
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
        let form = combo.toForm(formId, formMetadata);
        managerModal.addBody(form);

        /**
         * Footer
         */
        let viewerButton = document.createElement("button");
        viewerButton.classList.add("btn", "btn-link", "text-primary", "text-decoration-bone", "fs-6", "text-muted");
        viewerButton.style.setProperty("font-weight", "300");
        viewerButton.textContent = "Viewer";
        viewerButton.addEventListener("click", function (event) {
            managerModal.dismiss();
            openMetaViewer(managerModal, pageId);
        });
        managerModal.addFooterButton(viewerButton);
        managerModal.addFooterCloseButton();
        let submitButton = document.createElement("button");
        submitButton.classList.add("btn", "btn-primary");
        submitButton.setAttribute("type", "submit");
        submitButton.setAttribute("form", formId);
        submitButton.innerText = "Submit";
        submitButton.addEventListener("click", function (event) {
            event.preventDefault();
            let formData = new FormData(document.getElementById(formId));
            console.log("Submitted");
            for (let entry of formData) {
                console.log(entry);
            }
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

