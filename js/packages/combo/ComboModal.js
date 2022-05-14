/**
 * A pointer to the created modals
 * Private
 */
import Html from "./Html";
import {Modal, Tooltip, Tab} from "bootstrap";
import Logger from "./Logger";

/**
 *
 * @type {Object.<string, ComboModal>}
 */
let comboModals = {};

export default class ComboModal {


    footerButtons = [];
    bodies = [];
    isBuild = false;
    bodyStyles = {};
    dialogStyles = {};
    showFooter = true;
    dialogClasses = [];

    /**
     * A valid HTML id
     * @param modalId
     */
    constructor(modalId) {

        this.modalId = modalId;
        /**
         * We create the modal root because developers may want to add
         * event on it right away
         * @type {HTMLDivElement}
         */
        let queryElement = document.getElementById(modalId);
        if (queryElement !== null) {
            Logger.getLogger().error(`The id (${modalId}) given to create a modal was already used by an element in the DOM. We have reused it.`)
            this.modalRootHtmlElement = queryElement;
            this.reset();
        } else {
            this.modalRootHtmlElement = document.createElement("div");
            this.modalRootHtmlElement.setAttribute("id", this.modalId);
            this.modalRootHtmlElement.classList.add("modal", "fade");
            // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
            // modalRoot.setAttribute("tabindex", "-1");
            this.modalRootHtmlElement.setAttribute("aria-hidden", "true");
        }

    }

    setHeader(headerText) {
        this.headerText = headerText;
        return this;
    }

    /**
     * @param htmlBody
     * @return {ComboModal}
     */
    addBody(htmlBody) {

        this.bodies.push(htmlBody);
        return this;

    }

    addBodyStyle(property, value) {

        this.bodyStyles[property] = value;
        return this;

    }

    noFooter() {
        this.showFooter = false;
        return this;
    }

    addDialogStyle(property, value) {

        this.dialogStyles[property] = value;
        return this;

    }

    addDialogClass(value) {

        this.dialogClasses.push(value);
        return this;

    }

    /**
     * @return {ComboModal}
     */
    resetOnClose() {
        this.isResetOnClose = true;
        return this;
    }


    /**
     *
     * @type HTMLButtonElement|string htmlFooter
     */
    addFooterButton(htmlFooter) {

        this.footerButtons.push(htmlFooter);
        return this;
    }

    /**
     *
     * @return HTMLButtonElement the close button
     */
    addFooterCloseButton(label = "Close") {
        this.closeButton = document.createElement("button");
        this.closeButton.classList.add("btn", "btn-secondary")
        this.closeButton.innerHTML = label;
        let modal = this;
        this.closeButton.addEventListener("click", function () {
            modal.bootStrapModal.hide();
        });
        this.addFooterButton(this.closeButton);
        return this;
    }

    /**
     * Center the modal
     * @return {ComboModal}
     */
    centered() {
        this.isCentered = true;
        return this;
    }

    show() {

        if (this.modalRootHtmlElement == null) {
            throw new Error("This modal has no HTML element, you can't use it anymore");
        }

        if (!this.isBuild) {
            this.build();
        }

        /**
         * Reset on close ?
         * Included tabs does not work anymore
         * for whatever reason
         */
        if (this.isResetOnClose === true) {
            let comboModal = this;
            this.getElement().addEventListener('hidden.bs.modal', function () {
                /**
                 * the event is only dispatch on the root element, not all modal
                 */
                comboModal.reset();
            });
        }

        /**
         * Callback (Parent Child Relationship)
         */
        if (this.callBack !== undefined) {
            if (this.closeButton !== undefined) {
                let modal = this;
                this.closeButton.addEventListener("click", function () {
                    /**
                     * Two modals cannot be open at the same time
                     * https://getbootstrap.com/docs/5.0/components/modal/#toggle-between-modals
                     */
                    modal.dismissHide();
                    modal.callBack();
                });
            }
        }

        this.bootStrapModal.show();


    }

    dismissHide() {
        if (this.bootStrapModal !== undefined) {
            this.bootStrapModal.hide();
        }
    }

    getModalId() {
        return this.modalId;
    }

    /**
     *
     * @param {function} callBack
     */
    setCallBackOnClose(callBack) {
        this.callBack = callBack;
        return this;
    }


    /**
     * Create a modal and return the modal content element
     * @return ComboModal
     */
    static createFromId(modalId) {
        let modal = new ComboModal(modalId);
        comboModals[modalId] = modal;
        return modal;
    }

    /**
     * @param modalId
     * @return {ComboModal}
     */
    static getModal = function (modalId) {

        if (modalId in comboModals) {
            return comboModals[modalId];
        } else {
            return null;
        }
    }

    /**
     * List the managed modals
     */
    static listModals = function () {
        console.log(Object.keys(comboModals).join(", "));
    }

    /**
     * Delete all modals
     */
    static resetAllModals = function () {
        for (let prop in comboModals) {
            if (comboModals.hasOwnProperty(prop)) {
                let modal = comboModals[prop];
                modal.reset();
            }
        }
    }

