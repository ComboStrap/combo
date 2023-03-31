import FormMeta from "./FormMeta";
import Html from "./Html";
import FluentModal from "./FluentModal";
import FluentPopOver from "./FluentPopOver";
import {DokuUrl} from "./DokuUrl";
import FormMetaField from "./FormMetaField";
import ComboDate from "./ComboDate";
import {Interfaces} from "./Interfaces";

/**
 * Export to be able to import
 * then by name
 */
export {Html, FluentModal, FluentPopOver};

/**
 * Export for components in umd
 */
export default class combo {

    static toHtmlId(name: string | number) {
        return Html.toHtmlId(name)
    }

    static comboDate = ComboDate;
    static DokuUrl = DokuUrl;
    static FluentPopOver = FluentPopOver;

    static getOrCreateModal(id: string) {
        return FluentModal.getOrCreate(id);
    }

    static removeAllModals() {
        FluentModal.resetAllModals();
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
        return FluentModal.createTemporary();
    }

}

