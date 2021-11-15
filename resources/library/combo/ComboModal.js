/**
 * A pointer to the created modals
 * Private
 */
import Html from "./Html";
import {Modal, Tooltip, Tab} from "bootstrap";

/**
 *
 * @type {Object.<string, ComboModal>}
 */
let comboModals = {};

export default class ComboModal {


    footerButtons = [];
    bodies = [];
    isBuild = false;

    /**
     * A valid HTML id
     * @param modalId
     */
    constructor(modalId) {

        this.modalId = modalId;
        /**
         * We create it because developers may want to add
         * event on it right away
         * @type {HTMLDivElement}
         */
        this.modalRoot = document.createElement("div");

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

    }

    /**
     *
     * @return HTMLButtonElement the close button
     */
    addFooterCloseButton(label = "Close") {
        let closeButton = document.createElement("button");
        closeButton.classList.add("btn", "btn-secondary")
        closeButton.innerHTML = label;
        let modal = this;
        closeButton.addEventListener("click", function () {
            modal.bootStrapModal.hide();
        });
        this.addFooterButton(closeButton);
        this.closeButton = closeButton;
        return closeButton;
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

        if (this.modalRoot == null) {
            throw new Error("This modal was removed, you can't use it anymore");
        }

        if (!this.isBuild) {
            this.build();
        }

        /**
         * Remove on close ?
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
         * Parent child modal
         */
        if (this.parentModal !== undefined) {
            this.parentModal.dismissHide();
            if (this.closeButton !== undefined) {
                let parentModal = this.parentModal;
                this.closeButton.addEventListener("click", function () {
                    parentModal.show();
                });
            }
        }

        this.bootStrapModal.show();


    }

    dismissHide() {
        this.bootStrapModal.hide();
    }

    getId() {
        return this.modalId;
    }

    /**
     *
     * @param {ComboModal} parentModal
     */
    setParent(parentModal) {
        this.parentModal = parentModal;
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
        return this.modalRoot;
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

    reset() {
        this.remove();
        this.modalRoot = document.createElement("div");
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

        document.body.appendChild(this.modalRoot);
        this.modalRoot.setAttribute("id", this.modalId);
        this.modalRoot.classList.add("modal", "fade");
        // Uncaught RangeError: Maximum call stack size exceeded caused by the tabindex
        // modalRoot.setAttribute("tabindex", "-1");
        this.modalRoot.setAttribute("aria-hidden", "true");

        const modalManagerDialog = document.createElement("div");
        modalManagerDialog.classList.add(
            "modal-dialog",
            "modal-dialog-scrollable",
            "modal-fullscreen-md-down",
            "modal-lg");
        if (this.isCentered) {
            modalManagerDialog.classList.add("modal-dialog-centered")
        }
        // Get the modal more central but fix as we have tab and
        // we want still the mouse below the tab when we click
        modalManagerDialog.style.setProperty("margin", "5rem auto");
        modalManagerDialog.style.setProperty("height", "calc(100% - 9rem)");
        this.modalRoot.appendChild(modalManagerDialog);
        this.modalContent = document.createElement("div");
        this.modalContent.classList.add("modal-content");
        modalManagerDialog.appendChild(this.modalContent);

        this.modalBody = document.createElement("div");
        this.modalBody.classList.add("modal-body");
        this.modalContent.appendChild(this.modalBody);

        /**
         * The bootstrap modal function
         * can only be invoked when the body element has been defined
         */
        let options = {
            "backdrop": true,
            "keyboard": true,
            "focus": true
        };
        /**
         * No need to use the global `bootstrap.Modal`
         * Created at build time
         * @type {Modal}
         */
        this.bootStrapModal = new Modal(this.modalRoot, options);

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

        /**
         * Init the tooltip if any
         */
        let tooltipSelector = `#${this.modalId} [data-bs-toggle="tooltip"]`;
        document.querySelectorAll(tooltipSelector).forEach(el => new Tooltip(el));
    }

    /**
     * @return {ComboModal}
     */
    remove() {
        // Do we have tabs
        this.getElement().querySelectorAll('[data-bs-toggle="tab"]').forEach(tabTriggerElement => {
            let tab = Tab.getInstance(tabTriggerElement);
            if (tab !== null) {
                // tab are only created when the user click on them
                tab.dispose();
            }
        })
        if (this.bootStrapModal != null) {
            this.bootStrapModal.dispose();
            this.bootStrapModal = null;
        }
        if (this.getId() in comboModals) {
            delete comboModals[this.getId()];
        }
        this.getElement().remove();
        this.modalRoot = null;
        return this;
    }

    static getOrCreate(modalId) {
        let modal = ComboModal.getModal(modalId);
        if (modal === null) {
            modal = ComboModal.createFromId(modalId);
        }
        return modal;
    }
}
