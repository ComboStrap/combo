import {customAlphabet} from "nanoid";

export default class Html {

    static toHtmlId(s: string | number) {
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

    static createRandomIdWithPrefix(prefix: string) {

        return prefix + "-" + this.createRandomId();
    }

    static toEntities(text: string) {
        let entities = [];
        for (let i = 0; i < text.length; i++) {
            let entity = `&#${text.charCodeAt(i)};`
            entities.push(entity);
        }
        return entities.join('');
    }
}
