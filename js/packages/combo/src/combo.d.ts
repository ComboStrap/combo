import FormMeta from "./FormMeta";
import ComboModal from "./ComboModal";
import { DokuUrl } from "./DokuUrl";
import FormMetaField from "./FormMetaField";
import ComboDate from "./ComboDate";
import { Interfaces } from "./Interfaces";
export default class combo {
    static toHtmlId(name: string | number): string;
    static comboDate: typeof ComboDate;
    static DokuUrl: typeof DokuUrl;
    static getOrCreateModal(id: string): ComboModal;
    static removeAllModals(): void;
    static createAjaxUrl(callName: string): DokuUrl;
    static getRunnerUrl(): DokuUrl;
    static createFormMetaField(name: string): FormMetaField;
    /**
     *
     * @param formId
     * @param json
     * @return {FormMeta}
     */
    static createFormFromJson(formId: string, json: Interfaces): FormMeta;
    /**
     * @return {ComboModal}
     */
    static createTemporaryModal(): ComboModal;
}
