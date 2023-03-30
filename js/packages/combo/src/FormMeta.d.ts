import FormMetaField from "./FormMetaField";
import FormMetaTab from "./FormMetaTab";
import { Interfaces } from "./Interfaces";
/**
 * Represent the top meta
 * data from a form
 */
export default class FormMeta {
    formFields: Interfaces;
    tabs: Interfaces;
    width: number;
    private readonly name;
    private label;
    private url;
    private readonly getTabPaneId;
    private readonly getTabNavId;
    private readonly getControlId;
    constructor(formId: string);
    /**
     * @return string
     */
    getLabel(): string;
    getUrl(): string;
    getId(): string;
    /**
     * The width of the control
     * if there is no tab
     * @return {number|*}
     */
    getControlWidth(): number;
    getLabelWidth(): number;
    /**
     *
     * @param {string} formId
     * @param {Object} json
     * @return {FormMeta}
     */
    static createFromJson(formId: string, json: Interfaces): FormMeta;
    /**
     *
     * @param id
     * @return {FormMeta}
     */
    static createFromId(id: string): FormMeta;
    /**
     *
     * @param {FormMetaField} formField
     * @return {FormMeta}
     */
    addFormField(formField: FormMetaField): this;
    /**
     *
     * @return {FormMetaField[]}
     */
    getFields(): any[];
    /**
     *
     * @return {FormMetaTab[]}
     */
    getTabs(): any[];
    addTab(formMetaTab: FormMetaTab): void;
    valueOf(): string;
    getFieldsForTab(tabName: string): any[];
    toHtmlElement(): HTMLFormElement;
    setControlWidth(width: number): this;
    setLabel(label: string): void;
    setUrl(url: string): void;
}
