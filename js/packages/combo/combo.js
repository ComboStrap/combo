import FormMeta from "./FormMeta";
import Html from "./Html";
import ComboModal from "./ComboModal";
import {DokuAjaxRequest, DokuUrl} from "./DokuRequest";
import FormMetaField from "./FormMetaField";
import ComboDate from "./ComboDate";

window['combo'] = class combo {

    static toHtmlId(name) {
        return Html.toHtmlId(name)
    }

    static comboDate = ComboDate;
    static DokuUrl = DokuUrl;

    static getOrCreateModal(id) {
        return ComboModal.getOrCreate(id);
    }

    static removeAllModals() {
        ComboModal.resetAllModals();
    }

    static createDokuRequest(callName) {
        return DokuAjaxRequest.createDokuRequest(callName);
    }

    static getRunnerUrl() {
        return DokuUrl.createRunner();
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

