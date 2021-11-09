import FormMetaField from "./FormMetaField";
import FormMetaTab from "./FormMetaTab";

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

}
