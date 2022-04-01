const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API end point 'checkout-com/media'.
 * @class
 * @extends ApiService
 */
class CheckoutMediaService extends ApiService {
    /**
     *
     * @param httpClient {AxiosInstance}
     * @param loginService {LoginService}
     * @param apiEndpoint {string}
     */
    constructor(httpClient, loginService, apiEndpoint = '/_action/checkout-com/media') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'checkoutMediaService';
    }

    /**
     * Get system media
     *
     * @param mediaId {string}
     * @returns {*}
     */
    getSystemMedia(mediaId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/${mediaId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Remove system media
     *
     * @param mediaId {string}
     * @returns {*}
     */
    removeSystemMedia(mediaId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .delete(`${this.getApiBasePath()}/${mediaId}`, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CheckoutMediaService;
