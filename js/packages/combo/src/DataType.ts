export default class DataType {
    static isString(value: any) {
        return typeof value === 'string' || value instanceof String
    }
}
