import { MapString } from "./Interfaces";
export default class ComboModal {
    /**
     * HtmlElement or string
     */
    footerButtons: (string | HTMLElement)[];
    bodies: (string | HTMLElement)[];
    isBuild: boolean;
    bodyStyles: MapString;
    dialogStyles: MapString;
    showFooter: boolean;
    dialogClasses: string[];
    private readonly modalId;
    private readonly modalRootHtmlElement;
    private headerText;
    private isResetOnClose;
    private closeButton;
    private isCentered;
    private callBack;
    private bootStrapModal;
    private modalContent;
    private modalBody;
    /**
     * A valid HTML id
     * @param modalId
     */
    constructor(modalId: string);
    setHeader(headerText: string): this;
    /**
     * @param htmlBody
     * @return {ComboModal}
     */
    addBody(htmlBody: string | HTMLElement): this;
    addBodyStyle(property: string, value: string): this;
    noFooter(): this;
    addDialogStyle(property: string, value: string): this;
    addDialogClass(value: string): this;
    /**
     * @return {ComboModal}
     */
    resetOnClose(): this;
    /**
     *
     * @type HTMLButtonElement|string htmlFooter
     */
    addFooterButton(htmlFooter: HTMLButtonElement | string): this;
    /**
     *
     * @return HTMLButtonElement the close button
     */
    addFooterCloseButton(label?: string): this;
    /**
     * Center the modal
     * @return {ComboModal}
     */
    centered(): this;
    show(): void;
    dismissHide(): void;
    getModalId(): string;
    /**
     *
     * @param {function} callBack
     */
    setCallBackOnClose(callBack: () => void): this;
    /**
     * Create a modal and return the modal content element
     * @return ComboModal
     */
    static createFromId(modalId: string): ComboModal;
    /**
     * @param modalId
     * @return {ComboModal}
     */
    static getModal: (modalId: string) => ComboModal;
    /**
     * List the managed modals
     */
    static listModals: () => void;
    /**
     * Delete all modals
     */
    static resetAllModals: () => void;
    getElement(): HTMLElement;
    /**
     * Calling the {@link show} function will build the
     * modal, if this is the case, you can't build it anymore
     * you need to {@link reset} it and recreate it if needed
     * @return {boolean}
     */
    wasBuild(): boolean;
    setCentered(bool: boolean): this;
    resetIfBuild(): this;
    reset(): void;
    /**
     * Build the modal
     */
    build(): void;
    static getOrCreate(modalId: string): ComboModal;
}
