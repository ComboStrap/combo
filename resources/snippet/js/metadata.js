window.addEventListener("DOMContentLoaded", function () {


    /**
     * A pointer to the created modals
     */
    let comboModals = {};

    /**
     *
     * @param modalId
     * @return {ComboModal}
     */
    let getComboModal = function (modalId) {
        return comboModals[modalId];
    }
    /**
     * Create a modal and return the modal content element
     * @return ComboModal
     */
    let createComboModal = function (modalId) {

        let modal = new ComboModal(modalId);
        comboModals[modalId] = modal;
        return modal;
    }

    /**
     * Create a ajax call
     * @return ComboAjaxCall
     */
    let createGetCall = function (pageId) {

        let comboCall = new ComboAjaxCall(pageId);
        comboCall.setMethod("GET");
        return comboCall;
    }

    let createMetaField = function (properties) {
        return new FormMetaField(properties);
    }



    class ComboAjaxUrl {

        constructor(pageId) {
            this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);

            this.url.searchParams.set("call", "combo-meta-manager");
            if (pageId !== undefined) {
                this.id = pageId;
            } else {
                this.id = JSINFO.id;
            }
            this.url.searchParams.set("id", this.id);
        }


        setProperty(key, value) {
            this.url.searchParams.set(key, value);
            return this;
        }

        toString() {
            return this.url.toString();
        }
    }


    class ComboAjaxCall {


        method = "GET";

        constructor(pageId) {

            this.url = new ComboAjaxUrl(pageId);

        }

        async getJson() {

            let response = await fetch(this.url.toString(), {method: this.method});

            if (response.status !== 200) {
                console.log('Bad request, status Code is: ' + response.status);
                return {};
            }

            // Parses response data to JSON
            //   * response.json()
            //   * response.text()
            // are promise, you need to pass them to a callback to get the value
            return response.json();

        }

        setMethod(method) {
            this.method = method;
        }

        setProperty(key, value) {
            this.url.setProperty(key, value);
        }
    }

    /**
     * Represent a form meta element with:
     *   * a label
     *   * and a control element
     * Without the values
     */
    class FormMetaField {


        constructor(properties) {

            this.properties = properties;
        }

        /**
         * @return string
         */
        getLabel() {

            let label = this.properties["label"];
            if (label === undefined) {
                return this.getName()
                    .split(/_|-/)
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(" ");
            }
            return label;
        }

        getLabelUrl() {
            let label = this.properties["url"];
            if (label === undefined) {
                return this.getLabel();
            }
            return label;
        }

        getHtmlLabel(forId, customClass) {
            let label = this.getLabelUrl();
            let classLabel = "";
            if (this.getType() === "boolean") {
                classLabel = "form-check"
            } else {
                classLabel = "col-form-label";
            }
            return `<label for="${forId}" class="${customClass} ${classLabel}">${label}</label>`
        }

        getHtmlControl(id, value, defaultValue) {

            let metadataType = this.properties["type"];
            let mutable = this.properties["mutable"];
            let domainValues = this.properties["domain-values"];
            let disabled;
            let htmlElement;

            /**
             * The creation of the form element
             */
            if (domainValues !== undefined) {

                /**
                 * Select element
                 * @type {string}
                 */
                htmlElement = "select";
                let defaultValueHtml = "";
                if (defaultValue !== undefined) {
                    defaultValueHtml = ` (${defaultValue})`;
                }

                htmlElement = `<select class="form-select" aria-label="${this.getLabel()}" name="${this.getName()}">`;
                let selected = "";
                if (value === null) {
                    selected = "selected";
                }
                htmlElement += `<option value="" ${selected}>Default${defaultValueHtml}</option>`;
                for (let selectValue of domainValues) {
                    if (selectValue === value) {
                        selected = "selected";
                    } else {
                        selected = "";
                    }
                    htmlElement += `<option value="${selectValue}" ${selected}>${selectValue}</option>`;
                }
                htmlElement += `</select>`;
                return htmlElement;


            } else {

                let htmlPlaceholder = `placeholder="Enter a ${this.getLabel()}"`;
                if (!(defaultValue === null || defaultValue === undefined)) {
                    htmlPlaceholder = `placeholder="${defaultValue}"`;
                }
                let htmlValue = "";
                let inputType;
                let name = this.getName();

                /**
                 * With disable, the data is not in the form
                 */
                if (mutable !== undefined && mutable === false) {
                    disabled = "disabled";
                } else {
                    disabled = "";
                }

                /**
                 * Input Element
                 * @type {string}
                 */
                let htmlTag = "input";
                let htmlClass = "form-control";
                let checked = "";

                /**
                 * Type ?
                 */
                switch (metadataType) {
                    case "datetime":
                        inputType = "datetime-local";
                        if (value !== null) {
                            value = value.slice(0, 19);
                        }
                        if (value !== null) {
                            htmlValue = `value="${value}"`;
                        }
                        break;
                    case "paragraph":
                        htmlTag = "textarea";
                        if (value !== null) {
                            htmlValue = `${value}`;
                        }
                        break;
                    case "boolean":
                        inputType = "checkbox";
                        htmlClass = "form-check-input";
                        if (value === defaultValue) {
                            checked = "checked"
                        }
                        htmlValue = `value="${value}"`;
                        htmlPlaceholder = "";
                        break;
                    case "line":
                    default:
                        inputType = "text";
                        if (!(value === null || value === undefined)) {
                            htmlValue = `value="${value}"`;
                        }
                }

                switch (htmlTag) {
                    case "textarea":
                        htmlElement = `<textarea id="${id}" name="${name}" class="${htmlClass}" rows="3" ${htmlPlaceholder} >${htmlValue}</textarea>`;
                        break;
                    default:
                    case "input":
                        htmlElement = `<input type="${inputType}" name="${name}" class="${htmlClass}" id="${id}" ${htmlPlaceholder} ${htmlValue} ${checked} ${disabled}>`;
                        break;

                }
                return htmlElement;
            }
        }

        getType() {
            return this.properties["type"];
        }

        getName() {
            return this.properties["name"];
        }
    }

    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager, pageId) {
        let modalViewerId = toHtmlId(`combo_metadata_viewer_${pageId}`);
        let modalViewer = getComboModal(modalViewerId);
        if (modalViewer === undefined) {
            modalViewer = createComboModal(modalViewerId);
            modalViewer.setHeader("Metadata Viewer");
            let viewerCall = createGetCall(pageId);
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
     * @return {ComboAjaxUrl}
     */
    function createAjaxUrl(pageId) {
        return new ComboAjaxUrl(pageId);
    }

    /**
     *
     * @param {ComboModal} managerModal
     * @param pageId
     * @return {Promise<*>}
     */
    async function fetchAndBuildMetadataManager(managerModal, pageId) {

        let call = createGetCall(pageId);
        let jsonMetaDataObject = await call.getJson();

        /**
         * Parsing the data
         * before creating the header and body modal
         */
        let formFieldsByTab = {};
        let dataFields = jsonMetaDataObject["fields"];
        for (const dataField of dataFields) {

            let dataFieldType = dataField["type"];
            let dataFieldTab = dataField["tab"];

            let fieldMetas = [];
            let fieldValues = [];
            let fieldGroup = "";
            switch (dataFieldType) {
                case "tabular":
                    let columns = dataField["columns"];
                    for (const column of columns) {
                        let metaField = createMetaField(column);
                        fieldMetas.push(metaField);
                    }
                    fieldValues = dataField["values"];
                    fieldGroup = dataField["url"];
                    break
                default:
                    fieldMetas = createMetaField(dataField);
                    fieldValues = [dataField["value"], dataField["default"]];
            }

            if (formFieldsByTab[dataFieldTab] === undefined) {
                formFieldsByTab[dataFieldTab] = [];
            }
            formFieldsByTab[dataFieldTab].push({
                "type": dataFieldType,
                "group": fieldGroup,
                "metas": fieldMetas,
                "values": fieldValues
            });

        }

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
        let defaultTab = tabsMeta[0];
        // Merge the tab found in the tab metas and in the field
        // to be sure to let no error
        let tabsFromField = Object.keys(formFieldsByTab);
        let tabsFromMeta = Object.keys(tabsMeta);
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
            htmlTabNavs += `<li class="nav-item">
<button
    class="nav-link ${activeClass}"
    id="${tabNavId}"
    type="button"
    role="tab"
    aria-selected = "${ariaSelected}"
    aria-controls = "${tabPanId}"
    data-bs-toggle = "tab"
    data-bs-target = "#${tabPanId}" >${tabLabel}</button>
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
            for (let formField of formFieldsByTab[tab]) {

                let datatype = formField["type"];
                switch (datatype) {
                    case "tabular":
                        let group = formField["group"];
                        htmlTabPans += `<div class="row mb-3 text-center">${group}</div>`;
                        let colsControlElement = formField["fields"];
                        let rows = formField["values"];
                        let colImageTag = "4";
                        let colImagePath = "8";
                        htmlTabPans += `<div class="row mb-3">`;
                        for (const colControlElement of colsControlElement) {
                            if (colControlElement.getName() === "image-tag") {
                                htmlTabPans += `<div class="col-sm-${colImageTag} text-center">`;
                            } else {
                                htmlTabPans += `<div class="col-sm-${colImagePath} text-center">`;
                            }
                            htmlTabPans += colControlElement.getLabelUrl();
                            htmlTabPans += `</div>`;
                        }
                        htmlTabPans += `</div>`;
                        for (let i = 0; i < rows.length; i++) {
                            let row = rows[i];
                            htmlTabPans += `<div class="row mb-3">`;
                            for (let i = 0; i < colsControlElement.length; i++) {
                                let colControlElement = colsControlElement[i];
                                elementIdCounter++;
                                let elementId = `combo-metadata-manager-control-${elementIdCounter}`;
                                if (colControlElement.getName() === "image-tag") {
                                    htmlTabPans += `<div class="col-sm-${colImageTag}">`;
                                } else {
                                    htmlTabPans += `<div class="col-sm-${colImagePath}">`;
                                }
                                htmlTabPans += colControlElement.getHtml(elementId, row[i]["value"], row[i]["default"]);
                                htmlTabPans += `</div>`;
                            }
                            htmlTabPans += `</div>`;
                        }
                        break;
                    default:
                        elementIdCounter++;
                        let elementId = `combo-metadata-manager-control-${elementIdCounter}`;
                        /**
                         * @type FormMetaField
                         */
                        let htmlElement = formField["fields"];
                        let labelHtml = htmlElement.getHtmlLabel(elementId, `col-sm-${leftColSize}`);
                        let value = formField["values"];
                        let controlHtml = htmlElement.getHtmlControl(elementId, value[0], value[1])
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
        let endpoint = createAjaxUrl(pageId).toString();
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
        let managerModal = getComboModal(modalManagerId);

        if (managerModal === undefined) {
            managerModal = createComboModal(modalManagerId);
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

