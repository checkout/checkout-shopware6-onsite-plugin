import HttpClient from 'src/service/http-client.service';

/**
 * This class extends the Core HttpClient class in order to adjust the original Request object
 * By default Shopware send `X-Requested-With` header with every request, it will fail on Regular Form
 */
export default class CheckoutComHttpClient extends HttpClient {
    constructor(withHttpRequest = true) {
        super();

        // Because some requests are required to be sent with the `X-Requested-With` header,
        // Decide if the request should be sent with the `X-Requested-With` header or not
        // by passing the `withHttpRequest` parameter to the constructor
        this._withHttpRequest = withHttpRequest;
    }

    /**
     * Modifies the original request object to add the specific headers.
     *
     * Returns a new and configured XMLHttpRequest object
     *
     * @param {'GET'|'POST'|'DELETE'|'PATCH'} type
     * @param {string} url
     * @param {string} contentType
     */
    _createPreparedRequest(type, url, contentType) {
        this._request = super._createPreparedRequest(type, url, contentType);

        if (!this._withHttpRequest) {
            // Remove the X-Requested-With header for the regular HTTP request
            // So it can send request to regular form
            this._request.setRequestHeader('X-Requested-With', '');
        }

        return this._request;
    }
}
