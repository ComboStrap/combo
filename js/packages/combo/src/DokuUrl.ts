import {HttpRequest} from "./HttpRequest";

export class DokuUrl {

    static AJAX = "AJAX";
    static RUNNER = "RUNNER";
    static CALL = "call";
    static EDIT = "edit";
    static SHOW = 'show';
    static FETCH = 'fetch';
    private readonly url: URL;

    constructor(type: string) {

        let DOKU_BASE = (window as any).DOKU_BASE;
        let JSINFO = (window as any).JSINFO;

        switch (type) {
            case DokuUrl.AJAX:
                // @ts-ignore
                this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
                this.url.searchParams.set("id", JSINFO.id);
                break;
            case DokuUrl.RUNNER:
                // @ts-ignore
                this.url = new URL(DOKU_BASE + 'lib/exe/taskrunner.php', window.location.href);
                this.url.searchParams.set("id", JSINFO.id);
                break;
            case DokuUrl.FETCH:
                this.url = new URL(DOKU_BASE + 'lib/exe/fetch.php', window.location.href);
                break;
            case DokuUrl.EDIT:
                this.url = new URL(DOKU_BASE + 'doku.php', window.location.href);
                this.url.searchParams.set("do", "edit");
                this.url.searchParams.set("id", JSINFO.id);
                break;
            case DokuUrl.SHOW:
                this.url = new URL(DOKU_BASE + 'doku.php', window.location.href);
                this.url.searchParams.set("id", JSINFO.id);
                break;
            default:
                throw new Error(`The type ${type} is unknown`);
        }

    }

    setProperty(key: string, value: string) {
        this.url.searchParams.set(key, value);
        return this;
    }

    toString() {
        return this.url.toString();
    }

    getCall() {
        return this.url.searchParams.get(DokuUrl.CALL);
    }


    toRequest() {
        return new HttpRequest(this.url);
    }

    static createAjax(call: string) {
        return (new DokuUrl(this.AJAX))
            .setProperty(DokuUrl.CALL, call);
    }

    static createRunner() {
        return new DokuUrl(this.RUNNER);
    }

    static createFetch(id: string, drive: string) {
        let dokuUrl = new DokuUrl(this.FETCH);
        if (typeof id === 'undefined') {
            throw new Error("The media id is mandatory")
        }
        dokuUrl.setProperty("media", id);
        if (typeof drive !== 'undefined') {
            dokuUrl.setProperty("drive", drive);
        }
        return dokuUrl;
    }

    static createEdit(id: string) {
        let dokuUrl = new DokuUrl(this.EDIT);
        if (typeof id !== 'undefined') {
            dokuUrl.setProperty("id", id);
        }
        return dokuUrl;
    }
}
