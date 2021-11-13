import FormMeta from "./FormMeta";
import Html from "./Html";
import ComboModal from "./ComboModal";
import DokuAjaxRequest from "./DokuRequest";

window['combo'] = class combo {

    static toHtmlId(name) {
        return Html.toHtmlId(name)
    }

    static getModal(id) {
        return ComboModal.getModal(id);
    }

    static removeAllModals() {
        ComboModal.removeAllModals();
    }

    static createModal(id) {
        return ComboModal.createFromId(id);
    }

    static createDokuRequest(callName) {
        return DokuAjaxRequest.createDokuRequest(callName);
    }

    static createFormFromJson(formId, json) {
        return FormMeta.createFromJson(formId, json);
    }

    /**
     * @return {ComboModal}
     */
    static createTemporaryModal() {
        return ComboModal.createTemporary();
    }

    static createChildModal(parentModal) {
        return combo.createTemporaryModal()
            .setParent(parentModal);
    }
}

