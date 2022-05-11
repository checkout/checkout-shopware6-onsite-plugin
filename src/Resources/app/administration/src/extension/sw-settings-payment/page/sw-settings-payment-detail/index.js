import template from './sw-settings-payment-detail.html.twig';
import { CHECKOUT_DOMAIN, PAYMENT_METHOD_IDENTIFIER } from '../../../../constant/settings';
import { getCheckoutConfig } from '../../../../services/utils/system-config.utils';

const { Component } = Shopware;

/**
 * Overwrite the default payment method component to
 * add section components for the payment method
 * then save data for some special payment methods
 * in the Shopware system config (our Checkout.com configs).
 */
Component.override('sw-settings-payment-detail', {
    template,

    inject: ['systemConfigApiService'],

    data() {
        return {
            isDisplayComponent: false,
            checkoutConfigs: null,
        };
    },

    computed: {
        isApplePay() {
            const { paymentMethod } = this;

            return (paymentMethod && paymentMethod.formattedHandlerIdentifier === PAYMENT_METHOD_IDENTIFIER.APPLE_PAY);
        },
        isGooglePay() {
            const { paymentMethod } = this;

            return (paymentMethod && paymentMethod.formattedHandlerIdentifier === PAYMENT_METHOD_IDENTIFIER.GOOGLE_PAY);
        },
    },

    watch: {
        'paymentMethod.id'() {
            if (!this.isApplePay && !this.isGooglePay) {
                return;
            }

            this.loadCheckoutConfigs().then(() => {
                this.isDisplayComponent = true;
            });
        },
    },

    methods: {
        onSave() {
            // If the checkoutConfigs is not set, we handle regular onSave
            if (!this.checkoutConfigs) {
                return this.$super('onSave');
            }

            if (!this.acl.can('payment.editor')) {
                this.createNotificationError({
                    message: this.$tc('checkout-payments.general.permissionDeny'),
                });

                return Promise.resolve();
            }

            this.isLoading = true;
            return Promise.all([
                this.saveSystemConfig(this.checkoutConfigs),
                this.$super('onSave'),
            ]).finally(() => {
                this.isLoading = false;
            });
        },

        async loadCheckoutConfigs() {
            this.checkoutConfigs = await this.systemConfigApiService.getValues(CHECKOUT_DOMAIN);
        },

        setCheckoutConfigs(suffixField, data) {
            const applePayField = getCheckoutConfig(suffixField);
            if (typeof data === 'string') {
                // Trim the string to remove whitespace from both sides of a string.
                data = `${data}`.trim();
            }

            this.checkoutConfigs[applePayField] = data;
        },

        saveSystemConfig() {
            return this.systemConfigApiService.saveValues(this.checkoutConfigs);
        },
    },
});
