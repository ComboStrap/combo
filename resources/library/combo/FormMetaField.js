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
     *
     * Type:
     *   * A single value
     *   * or an array of an array of values (ie table)
     * @param {{value: string, default: string}[][]} values - the values attached
     * @return {FormMetaField}
     */
    setValues(values) {
        this.values = values;
        return this;
    }

    /**
     * @param {{value: string, default: string}} value
     * @return {FormMetaField}
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

    getTab(){
        return this.tab;
    }
    static createFromJson(json) {
        if(!json.hasOwnProperty("name")) {
            throw new Error("To create a form meta field, the name property is mandatory.")
        }
        let name = json["name"];
        let formMetaField =  FormMetaField.createFromName(name);
        for(let property in json){
            if(!json.hasOwnProperty(property)) {
                continue;
            }
            let value = json[property];
            switch (property){
                case "name":
                    continue;
                case "label":
                    formMetaField.setLabel(value);
                    continue;
                case "tab":
                    formMetaField.setTab(value);
                    continue;
                case "type":
                    formMetaField.setType(value);
                    continue;
                case "mutable":
                    formMetaField.setMutable(value);
                    continue;
                case "description":
                    formMetaField.setDescription(value);
                    continue;
                case "url":
                    formMetaField.setUrl(value);
                    continue;
                default:
                    console.error(`The property (${property}) of the form (${name}) is unknown`);
            }
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
}
