import { HttpRequest } from "./HttpRequest";
export declare class DokuUrl {
    static AJAX: string;
    static RUNNER: string;
    static CALL: string;
    static EDIT: string;
    static SHOW: string;
    static FETCH: string;
    private readonly url;
    constructor(type: string);
    setProperty(key: string, value: string): this;
    toString(): string;
    getCall(): string;
    toRequest(): HttpRequest;
    static createAjax(call: string): DokuUrl;
    static createRunner(): DokuUrl;
    static createFetch(id: string, drive: string): DokuUrl;
    static createEdit(id: string): DokuUrl;
}
