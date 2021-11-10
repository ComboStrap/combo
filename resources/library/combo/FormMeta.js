'use strict';

import FormMetaField from "./FormMetaField";
import FormMetaTab from "./FormMetaTab";
import Html from "./Html";
import Logger from "./Logger";

/**
 * Represent the top meta
 * data from a form
 */
export default class FormMeta {

    formFields = {};
    tabs = {};
    width = 8;


    constructor(id) {

        if (id == null) {
            throw new Error("The if of the form should not be null");
        }
        this.name = id;
    }

    /**
     * @return string
     */
    getLabel() {
        return this.label;
    }

    getUrl() {
        return this.url;
    }

    getId() {
        return this.name;
    }

    /**
     * The width of the control
     * if there is no tab
     * @return {number|*}
     */
    getControlWidth() {
        return this.width;
    }

    getLabelWidth() {
        return 12 - this.getControlWidth();
    }


    static createFromJson(formId, json) {
        let form = FormMeta.createFromId(formId);
        for (let prop in json) {
            if (!json.hasOwnProperty(prop)) {
                continue;
            }
            let value = json[prop];
            switch (prop) {
                case "fields":
                    let fields = value;
                    for (let field in fields) {
                        if (fields.hasOwnProperty(field)) {
                            form.addFormField(FormMetaField.createFromJson(fields[field]));
                        }
                    }
                    continue;
                case "tabs":
                    let tabs = value;
                    for (let tab in tabs) {
                        if (tabs.hasOwnProperty(tab)) {
                            form.addTab(FormMetaTab.createFromJson(tabs[tab]));
                        }
                    }
                    break;
                case "width":
                    form.setControlWidth(value);
                    break;
                case "label":
                    form.setLabel(value);
                    break;
                case "url":
                    form.setUrl(value);
                    break;
                default:
                    Logger.getLogger().error(`The form property (${prop}) is unknown`);
            }


        }
        return form;
    }

    static createFromId(id) {
        return new FormMeta(id);
    }

    /**
     *
     * @param {FormMetaField} formField
     * @return {FormMeta}
     */
    addFormField(formField) {
        this.formFields[formField.getName()] = formField;
        // Be sure to have a tab for each field
        if (!this.tabs.hasOwnProperty(formField.getTab())) {
            this.tabs[formField.getTab()] = FormMetaTab.createFromName(formField.getTab());
        }
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
        this.tabs[formMetaTab.getName()] = formMetaTab;
    }

    valueOf() {
        return this.getId();
    };

    getFieldsForTab(tabName) {
        return this.getFields().filter(e => e.getTab() === tabName);
    }

    toHtmlElement() {

        let formId = this.getId();

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
            rightColSize = tab.getFieldWidth();

            for (let formField of this.getFieldsForTab(tab.getName())) {

                let children = formField.getChildren();
                switch (children.length) {
                    case 0:
                        elementIdCounter++;
                        let elementId = this.getControlId(elementIdCounter);
                        let labelHtml = formField.toHtmlLabel(elementId, `col-sm-${leftColSize}`);
                        let value = formField.getValue();
                        let defaultValue = formField.getDefaultValue();
                        let controlHtml = formField.toHtmlControl(elementId, value, defaultValue)
                        htmlTabPans += `
<div class="row mb-3">
    ${labelHtml}
    <div class="col-sm-${rightColSize}">${controlHtml}</div>
</div>
`;
                        break;
                    default:
                        let url = formField.getLabelLink();
                        htmlTabPans += `<div class="row mb-3 text-center">${url}</div>`;
                        htmlTabPans += `<div class="row mb-3">`;
                        let rows = 0;
                        for (const child of formField.getChildren()) {
                            let width = child.getControlWidth();
                            htmlTabPans += `<div class="col-sm-${width} text-center">`;
                            htmlTabPans += child.getLabelLink();
                            htmlTabPans += `</div>`;
                            let valuesLength = child.getValues().length;
                            if (valuesLength > rows) {
                                rows = valuesLength;
                            }
                        }
                        htmlTabPans += `</div>`;

                        for (let i = 0; i < rows; i++) {
                            htmlTabPans += `<div class="row mb-3">`;
                            for (const child of formField.getChildren()) {
                                let value = child.getValues()[i];
                                let defaultValue = child.getDefaultValues()[i];
                                elementIdCounter++;
                                let elementId = this.getControlId(elementIdCounter);
                                let width = child.getControlWidth();
                                htmlTabPans += `<div class="col-sm-${width}">`;
                                htmlTabPans += child.toHtmlControl(elementId, value, defaultValue);
                                htmlTabPans += `</div>`;
                            }
                            htmlTabPans += `</div>`;
                        }

                        break;

                }

            }
            htmlTabPans += "</div>"
        }
        htmlTabPans += "</div>";

        let form = document.createElement("form");
        form.setAttribute("id", formId);
        form.innerHTML = `${htmlTabNavs} ${htmlTabPans}`;
        return form;
    }


    setControlWidth(width) {
        this.width = width;
        return this;
    }

    setLabel(label) {
        this.label = label;
    }

    setUrl(url) {
        this.url = url;
    }

}
