import AdminAPIClient from '../../services/shopware/AdminAPIClient';

class ShopConfigurationAction {
    constructor() {
        this.apiClient = AdminAPIClient;
    }

    /**
     * Setup necessary data for the plugin and clear cache
     * @param accountType
     */
    async setupShop(accountType = 'abc') {
        return this.setupPlugin(accountType).then(() => this._clearCache())
    }

    /**
     * Set default data for the CheckoutCom plugin to work with all sales channels
     * @param accountType
     * @returns {Promise<void>}
     */
    async setupPlugin(accountType = 'abc') {
        // Config plugin
        return this.configCheckoutComPlugin(null, accountType).then(() => {
            // assign all payment methods to
            // all available sales channels
            return this.apiClient.get('/sales-channel');
        }).then(channels => {

            if (channels === undefined || channels === null) {
                throw new Error('Attention, No Sales Channels found from Shopware API');
            }

            channels.forEach(async channel => {
                await this._activatePaymentMethods(channel.id);
            });
        });
    }

    /**
     * Initialize CheckoutCom plugin by sales channel
     * @param channelId
     * @param accountType
     */
    configCheckoutComPlugin(channelId = null, accountType = 'abc') {
        const data = {
            [channelId]: {
                'CkoShopware6.config.checkoutPluginConfigSectionApi': {
                    'publicKey': Cypress.env(accountType).publicKey,
                    'secretKey': Cypress.env(accountType).secretKey,
                    'sandboxMode': true,
                    accountType
                },
                // ------------------------------------------------------------------
                'CkoShopware6.config.checkoutPluginConfigSectionOrderState': {
                    orderStateForAuthorizedPayment: 'checkout_com.skip',
                    orderStateForFailedPayment: 'checkout_com.skip',
                    orderStateForPaidPayment: 'checkout_com.skip',
                    orderStateForVoidedPayment: 'checkout_com.skip',
                },
                'core.mailerSettings.disableDelivery': true,
            }
        };

        return this.apiClient.post('/_action/system-config/batch', data);
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
