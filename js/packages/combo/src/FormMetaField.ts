import Boolean from "./Boolean";
import Logger from "./Logger";
import Html from "./Html";
import {AnyObject} from "./AnyObject";
import DataType from "./DataType";


/**
 * A form field may hold:
 *   * a simple scalar value
 *   * or a table (list of values)
 */
export default class FormMetaField {


    tab = "unknown";
    mutable = true;
    values: string[] = [];
    defaultValues: string[] = [];
    multiple = false;

    /**
     * Static const function
     * Waiting for the const keyword
     * to make them not mutable
     * @type {string}
     */
    static TABULAR_TYPE = "tabular";
    static DATE_TIME = "datetime";
    static PARAGRAPH = "paragraph";
    static BOOLEAN = "boolean";

    children: AnyObject = {};
    static JSON = "json";
    private readonly name: string;
    private type: string | undefined;
    private label: string | undefined;
    private url: string | undefined;
    private description: string | undefined;
    private parent: FormMetaField | undefined;
    private domainValues: any;
    private width: number | undefined;


    constructor(name: string) {
        this.name = name;
    }

    getLabelAnchor() {
        if (this.getUrl() === undefined && this.getDescription() === undefined) {
            return this.getLabel();
        }
        let url = this.getUrl();
        if (this.getUrl() === undefined) {
            url = "#";
            return `
<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="${this.getDescription()}">
    <a href="${url}" title="${this.getDescription()}" style="text-decoration:none;pointer-events: none;">${this.getLabel()}</a>
</span>`;
        } else {
            return `<a href="${url}" title="${this.getDescription()}" data-bs-toggle="tooltip" style="text-decoration:none" target="_blank">${this.getLabel()}</a>`;
        }
    }

    /**
     * The form field type
     * @param {string} type
     * @return {FormMetaField}
     */
    setType(type: string) {
        this.type = type;
        return this;
    }

    /**
     * The global label
     * (should be not null in case of tabular data)
     * @param {string} label
     * @return {FormMetaField}
     */
    setLabel(label: string) {
        this.label = label;
        return this;
    }

    /**
     * The global Url
     * (should be not null in case of tabular data)
     * @param {string} url
     * @return {FormMetaField}
     */
    setUrl(url: string) {
        this.url = url;
        return this;
    }


