window.addEventListener("DOMContentLoaded", function () {


    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager, pageId) {
        let modalViewerId = toHtmlId(`combo_metadata_viewer_${pageId}`);
        let modalViewer = combo.getModal(modalViewerId);
        if (modalViewer === undefined) {
            modalViewer = combo.createModal(modalViewerId);
            modalViewer.setHeader("Metadata Viewer");
            let viewerCall = combo.createGetCall(pageId);
            viewerCall.setProperty("type", "viewer");
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
     * @param pageId
     * @return {Promise<*>}
     */
    async function fetchAndBuildMetadataManager(managerModal, pageId) {

        let call = combo.createGetCall(pageId);
        let jsonMetaDataObject = await call.getJson();

        /**
         * Parsing the data
         * before creating the header and body modal
         */
        let formFieldsByTab = combo.toFormFieldsByTabs(jsonMetaDataObject["fields"]);


        /**
         * Header
         */
        managerModal.setHeader("Metadata Manager");

        /**
         * Creating the Body
         * (Starting with the tabs)
         */
        let htmlTabNavs = '<ul class="nav nav-tabs mb-3">';
        let activeClass;
        let ariaSelected;
        this.getTabPaneId = function (tab) {
            let htmlId = tab.replace(" ", "-");
            return `combo-metadata-tab-pane-${htmlId}`;
        }
        this.getTabNavId = function (tab) {
            let htmlId = tab.replace(" ", "-");
            return `combo-metadata-tab-nav-${htmlId}`;
        }
        let tabsMeta = jsonMetaDataObject["ui"]["tabs"];

        // Merge the tab found in the tab metas and in the field
        // to be sure to let no error
        let tabsFromField = Object.keys(formFieldsByTab);
        let tabsFromMeta = Object.keys(tabsMeta);
        let defaultTab = tabsFromMeta[0];
        let tabsMerged = tabsFromMeta.concat(tabsFromField.filter(element => tabsFromMeta.indexOf(element) < 0))
        for (let tab of tabsMerged) {
            if (tab === defaultTab) {
                activeClass = "active";
                ariaSelected = "true";
            } else {
                activeClass = "";
                ariaSelected = "false";
            }
            let tabLabel = tabsMeta[tab]["label"];
            let tabPanId = this.getTabPaneId(tab);
            let tabNavId = this.getTabNavId(tab);
            htmlTabNavs += `
<li class="nav-item">
<button
    class="nav-link ${activeClass}"
    id="${tabNavId}"
    type="button"
    role="tab"
    aria-selected = "${ariaSelected}"
    aria-controls = "${tabPanId}"
    data-bs-toggle = "tab"
    data-bs-target = "#${tabPanId}" >${tabLabel}
    </button>
</li>`
        }
        htmlTabNavs += '</ul>';

        /**
         * Creating the content
         * @type {string}
         */
        let htmlTabPans = "<div class=\"tab-content\">";
        let rightColSize;
        let leftColSize;
        let elementIdCounter = 0;
        for (let tab in formFieldsByTab) {
            if (!formFieldsByTab.hasOwnProperty(tab)) {
                continue;
            }
            let tabPaneId = this.getTabPaneId(tab);
            let tabNavId = this.getTabNavId(tab);
            if (tab === defaultTab) {
                activeClass = "active";
            } else {
                activeClass = "";
            }
            htmlTabPans += `<div class="tab-pane ${activeClass}" id="${tabPaneId}" role="tabpanel" aria-labelledby="${tabNavId}">`;
            let grid = tabsMeta[tab]["grid"];
            if (grid.length === 2) {
                leftColSize = grid[0];
                rightColSize = grid[1];
            } else {
                leftColSize = 3;
                rightColSize = 9;
            }

            for (/** @type {ComboFormField} **/ let formField of formFieldsByTab[tab]) {

                let datatype = formField.getType();
                switch (datatype) {
                    case "tabular":
                        let group = formField.getGroup();
                        htmlTabPans += `<div class="row mb-3 text-center">${group}</div>`;
                        let colsMeta = formField.getMetas();
                        let rows = formField.getValues();
                        let colImageTag = "4";
                        let colImagePath = "8";
                        htmlTabPans += `<div class="row mb-3">`;
                        for (const colMeta of colsMeta) {
                            if (colMeta.getName() === "image-tag") {
                                htmlTabPans += `<div class="col-sm-${colImageTag} text-center">`;
                            } else {
                                htmlTabPans += `<div class="col-sm-${colImagePath} text-center">`;
                            }
                            htmlTabPans += colMeta.getLabelUrl();
                            htmlTabPans += `</div>`;
                        }
                        htmlTabPans += `</div>`;
                        for (let i = 0; i < rows.length; i++) {
                            let row = rows[i];
                            htmlTabPans += `<div class="row mb-3">`;
                            for (let i = 0; i < colsMeta.length; i++) {
                                let colControlElement = colsMeta[i];
                                elementIdCounter++;
                                let elementId = `combo-metadata-manager-control-${elementIdCounter}`;
                                if (colControlElement.getName() === "image-tag") {
                                    htmlTabPans += `<div class="col-sm-${colImageTag}">`;
                                } else {
                                    htmlTabPans += `<div class="col-sm-${colImagePath}">`;
                                }
                                htmlTabPans += colControlElement.getHtmlControl(elementId, row[i].value, row[i].default);
                                htmlTabPans += `</div>`;
                            }
                            htmlTabPans += `</div>`;
                        }
                        break;
                    default:
                        elementIdCounter++;
                        let elementId = `combo-metadata-manager-control-${elementIdCounter}`;
                        let formMetaField = formField.getMeta();
                        let labelHtml = formMetaField.getHtmlLabel(elementId, `col-sm-${leftColSize}`);
                        let value = formField.getValue();
                        let controlHtml = formMetaField.getHtmlControl(elementId, value.value, value.default)
                        htmlTabPans += `
<div class="row mb-3">
    ${labelHtml}
    <div class="col-sm-${rightColSize}">${controlHtml}</div>
</div>
`;
                }

            }
            htmlTabPans += "</div>"
        }
        htmlTabPans += "</div>";

        let formId = managerModal.getId() + "_form";
        let endpoint = combo.createAjaxUrl(pageId).toString();
        managerModal.addBody(`<form id="${formId}" method="post" action="${endpoint}">${htmlTabNavs} ${htmlTabPans} </form>`);

        /**
         * Footer
         */
        let viewerButton = document.createElement("button");
        viewerButton.classList.add("btn", "btn-link", "text-primary", "text-decoration-bone", "fs-6", "text-muted");
        viewerButton.style.setProperty("font-weight", "300");
        viewerButton.textContent = "Viewer";
        viewerButton.addEventListener("click", function (event) {
            managerModal.dismiss();
            openMetaViewer(managerModal);
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

    let toHtmlId = function (s) {
        return s.replace(/[_\s:\/\\]/g, "-");
    }

    let openMetadataManager = async function (pageId) {

        let modalManagerId = toHtmlId(`combo_metadata_manager_page_${pageId}`);
        let managerModal = combo.getModal(modalManagerId);

        if (managerModal === undefined) {
            managerModal = combo.createModal(modalManagerId);
            managerModal = await fetchAndBuildMetadataManager(managerModal, pageId);
        }
        managerModal.show();


    }


    document.querySelectorAll(".combo_metadata_item").forEach((metadataControlItem) => {

        metadataControlItem.addEventListener("click", function (event) {
            event.preventDefault();
            void openMetadataManager(JSINFO.id).catch(console.error);
        });

    });
});

