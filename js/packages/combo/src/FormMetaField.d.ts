import { Interfaces } from "./Interfaces";
/**
 * A form field may hold:
 *   * a simple scalar value
 *   * or a table (list of values)
 */
export default class FormMetaField {
    tab: string;
    mutable: boolean;
    values: string[];
    defaultValues: string[];
    multiple: boolean;
    /**
     * Static const function
     * Waiting for the const keyword
     * to make them not mutable
     * @type {string}
     */
    static TABULAR_TYPE: string;
    static DATE_TIME: string;
    static PARAGRAPH: string;
    static BOOLEAN: string;
    children: Interfaces;
    static JSON: string;
    private readonly name;
    private type;
    private label;
    private url;
    private description;
    private parent;
    private domainValues;
    private width;
    constructor(name: string);
    getLabelAnchor(): string;
    /**
     * The form field type
     * @param {string} type
     * @return {FormMetaField}
     */
    setType(type: string): this;
    /**
     * The global label
     * (should be not null in case of tabular data)
     * @param {string} label
     * @return {FormMetaField}
     */
    setLabel(label: string): this;
    /**
     * The global Url
     * (should be not null in case of tabular data)
     * @param {string} url
     * @return {FormMetaField}
     */
    setUrl(url: string): this;
    /**
     * @param  value
     * @param defaultValue
     * @return {FormMetaField}
     */
    addValue(value: string, defaultValue: string): this;
    getType(): string;
    getLabel(): string;
    getUrl(): string;
    getName(): string;
    getDescription(): string;
    getTab(): string;
    /**
     *
     * @param json
     * @param {FormMetaField} parent
     * @return {FormMetaField}
     */
    static createFromJson(json: Interfaces, parent?: FormMetaField | null): FormMetaField;
    setMultiple(multiple: boolean): this;
    setParent(parent: FormMetaField): this;
    /**
     *
     * @param name
     * @return {FormMetaField}
     */
    static createFromName(name: string): FormMetaField;
    isMutable(): boolean;
    setTab(value: string): this;
    /**
     *
     * @param {boolean} value
     */
    setMutable(value: any): this;
    setDescription(value: string | undefined): this;
    getDefaultValue(): string;
    getValue(): string;
    getDomainValues(): any;
    setDomainValues(value: any[]): this;
    /**
     *
     * @param width - the width of the control, not of the label as it can be derived - in a tabular form, there is none, otherwise the {@link FormMetaTab.getWidth total width} of the tab minus this control width)))
     * @return {FormMetaField}
     */
    setControlWidth(width: number): this;
    getControlWidth(): number;
    getValues(): string[];
    getDefaultValues(): string[];
    /**
     *
     * @return {FormMetaField[]}
     *
     * See also the concept of list of objects
     * https://react-jsonschema-form.readthedocs.io/en/latest/usage/arrays/#arrays-of-objects
     *
     */
    getChildren(): any[];
    addChild(child: FormMetaField): this;
    toHtmlLabel(forId: string, customClass?: string): string;
    toHtmlControl(id: string | number, value?: any, defaultValue?: any): any;
    /**
     * Added to be able to add metadata to the returned Json Form
     * It has not yet all properties
     * @return
     */
    toJavascriptObject(): {
        "name": string;
        "label": string;
        "type": string;
        "description": string;
        "tab": string;
        "mutable": boolean;
        "value": string;
        "default": string;
        "url": string;
    };
    getMultiple(): boolean;
}
