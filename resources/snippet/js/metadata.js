window.addEventListener("DOMContentLoaded", function () {


    /**
     * A pointer to the created modals
     */
    let comboModals = {};

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

    let createControlElement = function (metadata, properties) {
        return new ControlElement(metadata, properties);
    }

    class ComboModal {

        /**
         * @type HTMLDivElement
         */
        modalFooter;

        constructor(modalId) {
            this.modalId = modalId;

            this.modalRoot = document.createElement("div");

            document.body.appendChild(this.modalRoot);
            this.modalRoot.setAttribute("id", modalId);
            this.modalRoot.classList.add("modal", "fade");
            // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
            // modalRoot.setAttribute("tabindex", "-1");
            this.modalRoot.setAttribute("aria-hidden", "true");

            const modalManagerDialog = document.createElement("div");
            modalManagerDialog.classList.add(
                "modal-dialog",
                "modal-dialog-scrollable",
                "modal-fullscreen-md-down",
                "modal-lg");
            // Get the modal more central but fix as we have tab and
            // we want still the mouse below the tab when we click
            modalManagerDialog.style.setProperty("margin", "5rem auto");
            modalManagerDialog.style.setProperty("height", "calc(100% - 9rem)");
            this.modalRoot.appendChild(modalManagerDialog);
            this.modalContent = document.createElement("div");
            this.modalContent.classList.add("modal-content");
            modalManagerDialog.appendChild(this.modalContent);

            this.modalBody = document.createElement("div");
            this.modalBody.classList.add("modal-body");
            this.modalContent.appendChild(this.modalBody);

            /**
             * The modal can only be invoked when the body has been defined
             *
             */
            let options = {
                "backdrop": true,
                "keyboard": true,
                "focus": true
            };
            this.bootStrapModal = new bootstrap.Modal(this.modalRoot, options);
        }

        setHeader(headerText) {
            let html = `
<div class="modal-header">
    <h5 class="modal-title">${headerText}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
`;
            this.modalContent.insertAdjacentHTML('afterbegin', html);
        }

        addBody(htmlBody) {

            this.modalBody.innerHTML = htmlBody;

        }

        createModalFooter() {
            this.modalFooter = document.createElement("div");
            this.modalFooter.classList.add("modal-footer");
            this.modalContent.appendChild(this.modalFooter);
        }

        /**
         *
         * @type HTMLButtonElement|string htmlFooter
         */
        addFooterButton(htmlFooter) {


            if (this.modalFooter === undefined) {
                this.createModalFooter();
            }
            if (typeof htmlFooter === 'string' || htmlFooter instanceof String) {
                this.modalFooter.insertAdjacentHTML('beforeend', htmlFooter);
            } else {
                this.modalFooter.appendChild(htmlFooter);
            }


        }

        /**
         *
         * @return HTMLButtonElement the close button
         */
        addFooterCloseButton(label = "Close") {
            let closeButton = document.createElement("button");
            closeButton.classList.add("btn", "btn-secondary")
            closeButton.innerText = label;
            let modal = this;
            closeButton.addEventListener("click", function () {
                modal.bootStrapModal.hide();
            });
            this.addFooterButton(closeButton);
            return closeButton;
        }

        show() {


            this.bootStrapModal.show();
            /**
             * Init the tooltip if any
             */
            document.querySelectorAll(`#${this.modalId} [data-bs-toggle="tooltip"]`).forEach(el => new bootstrap.Tooltip(el));
        }

        dismiss() {
            this.bootStrapModal.hide();
        }
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

    class ControlElement {


        constructor(name, properties) {
            this.name = name;
            this.properties = properties;
        }

        /**
         * @return string
         */
        getLabel() {

            let label = this.properties["label"];
            if (label === undefined) {
                return this.name
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

        getHtml(id, value, defaultValue) {

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

                htmlElement = `<select class="form-select" aria-label="${this.getLabel()}">`;
                let selected = "";
                if (value === null) {
                    selected = "selected";
                }
                htmlElement += `<option ${selected}>Default${defaultValueHtml}</option>`;
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

                let htmlPlaceholder = `placeholder="${defaultValue}"`;
                let htmlValue = "";
                let inputType;
                let name = this.name;

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
                        if (value === true) {
                            checked = "checked"
                        }
                        htmlValue = `value="${value}"`;
                        break;
                    case "line":
                    default:
                        inputType = "text";
                        if (value !== null) {
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
    }

    /**
     *
     * @type ComboModal modalManager
     */
    async function openMetaViewer(modalManager, pageId) {
        let modalViewerId = `combo_metadata_viewer`;
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

    let openMetadataManager = async function (pageId) {


        let modalManagerId = `combo_metadata_manager`;
        let managerModal = getComboModal(modalManagerId);

        if (managerModal !== undefined) {
            managerModal.show();
            return;
        }

        managerModal = createComboModal(modalManagerId);

        let call = createGetCall(pageId);
        let jsonMetaDataObject = await call.getJson();


        /**
         * Parsing the data
         * before creating the header and body modal
         */
        let htmlFormElementsByTab = {};
        let metadataProperties;
        let metadataType;

        let metadataTab;
        for (const metadata in jsonMetaDataObject) {
            if (jsonMetaDataObject.hasOwnProperty(metadata)) {
                metadataProperties = jsonMetaDataObject[metadata];
                metadataType = metadataProperties["type"];
                metadataTab = metadataProperties["tab"];

                let controlElements = [];
                let values = [];
                let group = "";
                switch (metadataType) {
                    case "tabular":
                        let columns = metadataProperties["columns"];
                        for (const column of columns) {
                            let rowOfControlElements = [];
                            for (const rowMetadata in column) {
                                if (column.hasOwnProperty(rowMetadata)) {
                                    rowOfControlElements.push(createControlElement(rowMetadata, column[rowMetadata]));
                                }
                            }
                            controlElements.push(rowOfControlElements);
                        }
                        values = metadataProperties["values"];
                        group = metadataProperties["label"];
                        break
                    default:
                        controlElements = createControlElement(metadata, metadataProperties);
                        values = [metadataProperties["value"], metadataProperties["default"]];
                }

                if (htmlFormElementsByTab[metadataTab] === undefined) {
                    htmlFormElementsByTab[metadataTab] = [];
                }
                htmlFormElementsByTab[metadataTab].push({
                    "type": metadataType,
                    "group": group,
                    "elements": controlElements,
                    "values": values
                });

            }

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
        let defaultTab = "Page";
        for (let tab in htmlFormElementsByTab) {
            if (tab === defaultTab) {
                activeClass = "active";
                ariaSelected = "true";
            } else {
                activeClass = "";
                ariaSelected = "false";
            }
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
    data-bs-target = "#${tabPanId}" >${tab}</button>
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
        for (let tab in htmlFormElementsByTab) {
            let tabPaneId = this.getTabPaneId(tab);
            let tabNavId = this.getTabNavId(tab);
            if (tab === defaultTab) {
                activeClass = "active";
            } else {
                activeClass = "";
            }
            htmlTabPans += `<div class="tab-pane ${activeClass}" id="${tabPaneId}" role="tabpanel" aria-labelledby="${tabNavId}">`;
            if (tab === "Quality") {
                leftColSize = 4;
                rightColSize = 8;
            } else if (tab === "Language") {
                leftColSize = 2;
                rightColSize = 10;
            } else {
                leftColSize = 3;
                rightColSize = 9;
            }
            for (let htmlFormElement of htmlFormElementsByTab[tab]) {

                let datatype = htmlFormElement["type"];
                switch (datatype) {
                    case "tabular":
                        break;
                    default:
                        elementIdCounter++;
                        let elementId = `combo-metadata-manager-control-${elementIdCounter}`;
                        /**
                         * @type ControlElement
                         */
                        let htmlElement = htmlFormElement["elements"];
                        let labelHtml = htmlElement.getHtmlLabel(elementId, `col-sm-${leftColSize}`);
                        let value = htmlFormElement["values"];
                        let controlHtml = htmlElement.getHtml(elementId, value[0], value[1])
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

        let formId = modalManagerId + "_form";
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
            console.log("Submitted");
        })
        managerModal.addFooterButton(submitButton);
        managerModal.show();


    }


    document.querySelectorAll(".combo_metadata_item").forEach((metadataControlItem) => {

        metadataControlItem.addEventListener("click", function (event) {
            event.preventDefault();
            void openMetadataManager(JSINFO.id).catch(console.error);
        });

    });
});

