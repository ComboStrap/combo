/**
 * Fetch wrapper
 * with
 */
export declare class HttpRequest {
    method: string;
    private readonly url;
    constructor(url: URL);
    getJson(): Promise<any>;
    getText(): Promise<string>;
    /**
     * @param {string} method
     * @return {HttpRequest}
     */
    setMethod(method: string): this;
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
    sendFormDataAsJson(formData: FormData): Promise<Response>;
    getResponse(): Promise<Response>;
}
