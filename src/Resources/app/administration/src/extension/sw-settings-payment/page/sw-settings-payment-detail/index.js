import template from './sw-settings-payment-detail.html.twig';
import { CHECKOUT_DOMAIN, CHECKOUT_DOMAIN_PAYMENT_METHOD, PAYMENT_METHOD_TYPE } from '../../../../constant/settings';

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
            salesChannelId: null,
            isCheckoutConfigDisplay: false,
            checkoutPaymentMethodConfigs: null,
            isLoadingComponent: false,
        };
    },

    computed: {
        paymentMethodCheckoutConfig() {
            const { paymentMethod } = this;

            if (!paymentMethod) {
                return null;
            }

            if (!paymentMethod.customFields) {
                return null;
            }

            return paymentMethod.customFields.checkoutConfig || null;
        },

        isCheckout() {
            return this.paymentMethodCheckoutConfig && this.paymentMethodCheckoutConfig.isCheckout;
        },

        isApplePay() {
            if (!this.isCheckout) {
                return false;
            }

            return this.paymentMethodCheckoutConfig.methodType === PAYMENT_METHOD_TYPE.APPLE_PAY;
        },

        isGooglePay() {
            if (!this.isCheckout) {
                return false;
            }

            return this.paymentMethodCheckoutConfig.methodType === PAYMENT_METHOD_TYPE.GOOGLE_PAY;
        },

        checkoutPaymentMethodConfig() {
            if (!this.isCheckout) {
                return {};
            }

            return this.checkoutPaymentMethodConfigs[this.paymentMethodCheckoutConfig.methodType] || {};
        },
    },

    watch: {
        'paymentMethod.id'() {
            this.isDisplayComponent = false;

            if (!this.isApplePay && !this.isGooglePay) {
                return;
            }

            this.loadCheckoutConfigs(this.salesChannelId).then(() => {
                this.isCheckoutConfigDisplay = true;
            });
        },
    },

    methods: {
        onSave() {
            // If the checkoutPaymentMethodConfigs is not set, we handle regular onSave
            if (!this.checkoutPaymentMethodConfigs) {
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
                this.saveSystemConfig(this.checkoutPaymentMethodConfigs),
                this.$super('onSave'),
            ]).finally(() => {
                this.isLoading = false;
            });
        },

        async loadCheckoutConfigs(salesChannelId) {
            this.setLoading(true);
            try {
                const checkoutConfigs = await this.systemConfigApiService.getValues(CHECKOUT_DOMAIN, salesChannelId);
                this.checkoutPaymentMethodConfigs = checkoutConfigs[CHECKOUT_DOMAIN_PAYMENT_METHOD] || {};
            } finally {
                this.setLoading(false);
            }
        },

        async onSalesChannelChanged(salesChannelId) {
            await this.loadCheckoutConfigs(salesChannelId);
            this.salesChannelId = salesChannelId;
        },

        setCheckoutPaymentConfigs(paymentConfigProperty, data) {
            if (typeof data === 'string') {
                // Trim the string to remove whitespace from both sides of a string.
                data = `${data}`.trim();
            }

            this.$set(
                this.checkoutPaymentMethodConfigs,
                this.paymentMethodCheckoutConfig.methodType,
                {
                    ...this.checkoutPaymentMethodConfig,
                    [paymentConfigProperty]: data,
                },
            );
        },

        saveSystemConfig() {
            return this.systemConfigApiService.saveValues({
                [CHECKOUT_DOMAIN_PAYMENT_METHOD]: this.checkoutPaymentMethodConfigs,
            }, this.salesChannelId);
        },

        setLoading(value) {
            this.isLoadingComponent = value;
        },
    },
});
