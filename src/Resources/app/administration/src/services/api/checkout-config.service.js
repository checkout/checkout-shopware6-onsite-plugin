const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API end point 'checkout/config'.
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
    constructor(
        httpClient,
        loginService,
        apiEndpoint = "/_action/checkout/config"
    ) {
        super(httpClient, loginService, apiEndpoint);
        this.name = "checkoutConfigService";
    }

    /**
     * Test api key
     *
     * @param secretKey {string}
     * @param publicKey {string}
     * @param isSandbox {boolean}
     * @returns {*}
     */
    testApiKey(secretKey, publicKey, isSandbox) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `${this.getApiBasePath()}/test-api-key`,
                { secretKey, publicKey, isSandbox },
                { headers }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CheckoutConfigService;
