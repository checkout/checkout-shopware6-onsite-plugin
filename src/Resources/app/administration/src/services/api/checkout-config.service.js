const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API end point 'checkout-com/config'.
 * @class
 * @extends ApiService
 */
class CheckoutConfigService extends ApiService {
    /**
     *
     * @param httpClient {AxiosInstance}
     * @param loginService {LoginService}
     * @param apiEndpoint {string}
     */
    constructor(httpClient, loginService, apiEndpoint = '/_action/checkout-com/config') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'checkoutConfigService';
    }

    /**
     * Test api key
     *
     * @param secretKey {string}
     * @param publicKey {string}
     * @param accountType {string}
     * @param isSandbox {boolean}
     * @returns {*}
     */
    testApiKey(secretKey, publicKey, accountType, isSandbox) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/test-api-key`, {
                secretKey,
                publicKey,
                accountType,
                isSandbox,
            }, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CheckoutConfigService;
