import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import StoreApiClient from 'src/service/store-api-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

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
        shopName: null,
        addProductToCartPath: null,
        removeBackupPath: null,
        productQuantitySelectClass: '.product-detail-quantity-select',
        directProductIdName: 'checkoutComProductId',
        directCurrencyCodeName: 'checkoutComCurrencyCode',
        directCountryCodeName: 'checkoutComCountryCode',
    };

    init() {
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
        const productIdInput = buttonElement.querySelector(`[name='${directProductIdName}']`);
        if (!productIdInput || !productIdInput.value) {
            throw new Error('Not found product id');
        }

        const currencyCodeInput = buttonElement.querySelector(`[name='${directCurrencyCodeName}']`);
        if (!currencyCodeInput || !currencyCodeInput.value) {
            throw new Error('Not found currency code');
        }

        const countryCodeInput = buttonElement.querySelector(`[name='${directCountryCodeName}']`);
        if (!countryCodeInput || !countryCodeInput.value) {
            throw new Error('Not found country code');
        }

        this.productId = productIdInput.value;
        this.currencyCode = currencyCodeInput.value;
        this.countryCode = countryCodeInput.value;
    }

    /**
     * Initial direct payment process
     *
     * @returns {Promise<boolean>}
     */
    initDirectPayment() {
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

    cancelDirectPayment() {
        const data = JSON.stringify({
            cartToken: this.cartToken,
        });

        this.cartToken = null;
        this.productId = null;
        this.currencyCode = null;
        this.countryCode = null;

        // Remove backup cart if the direct payment was canceled
        this.storeApiClient.post(this.options.removeBackupPath, data);
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

    /**
     * Add product to our new cart and backup the original cart
     *
     * @param {string|null} productId
     * @param {number} productQuantity
     * @returns {Promise<boolean>}
     */
    _addProductToCart(productId, productQuantity) {
        const { addProductToCartPath } = this.options;

        return new Promise((resolve) => {
            this.storeApiClient.post(addProductToCartPath, JSON.stringify({
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
