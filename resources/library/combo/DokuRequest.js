/* global JSINFO  */

/* global DOKU_BASE */

import ComboModal from "./ComboModal";
import Browser from "./Browser";

export class DokuUrl {

    static AJAX = "AJAX";
    static RUNNER = "RUNNER";
    static CALL = "call";

    constructor(type) {
        switch (type) {
            case DokuUrl.AJAX:
                this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
                break;
            case DokuUrl.RUNNER:
                this.url = new URL(DOKU_BASE + 'lib/exe/taskrunner.php', window.location.href);
        }
        this.url.searchParams.set("id", JSINFO.id);
    }

    setProperty(key, value) {
        this.url.searchParams.set(key, value);
        return this;
    }

    toString() {
        return this.url.toString();
    }

    getCall() {
        return this.url.searchParams.get(DokuUrl.CALL);
    }

    static createAjax(call) {
        return (new DokuUrl(this.AJAX))
            .setProperty(DokuUrl.CALL, call);
    }

    static createRunner() {
        return new DokuUrl(this.RUNNER);
    }
}

export class DokuAjaxRequest {


    method = "GET";

    constructor(call) {

        this.url = DokuUrl.createAjax(call);

    }

    async getJson() {

        let response = await this.getResponse()

        if (response.status !== 200) {
            return {};
        }

        // Parses response data to JSON
        //   * response.json()
        //   * response.text()
        // are promise, you need to pass them to a callback to get the value
        return response.json();

    }

    async getText() {

        let response = await this.getResponse();

        if (response.status !== 200) {
            return "";
        }

        // Parses response data to JSON
        //   * response.json()
        //   * response.text()
        // are promise, you need to pass them to a callback to get the value
        return response.text();

    }

    /**
     * @param {string} method
     * @return {DokuAjaxRequest}
     */
    setMethod(method) {
        this.method = method.toUpperCase();
        return this;
    }

    setProperty(key, value) {
        this.url.setProperty(key, value);
        return this;
    }

    /**
     *
     * @param formData
     * @return {Promise<Response>}
     *
     * We don't send a multipart-form-data
     * because php does not support them
     * natively if the name of the input are
     * not suffixed with `[]` (shame)
     */
    sendFormDataAsJson(formData) {

        return fetch(this.url.toString(), {
            method: this.method,
            body: JSON.stringify(Browser.formDataToObject(formData)),
            headers: {
                'Content-Type': 'application/json'
            },
        });
    }

    /**
     * Create a ajax call
     * @return DokuAjaxRequest
     */
    static createDokuRequest = function (call) {

        return new DokuAjaxRequest(call);
    }

    async getResponse() {

        let response = await fetch(this.url.toString(), {method: this.method});
        if (response.status !== 200) {
            let modal = ComboModal.createTemporary()
            modal.addBody(`Bad request:  the call ${this.url.getCall()} to the backend sends back the following exit code` + response.status)
            modal.show();
        }
        return response;
    }
}
