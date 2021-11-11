

export default class Boolean {

    static toBoolean(value) {
        if (typeof value === "boolean") {
            return value;
        }
        return (value === 'true');
    }
}
