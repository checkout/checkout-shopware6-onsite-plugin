import AdminAPIClient from '../../services/shopware/AdminAPIClient';

class ShopConfigurationAction {
    constructor() {
        this.apiClient = AdminAPIClient;
    }


    /**
     * Setup necessary data for the plugin and clear cache
     */
    setupShop() {
        this.setupPlugin();
        this._clearCache();
    }


    /**
     * Set default data for the CheckoutCom plugin to work with all sales channels
     */
    setupPlugin() {
        // assign all payment methods to
        // all available sales channels
        this.apiClient.get('/sales-channel')
            .then(channels => {

                if (channels === undefined || channels === null) {
                    throw new Error('Attention, No Sales Channels found from Shopware API');
                }

                channels.forEach(async channel => {
                    await this._activatePaymentMethods(channel.id);
                    await this._configCheckoutComPlugin(channel.id);
                });
            });
    }

    /**
     * Initialize CheckoutCom plugin by sales channel
     * @param channelId
     * @private
     */
    _configCheckoutComPlugin(channelId) {
        const data = {};

        data[channelId] = {
            'CheckoutCom.config.checkoutPluginConfigSectionApi': {
                'publicKey': Cypress.env('publicKey'),
                'secretKey': Cypress.env('secretKey'),
                'sandboxMode': true,
            },
            // ------------------------------------------------------------------
            'CheckoutCom.config.checkoutPluginConfigSectionOrderState': {
                orderStateForAuthorizedPayment: 'checkout_com.skip',
                orderStateForFailedPayment: 'checkout_com.skip',
                orderStateForPaidPayment: 'checkout_com.skip',
                orderStateForVoidedPayment: 'checkout_com.skip',
            },
        };

        this.apiClient.post('/_action/system-config/batch', data);
        this.apiClient.post('/_action/system-config', {
            'core.mailerSettings.disableDelivery': true,
        });
    }

    /**
     * Set system config value
     * @param key
     * @param value
     * @returns {*}
     */
    setSystemConfig(key, value) {
        if (value === undefined) return;

        const data = {
            null: {
                [key]: value
            }
        };

        return this.apiClient.post('/_action/system-config/batch', data);
    }

    /**
     * Activate CheckoutCom payment methods for all sales channels
     * @param id
     * @private
     */
    _activatePaymentMethods(id) {
        this.apiClient.get('/payment-method')
            .then(payments => {

                if (payments === undefined || payments === null) {
                    throw new Error('Attention, No payments from Shopware API');
                }

                let paymentMethodsIds = [];

                payments.forEach(element => {
                    paymentMethodsIds.push({
                        'id': element.id,
                    });
                });

                const data = {
                    'id': id,
                    'paymentMethods': paymentMethodsIds,
                };

                this.apiClient.patch('/sales-channel/' + id, data);
            });
    }

    /**
     * Clear cache
     * @returns {*}
     * @private
     */
    _clearCache() {
        return this.apiClient.delete('/_action/cache')
            .catch((err) => {
                console.log('Cache could not be cleared');
            });
    }

}

export default new ShopConfigurationAction();
