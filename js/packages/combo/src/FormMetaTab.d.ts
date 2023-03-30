import { Interfaces } from "./Interfaces";
export default class FormMetaTab {
    private readonly name;
    private width;
    private label;
    private widthField;
    private widthLabel;
    constructor(name: string);
    getName(): string;
    setWidth(width: number): this;
    /**
     * The width of the tab
     */
    getWidth(): number;
    static createFromJson(json: Interfaces): FormMetaTab;
    setLabel(label: string): this;
    getLabel(): string;
    setWidthField(width: number): this;
    setWidthLabel(width: number): this;
    getLabelWidth(): number;
    getFieldWidth(): number;
    static createFromName(name: string): FormMetaTab;
}
