import FormMeta from "./FormMeta";
import Html from "./Html";
import FormMetaField from "./FormMetaField";



export function toForm(formId, jsonMetaDataObject) {

    let formMeta = FormMeta.createFromJson(jsonMetaDataObject);

    /**
     * Creating the Body
     * (Starting with the tabs)
     */
    let htmlTabNavs = '<ul class="nav nav-tabs mb-3">';
    let activeClass;
    let ariaSelected;
    this.getTabPaneId = function (tab) {
        let htmlId = Html.toHtmlId(tab.getName());
        return `${formId}-tab-pane-${htmlId}`;
    }
    this.getTabNavId = function (tab) {
        let htmlId = Html.toHtmlId(tab.getName());
        return `${formId}-tab-nav-${htmlId}`;
    }
    this.getControlId = function (id) {
        let htmlId = Html.toHtmlId(id);
        return `${formId}-control-${htmlId}`;
    }
    let tabsMeta = formMeta.getTabs();
    let defaultTab = tabsMeta[0];
    for (let tab of tabsMeta) {
        if (Object.is(tab, defaultTab)) {
            activeClass = "active";
            ariaSelected = "true";
        } else {
            activeClass = "";
            ariaSelected = "false";
        }
        let tabLabel = tab.getLabel();
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
    for (let tab of tabsMeta) {
        let tabPaneId = this.getTabPaneId(tab);
        let tabNavId = this.getTabNavId(tab);
        if (tab === defaultTab) {
            activeClass = "active";
        } else {
            activeClass = "";
        }
        htmlTabPans += `<div class="tab-pane ${activeClass}" id="${tabPaneId}" role="tabpanel" aria-labelledby="${tabNavId}">`;
        leftColSize = tab.getLabelWidth();
        rightColSize = tab.getLabelWidth();

        for (let formField of formMeta.getFieldsForTab(tab.getName())) {

            let datatype = formField.getType();
            switch (datatype) {
                // number of children may also work ?
                case FormMetaField.TABULAR_TYPE:
                    let url = formField.getHtmlAnchor();
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


