/**
 * A form field may hold:
 *   * a simple scalar value
 *   * or a table (list of values)
 */
export default class FormMetaField {


    constructor(name) {
        this.name = name;
    }


    name;

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

    static createFromJson(json) {
        let name = json["name"];
        return FormMetaField.createFromName(name);
    }

    static createFromName(name) {
        return new FormMetaField(name);
    }
}
