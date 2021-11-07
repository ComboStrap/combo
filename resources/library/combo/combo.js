(function (combo) {

    /**
     * A form field may hold:
     *   * a simple scalar value
     *   * or a table (list of values)
     */
    class ComboFormField {

        /**
         * The form field type
         * @param {string} type
         * @return {ComboFormField}
         */
        setType(type) {
            this.type = type;
            return this;
        }

        /**
         * The global label
         * (should be not null in case of tabular data)
         * @param {string} label
         * @return {ComboFormField}
         */
        setLabel(label) {
            this.label = label;
            return this;
        }

        /**
         * The global Url
         * (should be not null in case of tabular data)
         * @param {string} url
         * @return {ComboFormField}
         */
        setUrl(url) {
            this.url = url;
            return this;
        }

        /**
         * @param {FormMetaField[]} metas - the type of values (equivalent to column metadata for a table)
         * @return {ComboFormField}
         */
        setMetas(metas) {
            this.metas = metas;
            return this;
        }

        /**
         * @param {FormMetaField} meta - the type of value
         * @return {ComboFormField}
         */
        setMeta(meta) {
            this.meta = meta;
            return this;
        }

        /**
         * @return {FormMetaField[]}
         */
        getMetas() {
            return this.metas;
        }

        /**
         * @return {FormMetaField}
         */
        getMeta() {
            return this.meta;
        }

        /**
         *
         * Type:
         *   * A single value
         *   * or an array of an array of values (ie table)
         * @param {{value: string, default: string}[][]} values - the values attached
         * @return {ComboFormField}
         */
        setValues(values) {
            this.values = values;
            return this;
        }

        /**
         * @param {{value: string, default: string}} value
         * @return {ComboFormField}
         */
        setValue(value) {
            this.value = value;
            return this;
        }

        /**
         *
         * @return {{value: string, default: string}[][]}
         */
        getValues() {
            return this.values;
        }

        /**
         *
         * @return {{value: string, default: string}}
         */
        getValue() {
            return this.value;
        }

        getType() {
            return this.type;
        }

        getLabel() {
            return this.label;
        }

        getUrl() {
            return this.url;
        }

    }

    class ComboModal {

        /**
         * @type HTMLDivElement
         */
        modalFooter;

        /**
         * A valid HTML id
         * @param modalId
         */
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
            let tooltipSelector = `#${this.modalId} [data-bs-toggle="tooltip"]`;
            document.querySelectorAll(tooltipSelector).forEach(el => new bootstrap.Tooltip(el));
        }

        dismiss() {
            this.bootStrapModal.hide();
        }

        getId() {
            return this.modalId;
        }
    }

    class DokuAjaxUrl {

        constructor(call) {
            this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);

            this.url.searchParams.set("call", call);
            this.url.searchParams.set("id", JSINFO.id);
        }


        setProperty(key, value) {
            this.url.searchParams.set(key, value);
            return this;
        }

        toString() {
            return this.url.toString();
        }
    }

    class DokuAjaxRequest {


        method = "GET";

        constructor(call) {

            this.url = new DokuAjaxUrl(call);

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
            return this;
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

            if (properties == null) {
                throw new Error("Properties of a form meta field should not be null");
            }
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

        /**
         * The width of the control
         * is the most imporant as it can
         * be used to determine the column width
         * in case of tabular data
         * @return {number|*}
         */
        getControlWidth() {
            let width = this.properties["width"];
            if (width !== undefined) {
                return width;
            } else {
                return 8;
            }
        }

        getLabelWidth() {
            return 12 - this.getLabelWidth();
        }


    }

    /**
     * Create a ajax call
     * @return DokuAjaxRequest
     */
    combo.createDokuRequest = function (call) {

        return new DokuAjaxRequest(call);
    }

    combo.createMetaField = function (properties) {
        return new FormMetaField(properties);
    }

    /**
     * A pointer to the created modals
     */
    let comboModals = {};

    /**
     *
     * @param modalId
     * @return {ComboModal}
     */
    combo.getModal = function (modalId) {
        return comboModals[modalId];
    }
    /**
     * Create a modal and return the modal content element
     * @return ComboModal
     */
    combo.createModal = function (modalId) {

        let modal = new ComboModal(modalId);
        comboModals[modalId] = modal;
        return modal;
    }

    /**
     * List the managed modals
     */
    combo.listModals = function () {
        console.log(Object.keys(comboModals).join(", "));
    }

    /**
     * Delete all modals
     */
    combo.destroyAllModals = function () {
        Object.keys(comboModals).forEach(modalId => {
            document.getElementById(modalId).remove();
        })
        comboModals = {};
    }

    /**
     *
     * @param dataFields
     * @return {{FormField[]}}
     */
    let toFormFieldsByTabs = function (dataFields) {

        const formFieldsByTab = {};
        for (const dataField of dataFields) {

            let dataFieldType = dataField["type"];
            let dataFieldTab = dataField["tab"];

            if (formFieldsByTab[dataFieldTab] === undefined) {
                formFieldsByTab[dataFieldTab] = [];
            }


            switch (dataFieldType) {
                case "tabular":
                    let label = dataField["label"];
                    let url = dataField["url"];
                    let columns = dataField["columns"];
                    let fieldMetas = [];
                    if (columns === undefined) {
                        throw new Error(`The columns should be defined for the tabular field (${label})`);
                    }
                    for (const column of columns) {
                        if (column === null) {
                            throw new Error(`A column of the ${label} field is null`);
                        }
                        let metaField = combo.createMetaField(column);
                        fieldMetas.push(metaField);
                    }

                    let fieldValues = dataField["values"];
                    formFieldsByTab[dataFieldTab].push(
                        createFormField()
                            .setType(dataFieldType)
                            .setLabel(label)
                            .setUrl(url)
                            .setMetas(fieldMetas)
                            .setValues(fieldValues)
                    );
                    break
                default:
                    let fieldMeta = combo.createMetaField(dataField);
                    let fieldValue = {
                        value: dataField["value"],
                        default: dataField["default"]
                    };
                    formFieldsByTab[dataFieldTab].push(
                        createFormField()
                            .setType(dataFieldType)
                            .setMeta(fieldMeta)
                            .setValue(fieldValue)
                    );
                    break;
            }


        }
        return formFieldsByTab;
    }

    combo.toHtmlId = function (s) {
        /**
         * A point is also replaced otherwise you
         * can't use the id as selector in CSS
         */
        return s
            .toString() // in case of number
            .replace(/[_.\s:\/\\]/g, "-");
    }

    combo.toForm = function (formId, jsonMetaDataObject) {

        debugger;
        let formFieldsByTab = toFormFieldsByTabs(jsonMetaDataObject["fields"]);
        /**
         * Creating the Body
         * (Starting with the tabs)
         */
        let htmlTabNavs = '<ul class="nav nav-tabs mb-3">';
        let activeClass;
        let ariaSelected;
        this.getTabPaneId = function (id) {
            let htmlId = combo.toHtmlId(id);
            return `${formId}-tab-pane-${htmlId}`;
        }
        this.getTabNavId = function (id) {
            let htmlId = combo.toHtmlId(id);
            return `${formId}-tab-nav-${htmlId}`;
        }
        this.getControlId = function (id) {
            let htmlId = combo.toHtmlId(id);
            return `${formId}-control-${htmlId}`;
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
                        let url = formField.getUrl();
                        htmlTabPans += `<div class="row mb-3 text-center">${url}</div>`;
                        let colsMeta = formField.getMetas();
                        let rows = formField.getValues();

                        htmlTabPans += `<div class="row mb-3">`;
                        for (const colMeta of colsMeta) {
                            let width = colMeta.getControlWidth();
                            htmlTabPans += `<div class="col-sm-${width} text-center">`;
                            htmlTabPans += colMeta.getLabelUrl();
                            htmlTabPans += `</div>`;
                        }
                        htmlTabPans += `</div>`;
                        for (let i = 0; i < rows.length; i++) {
                            let row = rows[i];
                            htmlTabPans += `<div class="row mb-3">`;
                            for (let i = 0; i < colsMeta.length; i++) {
                                let metaField = colsMeta[i];
                                elementIdCounter++;
                                let elementId = this.getControlId(elementIdCounter);
                                let width = metaField.getControlWidth();
                                htmlTabPans += `<div class="col-sm-${width}">`;
                                htmlTabPans += metaField.getHtmlControl(elementId, row[i].value, row[i].default);
                                htmlTabPans += `</div>`;
                            }
                            htmlTabPans += `</div>`;
                        }
                        break;
                    default:
                        elementIdCounter++;
                        let elementId = this.getControlId(elementIdCounter);
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

        return `<form id="${formId}">${htmlTabNavs} ${htmlTabPans}</form>`;
    }

    let createFormField = function () {
        return new ComboFormField();
    }

})(window.combo = window.combo || {});
