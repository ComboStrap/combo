import FormMeta from "./FormMeta";
import Html from "./Html";
import ComboModal from "./ComboModal";
import DokuAjaxRequest from "./DokuRequest";

window['combo'] = class {

    static toHtmlId(name) {
        return Html.toHtmlId(name)
    }

    static getModal(id) {
        return ComboModal.getModal(id);
    }

    static destroyAllModals() {
        ComboModal.destroyAllModals();
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

}

