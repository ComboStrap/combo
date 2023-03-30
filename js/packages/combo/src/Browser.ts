import {Interfaces} from "./Interfaces";


export default class Browser {


    /**
     *
     * @param element
     * @return {boolean}
     */
    static hasWindowGuard(element: Element) {
        if(!this.hasWindow(element)) {
            throw Error("The element has no window")
        }
    }

    static hasWindow(element: Element) {
        return !(!element ||
            !element.ownerDocument ||
            !element.ownerDocument.defaultView);
    }

    static getWindow(element: Element){
        this.hasWindowGuard(element);
        return element.ownerDocument.defaultView
    }

    static formDataToObject(formData: any){
        let obj: Interfaces = {};
        for (let entry of formData) {
            let name = entry[0];
            let value = entry[1];
            if(obj.hasOwnProperty(name)){
                let actualValue = obj[name];
                if(Array.isArray(actualValue)){
                    obj[name].push(value);
                } else {
                    obj[name] = [actualValue,value];
                }
            } else {
                obj[name] = value
            }
        }
        return obj;
    }

}
