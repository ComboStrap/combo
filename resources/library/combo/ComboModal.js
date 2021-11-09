/**
 * A pointer to the created modals
 * Private
 */
let comboModals = {};

export default class ComboModal {

    /**
     * @type HTMLDivElement
     */
    modalFooter;

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
        this.bootStrapModal = new bootstrap.Modal(this.modalRoot, options);
    }

    setHeader(headerText) {
        let html = `
<div class="modal-header">
    <h5 class="modal-title">${headerText}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
`;
        this.modalContent.insertAdjacentHTML('afterbegin', html);
    }

    addBody(htmlBody) {

        this.modalBody.innerHTML = htmlBody;

    }

    createModalFooter() {
        this.modalFooter = document.createElement("div");
        this.modalFooter.classList.add("modal-footer");
        this.modalContent.appendChild(this.modalFooter);
    }

    /**
     *
     * @type HTMLButtonElement|string htmlFooter
     */
    addFooterButton(htmlFooter) {


        if (this.modalFooter === undefined) {
            this.createModalFooter();
        }
        if (typeof htmlFooter === 'string' || htmlFooter instanceof String) {
            this.modalFooter.insertAdjacentHTML('beforeend', htmlFooter);
        } else {
            this.modalFooter.appendChild(htmlFooter);
        }


    }

    /**
     *
     * @return HTMLButtonElement the close button
     */
    addFooterCloseButton(label = "Close") {
        let closeButton = document.createElement("button");
        closeButton.classList.add("btn", "btn-secondary")
        closeButton.innerText = label;
        let modal = this;
        closeButton.addEventListener("click", function () {
            modal.bootStrapModal.hide();
        });
        this.addFooterButton(closeButton);
        return closeButton;
    }

    show() {


        this.bootStrapModal.show();
        /**
         * Init the tooltip if any
         */
        let tooltipSelector = `#${this.modalId} [data-bs-toggle="tooltip"]`;
        document.querySelectorAll(tooltipSelector).forEach(el => new bootstrap.Tooltip(el));
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
    static createFromName(modalName) {
        let modal = new ComboModal(modalName);
        comboModals[modalName] = modal;
        return modal;
    }

    /**
     * @param modalName
     * @return {ComboModal}
     */
    static getModal = function (modalName) {
        return comboModals[modalName];
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
}