    /**
     * @param  value
     * @param defaultValue
     * @return {FormMetaField}
     */
    addValue(value: string, defaultValue: string) {
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
                .split(/[_\-]/)
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
     * @param {FormMetaField} parent
     * @return {FormMetaField}
     */
    static createFromJson(json: AnyObject, parent: FormMetaField | null = null) {
        if (!json.hasOwnProperty("name")) {
            Logger.getLogger().error("To create a form meta field, the name property is mandatory.");
        }
        let name: string = json["name"] as string;
        let formMetaField = FormMetaField.createFromName(name);
        if (parent != null) {
            formMetaField.setParent(parent);
        }

        let value: any;
        let valueDefault: any;
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
                case "multiple":
                    formMetaField.setMultiple(jsonValue);
                    continue;
                case "width":
                    formMetaField.setControlWidth(jsonValue);
                    continue;
                case "children":
                    let jsonChildren = jsonValue;
                    for (let jsonChildProp in jsonChildren) {
                        if (!jsonChildren.hasOwnProperty(jsonChildProp)) {
                            continue;
                        }
                        let child = FormMetaField.createFromJson(jsonChildren[jsonChildProp], formMetaField);
                        formMetaField.addChild(child);
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
                    formMetaField.addValue(element, '');
                }
            })
        }
        return formMetaField;
    }

    setMultiple(multiple: boolean) {
        this.multiple = multiple;
        return this;
    }

    setParent(parent: FormMetaField) {
        this.parent = parent;
        return this;
    }

    /**
     *
     * @param name
     * @return {FormMetaField}
     */
    static createFromName(name: string) {
        return new FormMetaField(name);
    }

    isMutable() {
        return this.mutable;
    }

    setTab(value: string) {
        this.tab = value;
        return this;
    }

    /**
     *
     * @param {boolean} value
     */
    setMutable(value: any) {
        this.mutable = Boolean.toBoolean(value);
        return this;
    }

    setDescription(value: string | undefined) {
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

    setDomainValues(value: any[]) {
        if (!Array.isArray(value)) {
            console.error(`The domains values should be an array. (${value}) is not an array`);
            return;
        }
        this.domainValues = value;
        return this;
    }

    /**
     *
     * @param width - the width of the control, not of the label as it can be derived - in a tabular form, there is none, otherwise the {@link FormMetaTab.getWidth total width} of the tab minus this control width)))
     * @return {FormMetaField}
     */
    setControlWidth(width: number) {
        this.width = width;
        return this;
    }

    getControlWidth() {
        if (this.width === undefined && this.parent !== undefined) {
            return 12 / this.parent.getChildren().length;
        }
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
     *
     * See also the concept of list of objects
     * https://react-jsonschema-form.readthedocs.io/en/latest/usage/arrays/#arrays-of-objects
     *
     */
    getChildren() {
        return Object.values(this.children);
    }

    addChild(child: FormMetaField) {
        this.children[child.getName()] = child;
        return this;
    }

    toHtmlLabel(forId: string, customClass?: string) {
        let label = this.getLabelAnchor();
        let classLabel;
        if (this.getType() === FormMetaField.BOOLEAN) {
            classLabel = "form-check-label"
        } else {
            classLabel = "col-form-label";
        }
        if (typeof customClass !== 'undefined') {
            classLabel = `${customClass} ${classLabel}`
        }
        return `<label for="${forId}" class="${classLabel}">${label}</label>`
    }

    toHtmlControl(id: string | number, value: any = null, defaultValue: any = null) {

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

            let multiple = "";
            if (this.getMultiple()) {
                multiple = "multiple";
            }

            htmlElement = `<select class="form-select" aria-label="${this.getLabel()}" name="${this.getName()}" id="${id}" ${multiple}>`;
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

            /**
             * Placeholder default
             * @type {string}
             */
            let placeholderValue = `No value`;
            if (mutable) {
                placeholderValue = `Enter a ${this.getLabel()}`;
            }
            // value
            if (!(defaultValue === null || defaultValue === undefined)) {
                if (DataType.isString(defaultValue)) {
                    // json data for instance
                    placeholderValue = Html.toEntities(defaultValue);
                } else {
                    placeholderValue = defaultValue;
                }
            }
            let htmlPlaceholder = `placeholder="${placeholderValue}"`;

            let htmlValue = "";
            let inputType;
            let name = this.getName();

            /**
             * With disable, the data is not in the form
             */
            if (mutable !== undefined && !mutable) {
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
             * Text area
             */
            let rows = 3;

            /**
             * Type ?
             */
            switch (metadataType) {
                case FormMetaField.DATE_TIME:
                    inputType = "datetime-local";
                    if (value !== null) {
                        value = value.slice(0, 19);
                        htmlValue = `value="${value}"`;
                    } else {
                        if (!mutable && defaultValue !== null) {
                            defaultValue = defaultValue.slice(0, 19);
                            htmlValue = `value="${defaultValue}"`;
                        }
                    }
                    htmlPlaceholder = ""; // does not apply to local date time

                    break;
                case FormMetaField.PARAGRAPH:
                    htmlTag = "textarea";
                    if (value !== null) {
                        value = Html.toEntities(value);
                        htmlValue = `${value}`;
                    }
                    break;
                case FormMetaField.JSON:
                    htmlTag = "textarea";
                    if (value !== null) {
                        if (typeof value === 'object') {
                            value = JSON.stringify(value, null, 2);
                        }
                        value = Html.toEntities(value);
                        htmlValue = `${value}`;
                    }
                    rows = 15;
                    break;
                case FormMetaField.BOOLEAN:
                    inputType = "checkbox";
                    htmlClass = "form-check-input";
                    if (defaultValue !== null) {
                        htmlValue = `value="${defaultValue}"`;
                        if (value === defaultValue) {
                            checked = "checked"
                        }
                    } else {
                        if (value === "on") {
                            checked = "checked"
                        }
                    }

                    htmlPlaceholder = "";
                    break;
                default:
                    inputType = "text";
                    if (!(value === null || value === undefined)) {
                        htmlValue = `value="${value}"`;
                    }
            }

            switch (htmlTag) {
                case "textarea":
                    htmlElement = `<textarea id="${id}" name="${name}" class="${htmlClass}" rows="${rows}" ${htmlPlaceholder} ${disabled}>${htmlValue}</textarea>`;
                    break;
                default:
                case "input":
                    htmlElement = `<input type="${inputType}" name="${name}" class="${htmlClass}" id="${id}" ${htmlPlaceholder} ${htmlValue} ${checked} ${disabled}/>`;
                    break;

            }
            return htmlElement;
        }

    }

    /**
     * Added to be able to add metadata to the returned Json Form
     * It has not yet all properties
     * @return
     */
    toJavascriptObject() {
        return {
            "name": this.getName(),
            "label": this.getLabel(),
            "type": this.getType(),
            "description": this.getDescription(),
            "tab": this.getTab(),
            "mutable": this.isMutable(),
            "value": this.getValue(),
            "default": this.getDefaultValue(),
            "url": this.getUrl()
        }
    }

    getMultiple() {
        return this.multiple;
    }
}
