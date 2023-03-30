import { Interfaces } from "./Interfaces";
export default class Browser {
    /**
     *
     * @param element
     * @return {boolean}
     */
    static hasWindowGuard(element: Element): void;
    static hasWindow(element: Element): boolean;
    static getWindow(element: Element): Window;
    static formDataToObject(formData: any): Interfaces;
}
