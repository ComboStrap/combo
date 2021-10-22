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
         * In case of tabular data, the table label
         * @param {string} group
         * @return {ComboFormField}
         */
        setGroup(group) {
            this.group = group;
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

        getGroup() {
            return this.group;
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
     * Create a ajax call
     * @return ComboAjaxCall
     */
    combo.createGetCall = function (pageId) {

        let comboCall = new ComboAjaxCall(pageId);
        comboCall.setMethod("GET");
        return comboCall;
    }

    combo.createMetaField = function (properties) {
        return new FormMetaField(properties);
    }

    /**
     *
     * @return {ComboAjaxUrl}
     */
    combo.createAjaxUrl = function (pageId) {
        return new ComboAjaxUrl(pageId);
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
    combo.toFormFieldsByTabs = function (dataFields) {

        const formFieldsByTab = {};
        for (const dataField of dataFields) {

            let dataFieldType = dataField["type"];
            let dataFieldTab = dataField["tab"];

            if (formFieldsByTab[dataFieldTab] === undefined) {
                formFieldsByTab[dataFieldTab] = [];
            }


            switch (dataFieldType) {
                case "tabular":
                    let columns = dataField["columns"];
                    let fieldMetas = [];
                    for (const column of columns) {
                        let metaField = combo.createMetaField(column);
                        fieldMetas.push(metaField);
                    }
                    let fieldValues = dataField["values"];
                    let fieldGroup = dataField["url"];
                    formFieldsByTab[dataFieldTab].push(
                        createFormField()
                            .setType(dataFieldType)
                            .setGroup(fieldGroup)
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

    let createFormField = function () {
        return new ComboFormField();
    }

})(window.combo = window.combo || {});
