export default class ComboDate {
    private date;
    constructor(date: Date);
    static createFromIso(isoString: string): ComboDate;
    toSqlTimestampString(): string;
}
