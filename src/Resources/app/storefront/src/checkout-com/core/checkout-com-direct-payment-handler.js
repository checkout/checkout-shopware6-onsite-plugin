import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import StoreApiClient from 'src/service/store-api-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import { DATA_BAG_KEY } from '../helper/constants';

/**
 * This plugin handles the payment process for the direct payment.
 * Use a new custom direct cart instead of the Shopware original cart
 */
export default class CheckoutComDirectPaymentHandler extends Plugin {
    cartToken = null;
    productId = null;
    currencyCode = null;
    countryCode = null;

    static options = {
        paymentMethodType: null,
        shopName: null,
        addProductToCartEndpoint: null,
        removeBackupEndpoint: null,
        getShippingMethodsEndpoint: null,
        updateShippingPayloadEndpoint: null,
        processPaymentEndpoint: null,
        productQuantitySelectClass: '.product-detail-quantity-select',
        directProductIdName: 'checkoutComProductId',
        directCurrencyCodeName: 'checkoutComCurrencyCode',
        directCountryCodeName: 'checkoutComCountryCode',
    };

    init() {
        const { paymentMethodType } = this.options;
        if (!paymentMethodType) {
            throw new Error(`The "paymentMethodType" option for the plugin "${this._pluginName}" is not defined.`);
        }

        this.storeApiClient = new StoreApiClient();
    }

    getDirectButtons() {
        const { directButtonClass } = this.options;
        if (!directButtonClass) {
            throw new Error(`The "directButtonClass" option for the plugin "${this._pluginName}" is not defined.`);
        }

        return DomAccess.querySelectorAll(document, directButtonClass, false);
    }

    setUpDirectOptions(buttonElement) {
        const {
            directProductIdName,
            directCurrencyCodeName,
            directCountryCodeName,
        } = this.options;

        this.productId = this.getInputValue(buttonElement, directProductIdName);
        this.currencyCode = this.getInputValue(buttonElement, directCurrencyCodeName);
        this.countryCode = this.getInputValue(buttonElement, directCountryCodeName);
    }

    /**
     * Initial direct payment process
     *
     * @returns {Promise<boolean>}
     */
    initDirectPayment() {
        if (this.cartToken) {
            return Promise.resolve(true);
        }

        const { productId } = this;
        const { productQuantitySelectClass } = this.options;

        let productQuantity = 1;

        const quantitySelects = DomAccess.querySelectorAll(document, productQuantitySelectClass, false);

        // If it has quantity dropdown, use it instead of the default quantity
        if (quantitySelects.length > 0) {
            productQuantity = parseInt(quantitySelects[0].value, 10);
        }

        return this._addProductToCart(productId, productQuantity);
    }

    /**
     * Get shipping methods and calculate the current direct cart
     *
     * @param {string} countryCode
     * @returns {Promise<Object>}
     */
    getShippingMethods(countryCode) {
        const {
            paymentMethodType,
            getShippingMethodsEndpoint,
        } = this.options;
        if (!countryCode || !this.cartToken) {
            return Promise.resolve({});
        }

        return new Promise((resolve) => {
            this.storeApiClient.post(getShippingMethodsEndpoint, JSON.stringify({
                cartToken: this.cartToken,
                paymentMethodType,
                countryCode,
            }), (result) => {
                if (!result) {
                    resolve({});
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
    }

    /**
     * Calculate the current direct cart by using the shipping method ID
     *
     * @param {string} shippingMethodId
     * @returns {Promise<Object>}
     */
    updateShippingPayload(shippingMethodId) {
        const {
            paymentMethodType,
            updateShippingPayloadEndpoint,
        } = this.options;

        if (!this.cartToken) {
            return Promise.resolve({});
        }

        return new Promise((resolve) => {
            this.storeApiClient.post(updateShippingPayloadEndpoint, JSON.stringify({
                cartToken: this.cartToken,
                paymentMethodType,
                shippingMethodId,
            }), (result) => {
                if (!result) {
                    resolve({});
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
    }

    /**
     * Process payment data returned by the payment provider
     *
     * @param {Object|string} token
     * @param {{
     *          email: string,
     *          firstName: string,
     *          lastName: string,
     *          phoneNumber: string,
     *          street: string,
     *          additionalAddressLine1: string,
     *          zipCode: string,
     *          countryStateCode: string,
     *          city: string,
     *          countryCode: string,
     *        }} shippingContact
     * @returns {Promise<Object>}
     */
    paymentAuthorized(token, shippingContact) {
        const {
            paymentMethodType,
            processPaymentEndpoint,
        } = this.options;

        return new Promise((resolve) => {
            this.storeApiClient.post(processPaymentEndpoint, JSON.stringify({
                cartToken: this.cartToken,
                paymentMethodType,
                shippingContact,
                [DATA_BAG_KEY]: {
                    token,
                },
            }), (result) => {
                if (!result) {
                    resolve({});
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
    }

    cancelDirectPayment() {
        const data = JSON.stringify({
            cartToken: this.cartToken,
        });
        this.cartToken = null;
        this.productId = null;
        this.currencyCode = null;
        this.countryCode = null;

        // Remove backup cart if the direct payment was canceled
        this.storeApiClient.post(this.options.removeBackupEndpoint, data);
        this.removePageLoading();
    }

    createPageLoading() {
        // Create page loading indicator
        PageLoadingIndicatorUtil.create();
    }

    removePageLoading() {
        // Remove page loading indicator
        PageLoadingIndicatorUtil.remove();
    }

    getInputValue(parentElement, nameSelector) {
        const input = parentElement.querySelector(`[name='${nameSelector}']`);
        if (!input || !input.value) {
            throw new Error(`No ${nameSelector} found`);
        }

        return input.value;
    }

    /**
     * Add product to our new cart and backup the original cart
     *
     * @param {string|null} productId
     * @param {number} productQuantity
     * @returns {Promise<boolean>}
     */
    _addProductToCart(productId, productQuantity) {
        const { addProductToCartEndpoint } = this.options;

        return new Promise((resolve) => {
            this.storeApiClient.post(addProductToCartEndpoint, JSON.stringify({
                productId,
                productQuantity,
            }), (result) => {
                if (!result) {
                    resolve(false);
                    return;
                }

                const { cartToken } = JSON.parse(result);

                if (!cartToken) {
                    resolve(false);
                    return;
                }

                this.cartToken = cartToken;

                resolve(true);
            });
        });
    }
}
