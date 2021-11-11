/* global JSINFO  */

/* global DOKU_BASE */

import ComboModal from "./ComboModal";

class DokuAjaxUrl {

    constructor(call) {
        this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);
        this.url.searchParams.set("call", call);
        this.call = call;
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
        return this.call;
    }
}

export default class DokuAjaxRequest {


    method = "GET";

    constructor(call) {

        this.url = new DokuAjaxUrl(call);

    }

    async getJson() {

        let response = await fetch(this.url.toString(), {method: this.method});

        if (response.status !== 200) {
            let modal = ComboModal.createTemporary()
            modal.addBody(`Bad request:  the call ${this.url.getCall()} to the backend sends back the following exit code` + response.status)
            modal.show();
            return {};
        }

        // Parses response data to JSON
        //   * response.json()
        //   * response.text()
        // are promise, you need to pass them to a callback to get the value
        return response.json();

    }

    setMethod(method) {
        this.method = method;
        return this;
    }

    setProperty(key, value) {
        this.url.setProperty(key, value);
        return this;
    }

    async sendAsJson(formData) {
        let request = new XMLHttpRequest();
        let response = await fetch(this.url.toString(), {method: this.method});
        for (let entry of formData) {
            console.log(entry);
        }
        return request.send(formData);
    }

    /**
     * Create a ajax call
     * @return DokuAjaxRequest
     */
    static createDokuRequest = function (call) {

        return new DokuAjaxRequest(call);
    }
}
