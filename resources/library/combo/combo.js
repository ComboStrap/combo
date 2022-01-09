import FormMeta from "./FormMeta";
import Html from "./Html";
import ComboModal from "./ComboModal";
import DokuAjaxRequest from "./DokuRequest";
import FormMetaField from "./FormMetaField";

window['combo'] = class combo {

    static toHtmlId(name) {
        return Html.toHtmlId(name)
    }


    static getOrCreateModal(id) {
        return ComboModal.getOrCreate(id);
    }

    static removeAllModals() {
        ComboModal.resetAllModals();
    }

    static createDokuRequest(callName) {
        return DokuAjaxRequest.createDokuRequest(callName);
    }

    static createFormMetaField(name) {
        return FormMetaField.createFromName(name);
    }

    /**
     *
     * @param formId
     * @param json
     * @return {FormMeta}
     */
    static createFormFromJson(formId, json) {
        return FormMeta.createFromJson(formId, json);
    }

    /**
     * @return {ComboModal}
     */
    static createTemporaryModal() {
        return ComboModal.createTemporary();
    }

}

