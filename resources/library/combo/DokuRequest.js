/* global JSINFO  */
/* global DOKU_BASE */

class DokuAjaxUrl {

    constructor(call) {
        this.url = new URL(DOKU_BASE + 'lib/exe/ajax.php', window.location.href);

        this.url.searchParams.set("call", call);

        this.url.searchParams.set("id", JSINFO.id);
    }


    setProperty(key, value) {
        this.url.searchParams.set(key, value);
        return this;
    }

    toString() {
        return this.url.toString();
    }
}

class DokuAjaxRequest {


    method = "GET";

    constructor(call) {

        this.url = new DokuAjaxUrl(call);

    }

    async getJson() {

        let response = await fetch(this.url.toString(), {method: this.method});

        if (response.status !== 200) {
            console.log('Bad request, status Code is: ' + response.status);
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
    }

    setProperty(key, value) {
        this.url.setProperty(key, value);
        return this;
    }

    /**
     * Create a ajax call
     * @return DokuAjaxRequest
     */
    static createDokuRequest = function (call) {

        return new DokuAjaxRequest(call);
    }
}
