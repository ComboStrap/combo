/**
 * A pointer to the created modals
 * Private
 */
import Html from "./Html";
import {Modal, Tooltip} from "bootstrap";

let comboModals = {};

export default class ComboModal {

    /**
     * @type HTMLDivElement
     */
    modalFooter;
    footerButtons = [];
    bodies = [];
    isBuild = false;

    /**
     * A valid HTML id
     * @param modalId
     */
    constructor(modalId) {

        this.modalId = modalId;

        this.modalRoot = document.createElement("div");

        document.body.appendChild(this.modalRoot);
        this.modalRoot.setAttribute("id", modalId);
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
         * The modal can only be invoked when the body has been defined
         *
         */
        let options = {
            "backdrop": true,
            "keyboard": true,
            "focus": true
        };
        /**
         * No need to use the `bootstrap`
         * @type {Modal}
         */
        this.bootStrapModal = new Modal(this.modalRoot, options);
    }

    setHeader(headerText) {
        this.headerText = headerText;
    }

    addBody(htmlBody) {

        this.bodies.push(htmlBody);

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
        return closeButton;
    }

    show() {

        if (!this.isBuild) {
            this.build()
        }

        this.bootStrapModal.show();
        /**
         * Init the tooltip if any
         */
        let tooltipSelector = `#${this.modalId} [data-bs-toggle="tooltip"]`;
        document.querySelectorAll(tooltipSelector).forEach(el => new Tooltip(el));
    }

    dismiss() {
        this.bootStrapModal.hide();
    }

    getId() {
        return this.modalId;
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
        return comboModals[modalId];
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
    static destroyAllModals = function () {
        Object.keys(comboModals).forEach(modalId => {
            document.getElementById(modalId).remove();
        })
        comboModals = {};
    }

    static createTemporary() {
        return this.createFromId(Html.createRandomId());
    }

    getElement() {
        return this.modalRoot;
    }

    /**
     * Build the modal
     */
    build() {
        this.isBuild = true;
        if (this.headerText !== undefined) {
            let headerHtml = `
<div class="modal-header">
    <h5 class="modal-title">${this.headerText}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
`;
            this.modalContent.insertAdjacentHTML('afterbegin', headerHtml);
        }

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

        if (this.footerButtons.length===0){
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
}
