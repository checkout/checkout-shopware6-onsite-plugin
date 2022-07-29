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
            .post(`${this.getApiBasePath()}/payment/${orderId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Capture checkout.com payment by the order id
     *
     * @param orderId {string}
     * @returns {*}
     */
    capturePayment(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/capture/${orderId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Void checkout.com payment by the order id
     *
     * @param orderId {string}
     * @returns {*}
     */
    voidPayment(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/void/${orderId}`, {}, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * Refund checkout.com payment by the order id
     *
     * @param orderId {string}
     * @param items {array}
     * @returns {*}
     */
    refundPayment(orderId, items) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`${this.getApiBasePath()}/refund`, {
                orderId,
                items,
            }, { headers }).then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default CheckoutOrderService;