    /**
     *
     * @return {ComboModal}
     */
    static createTemporary() {
        return this.createFromId(Html.createRandomId());
    }

    getElement() {
        return this.modalRootHtmlElement;
    }

    /**
     * Calling the {@link show} function will build the
     * modal, if this is the case, you can't build it anymore
     * you need to {@link reset} it and recreate it if needed
     * @return {boolean}
     */
    wasBuild() {
        return this.isBuild;
    }

    setCentered(bool){
        this.isCentered = bool;
        return this;
    }
    resetIfBuild() {
        if (this.wasBuild()) {
            this.reset();
        }
        return this;
    }

    reset() {

        // DOM
        this.modalRootHtmlElement.querySelectorAll('[data-bs-toggle="tab"]').forEach(tabTriggerElement => {
            let tab = Tab.getInstance(tabTriggerElement);
            if (tab !== null) {
                // tab are only created when the user click on them
                tab.dispose();
            }
        })

        /**
         * Bootstrap Modal
         * dispose should delete the root element
         * but it does not
         */
        if (this.bootStrapModal !== undefined) {
            this.dismissHide();
        }
        this.modalRootHtmlElement.innerHTML = "";

        /**
         * Content
         */
        this.isBuild = false;
        this.bodies = [];
        this.footerButtons = [];
        this.headerText = undefined;
    }

    /**
     * Build the modal
     */
    build() {

        this.isBuild = true;

        document.body.appendChild(this.modalRootHtmlElement);

        const modalManagerDialog = document.createElement("div");
        modalManagerDialog.classList.add(
            "modal-dialog",
            "modal-dialog-scrollable",
            "modal-lg");
        if (this.isCentered) {
            modalManagerDialog.classList.add("modal-dialog-centered")
        } else {
            // Get the modal more central but fix as we have tab and
            // we want still the mouse below the tab to be at the same position when we click
            modalManagerDialog.style.setProperty("margin", "5rem auto");
            modalManagerDialog.style.setProperty("height", "calc(100% - 9rem)");
        }
        for (let dialogStyleName in this.dialogStyles) {
            if (!this.dialogStyles.hasOwnProperty(dialogStyleName)) {
                continue;
            }
            modalManagerDialog.style.setProperty(dialogStyleName, this.dialogStyles[dialogStyleName]);
        }
        for (let dialogClass in this.dialogClasses){
            modalManagerDialog.classList.add(dialogClass);
        }
        this.modalRootHtmlElement.appendChild(modalManagerDialog);
        this.modalContent = document.createElement("div");
        this.modalContent.classList.add("modal-content");
        modalManagerDialog.appendChild(this.modalContent);

        this.modalBody = document.createElement("div");
        this.modalBody.classList.add("modal-body");
        for (let bodyStyleName in this.bodyStyles) {
            if (!this.bodyStyles.hasOwnProperty(bodyStyleName)) {
                continue;
            }
            this.modalBody.style.setProperty(bodyStyleName, this.bodyStyles[bodyStyleName]);
        }
        this.modalContent.appendChild(this.modalBody);


        /**
         * No need to use the global variable access mode (ie `bootstrap.Modal`)
         * It's created at build time
         * @type {Modal}
         */
        this.bootStrapModal = Modal.getInstance(this.modalRootHtmlElement);
        if (this.bootStrapModal === null) {
            /**
             * The bootstrap modal function
             * can only be invoked when the body element has been defined
             */
            let options = {
                "backdrop": true,
                "keyboard": true,
                "focus": true
            };
            this.bootStrapModal = new Modal(this.modalRootHtmlElement, options);
        }

        /**
         * Building the header
         */
        if (this.headerText !== undefined) {
            let headerHtml = `
<div class="modal-header">
    <h5 class="modal-title">${this.headerText}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
`;
            this.modalContent.insertAdjacentHTML('afterbegin', headerHtml);
        }

        /**
         * Building the body
         */
        for (let body of this.bodies) {
            let type = typeof body;
            switch (type) {
                case "string":
                    this.modalBody.insertAdjacentHTML('beforeend', body);
                    break;
                default:
                case "object":
                    this.modalBody.appendChild(body);
                    break;
            }
        }

        /**
         * Footer button
         */
        if(this.showFooter) {
            let modalFooter = document.createElement("div");
            modalFooter.classList.add("modal-footer");
            this.modalContent.appendChild(modalFooter);

            if (this.footerButtons.length === 0) {
                this.addFooterCloseButton();
            }

            for (let footerButton of this.footerButtons) {
                if (typeof footerButton === 'string' || footerButton instanceof String) {
                    modalFooter.insertAdjacentHTML('beforeend', footerButton);
                } else {
                    modalFooter.appendChild(footerButton);
                }
            }
        }

        /**
         * Init the tooltip if any
         */
        let tooltipSelector = `#${this.modalId} [data-bs-toggle="tooltip"]`;
        document.querySelectorAll(tooltipSelector).forEach(el => new Tooltip(el));
    }


    static getOrCreate(modalId) {
        let modal = ComboModal.getModal(modalId);
        if (modal === null) {
            modal = ComboModal.createFromId(modalId);
        }
        return modal;
    }
}
