import Boolean from "./Boolean";

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

    constructor(name) {
        this.name = name;
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
            throw new Error("To create a form meta field, the name property is mandatory.")
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
                default:
                    console.error(`The property (${property}) of the form (${name}) is unknown`);
            }
        }
        formMetaField.addValue(value, valueDefault);
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

}
