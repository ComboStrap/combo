import {nanoid} from "nanoid";

export default class Html {

    static toHtmlId(s) {
        /**
         * A point is also replaced otherwise you
         * can't use the id as selector in CSS
         */
        return s
            .toString() // in case of number
            .replace(/[_.\s:\/\\]/g, "-");
    }

    static createRandomId() {
        return nanoid();
    }

}
