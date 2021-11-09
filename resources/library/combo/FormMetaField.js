import Boolean from "./Boolean";
import Logger from "./Logger";

/**
 * A form field may hold:
 *   * a simple scalar value
 *   * or a table (list of values)
 */
export default class FormMetaField {


    name;
    tab;
    mutable = true;
    description;
    values = [];
    defaultValues = [];
    static TABULAR_TYPE = "tabular";
    children = {};


    constructor(name) {
        this.name = name;
    }

    getLabelLink() {
        return `<a href="${this.getUrl()}" title="${this.getDescription()}" data-bs-toggle="tooltip" style="text-decoration:none">${this.getLabel()}</a>`;
    }

    /**
     * The form field type
     * @param {string} type
     * @return {FormMetaField}
     */
    setType(type) {
        this.type = type;
        return this;
    }

    /**
     * The global label
     * (should be not null in case of tabular data)
     * @param {string} label
     * @return {FormMetaField}
     */
    setLabel(label) {
        this.label = label;
        return this;
    }

    /**
     * The global Url
     * (should be not null in case of tabular data)
     * @param {string} url
     * @return {FormMetaField}
     */
    setUrl(url) {
        this.url = url;
        return this;
    }


    /**
     * @param  value
     * @param defaultValue
     * @return {FormMetaField}
     */
    addValue(value, defaultValue) {
        this.values.push(value);
        this.defaultValues.push(defaultValue);
        return this;
    }


    getType() {
        return this.type;
    }

    getLabel() {
        if (this.label === undefined) {
            return this.getName()
                .split(/_|-/)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
        }
        return this.label;
    }

    getUrl() {
        return this.url;
    }

    getName() {
        return this.name;
    }

    getDescription() {
        return this.description;
    }

    getTab() {
        return this.tab;
    }

    /**
     *
     * @param json
     * @return {FormMetaField}
     */
    static createFromJson(json) {
        if (!json.hasOwnProperty("name")) {
            Logger.getLogger.error("To create a form meta field, the name property is mandatory.");
        }
        let name = json["name"];
        let formMetaField = FormMetaField.createFromName(name);

        let value;
        let valueDefault;
        for (let property in json) {
            if (!json.hasOwnProperty(property)) {
                continue;
            }
            let jsonValue = json[property];
            switch (property) {
                case "name":
                    continue;
                case "label":
                    formMetaField.setLabel(jsonValue);
                    continue;
                case "tab":
                    formMetaField.setTab(jsonValue);
                    continue;
                case "type":
                    formMetaField.setType(jsonValue);
                    continue;
                case "mutable":
                    formMetaField.setMutable(jsonValue);
                    continue;
                case "description":
                    formMetaField.setDescription(jsonValue);
                    continue;
                case "url":
                    formMetaField.setUrl(jsonValue);
                    continue;
                case "value":
                    value = jsonValue;
                    continue;
                case "default":
                    valueDefault = jsonValue;
                    continue;
                case "domain-values":
                    formMetaField.setDomainValues(jsonValue);
                    continue;
                case "width":
                    formMetaField.setControlWidth(jsonValue);
                    continue;
                case "children":
                    let jsonChildren = jsonValue;
                    for (let jsonChild in jsonChildren) {
                        if (jsonChildren.hasOwnProperty(jsonChild)) {
                            let child = FormMetaField.createFromJson(jsonChild);
                            formMetaField.addChild(child);
                        }
                    }
                    continue;
                default:
                    Logger.getLogger().error(`The property (${property}) of the form (${name}) is unknown`);
            }
        }
        if (!Array.isArray(value)) {
            formMetaField.addValue(value, valueDefault);
        } else {
            value.forEach((element, index) => {
                let valueDefaultElement = valueDefault[index];
                if (valueDefaultElement !== undefined) {
                    formMetaField.addValue(element, valueDefaultElement);
                } else {
                    formMetaField.addValue(element);
                }
            })
        }
        return formMetaField;
    }

    static createFromName(name) {
        return new FormMetaField(name);
    }

    isMutable() {
        return this.mutable;
    }

    setTab(value) {
        this.tab = value;
        return this;
    }

    /**
     *
     * @param {boolean} value
     */
    setMutable(value) {
        this.mutable = Boolean.toBoolean(value);
        return this;
    }

    setDescription(value) {
        this.description = value;
        return this;
    }

    getDefaultValue() {
        return this.defaultValues[0];
    }

    getValue() {
        return this.values[0];
    }

    getDomainValues() {
        return this.domainValues;
    }

    setDomainValues(value) {
        if (!Array.isArray(value)) {
            console.error(`The domains values should be an array. (${value}) is not an array`);
            return;
        }
        this.domainValues = value;
        return this;
    }

    /**
     *
     * @param width - the width of the control, not of the label as it can be derived - in a tabular form, there is none, otherwise the {@link FormMetaTab.getWidth total width} of the tab minus this control width)
     * @return {FormMetaField}
     */
    setControlWidth(width) {
        this.width = width;
        return this;
    }

    getControlWidth() {
        return this.width;
    }

    getValues() {
        return this.values;
    }

    getDefaultValues() {
        return this.defaultValues;
    }

    /**
     *
     * @return {FormMetaField[]}
     */
    getChildren() {
        return Object.values(this.children);
    }

    addChild(child) {
        this.children[child.getName()] = child;
        return this;
    }

    toHtmlLabel(forId, customClass) {
        let label = this.getLabelLink();
        let classLabel = "";
        if (this.getType() === "boolean") {
            classLabel = "form-check"
        } else {
            classLabel = "col-form-label";
        }
        return `<label for="${forId}" class="${customClass} ${classLabel}">${label}</label>`
    }

    toHtmlControl(id, value, defaultValue) {

        let metadataType = this.getType();
        let mutable = this.isMutable();
        let domainValues = this.getDomainValues();
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
}
