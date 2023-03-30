import FormMeta from "./FormMeta";
import Html from "./Html";
import ComboModal from "./ComboModal";
import {DokuUrl} from "./DokuUrl";
import FormMetaField from "./FormMetaField";
import ComboDate from "./ComboDate";
import {Interfaces} from "./Interfaces";

export default class combo {

    static toHtmlId(name: string | number) {
        return Html.toHtmlId(name)
    }

    static comboDate = ComboDate;
    static DokuUrl = DokuUrl;

    static getOrCreateModal(id: string) {
        return ComboModal.getOrCreate(id);
    }

    static removeAllModals() {
        ComboModal.resetAllModals();
    }


    static createAjaxUrl(callName: string) {
        return DokuUrl.createAjax(callName);
    }

    static getRunnerUrl() {
        return DokuUrl.createRunner();
    }

    static createFormMetaField(name: string) {
        return FormMetaField.createFromName(name);
    }

    /**
     *
     * @param formId
     * @param json
     * @return {FormMeta}
     */
    static createFormFromJson(formId: string, json: Interfaces) {
        return FormMeta.createFromJson(formId, json);
    }

    /**
     * @return {ComboModal}
     */
    static createTemporaryModal() {
        return ComboModal.createTemporary();
    }

}

