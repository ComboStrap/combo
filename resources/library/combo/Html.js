import {customAlphabet, nanoid} from "nanoid";

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
        /**
         * Shorthand selector does not support numbers
         * as first position
         * The alphabet does not allow them then
         */
        const nanoid = customAlphabet('abcdefghijklmnopqrstuvwxyz', 10)
        return nanoid();
    }

    static toEntities(text) {
        let entities = [];
        for (let i = 0; i < text.length; i++) {
            let entity = `&#${text[i].charCodeAt()};`
            entities.push(entity);
        }
        return entities.join('');
    }
}
