/* global JSINFO  */

/* global DOKU_BASE */

import ComboModal from "./ComboModal";
import Browser from "./Browser";



/**
 * Fetch wrapper
 * with
 */
export class HttpRequest {


    method = "GET";


    /**
     * @param {URL} url
     */
    constructor(url) {

        this.url = url;

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
     * @return {HttpRequest}
     */
    setMethod(method) {
        this.method = method.toUpperCase();
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


    async getResponse() {

        let response = await fetch(this.url.toString(), {method: this.method});
        if (response.status !== 200) {
            let modal = ComboModal.createTemporary()
            modal.addBody(`Bad request:  the call ${this.url} to the backend sends back the following exit code` + response.status)
            modal.show();
        }
        return response;
    }
}
