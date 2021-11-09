'use strict';

import FormMetaField from "./FormMetaField";
import FormMetaTab from "./FormMetaTab";
import Html from "./Html";

/**
 * Represent the top meta
 * data from a form
 */
export default class FormMeta {

    label;


    formFields = {};
    name;
    tabs = {};


    constructor(name) {

        if (name == null) {
            throw new Error("Name of a form should not be null");
        }
        this.name = name;
    }

    /**
     * @return string
     */
    getLabel() {

        let label = this.properties["label"];

        return this.label;
    }

    getLabelUrl() {
        let label = this.properties["url"];
        if (label === undefined) {
            return this.getLabel();
        }
        return label;
    }





    getType() {
        return this.properties["type"];
    }

    getName() {
        return this.name;
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
        return 12 - this.getControlWidth();
    }


    static createFromJson(json) {
        let name = json["name"];
        let form = FormMeta.createFromName(name);
        let fields = json["fields"];
        for (let field in fields) {
            if (fields.hasOwnProperty(field)) {
                form.addFormField(FormMetaField.createFromJson(fields[field]));
            }
        }
        let tabs = json["tabs"];
        for (let tab in tabs) {
            if(tabs.hasOwnProperty(tab)) {
                form.addTab(FormMetaTab.createFromJson(tabs[tab]));
            }
        }
        return form;
    }

    static createFromName(name) {
        return new FormMeta(name);
    }

    /**
     *
     * @param {FormMetaField} formField
     * @return {FormMeta}
     */
    addFormField(formField) {
        this.formFields[formField.getName()] = formField;
        return this;
    }

    /**
     *
     * @return {FormMetaField[]}
     */
    getFields() {
        return Object.values(this.formFields);
    }

    /**
     *
     * @return {FormMetaTab[]}
     */
    getTabs() {
        return Object.values(this.tabs);
    }

    addTab(formMetaTab) {
        this.tabs[formMetaTab.getName()]=formMetaTab;
    }

    valueOf(){
        return this.getName();
    };

    getFieldsForTab(tabName) {
        return this.getFields().filter(e=>e.getName()===tabName);
    }

    toHtmlElement(formId) {

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
        let tabsMeta = this.getTabs();
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

            for (let formField of this.getFieldsForTab(tab.getName())) {

                let datatype = formField.getType();
                switch (datatype) {
                    // number of children may also work ?
                    case FormMetaField.TABULAR_TYPE:
                        let url = formField.getLabelLink();
                        htmlTabPans += `<div class="row mb-3 text-center">${url}</div>`;
                        htmlTabPans += `<div class="row mb-3">`;
                        for (const child of formField.getChildren()) {
                            let width = child.getControlWidth();
                            htmlTabPans += `<div class="col-sm-${width} text-center">`;
                            htmlTabPans += child.getLabelLink();
                            htmlTabPans += `</div>`;
                        }
                        htmlTabPans += `</div>`;
                        htmlTabPans += `<div class="row mb-3">`;
                        for (const child of formField.getChildren()) {
                            let values = child.getValues();
                            let defaultValues = child.getDefaultValues();
                            for (let i = 0; i < values.length; i++) {
                                let value = values[i];
                                let defaultValue = defaultValues[i];
                                elementIdCounter++;
                                let elementId = this.getControlId(elementIdCounter);
                                let width = child.getControlWidth();
                                htmlTabPans += `<div class="col-sm-${width}">`;
                                htmlTabPans += child.toHtmlControl(elementId, value, defaultValue);
                                htmlTabPans += `</div>`;
                            }
                        }
                        htmlTabPans += `</div>`;
                        break;
                    default:
                        elementIdCounter++;
                        let elementId = this.getControlId(elementIdCounter);
                        let labelHtml = formField.toHtmlLabel(elementId, `col-sm-${leftColSize}`);
                        let value = formField.getValue();
                        let defaultValue = formField.getValue();
                        let controlHtml = formField.toHtmlControl(elementId, value, defaultValue)
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

        let form = document.createElement("form");
        form.setAttribute("id",formId);
        form.innerHTML = `${htmlTabNavs} ${htmlTabPans}`;
        return form;
    }


}
