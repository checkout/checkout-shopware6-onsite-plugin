const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API end point 'checkout-com/order'.
 * @class
 * @extends ApiService
 */
class CheckoutOrderService extends ApiService {
    /**
     *
     * @param httpClient {AxiosInstance}
     * @param loginService {LoginService}
     * @param apiEndpoint {string}
     */
    constructor(httpClient, loginService, apiEndpoint = '/_action/checkout-com/order') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'checkoutOrderService';
    }

    /**
     * Get checkout.com payment by the order id
     *
     * @param orderId {string}
     * @returns {*}
     */
    getCheckoutComPayment(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/payment`, { orderId }, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CheckoutOrderService;
