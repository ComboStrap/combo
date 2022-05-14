export default class ComboDate {

    constructor(date) {
        this.date = date;
    }


    static createFromIso(isoString) {
        let date = new Date(isoString);
        return new ComboDate(date)
    }

    toSqlTimestampString() {
        return `${this.date.getFullYear()}-${(this.date.getMonth() + 1).toString().padStart(2, '0')}-${this.date.getDate().toString().padStart(2, '0')} ${this.date.getHours().toString().padStart(2, '0')}:${this.date.getMinutes().toString().padStart(2, '0')}:${this.date.getSeconds().toString().padStart(2, '0')}`;
    }


}
