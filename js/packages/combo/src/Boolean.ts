

export default class Boolean {

    static toBoolean(value: any) {
        if (typeof value === "boolean") {
            return value;
        }
        return (value === 'true');
    }
}
