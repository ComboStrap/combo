
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


let combo = {};

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
 * @param {{}} dataFields
 * @return {{FormField[]}}
 */
let toFormFieldsByTabs = function (dataFields) {





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

    let formFieldsByTab = FormMeta.createFromJson(jsonMetaDataObject);
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

        for (/** @type {FormMetaField} **/ let formField of formFieldsByTab[tab]) {

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
    return new FormMetaField();
}

module.exports = combo;
