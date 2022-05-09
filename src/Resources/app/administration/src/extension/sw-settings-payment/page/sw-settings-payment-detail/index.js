import template from './sw-settings-payment-detail.html.twig';
import { CHECKOUT_DOMAIN_PAYMENT_METHOD, PAYMENT_METHOD_TYPE } from '../../../../constant/settings';

const {
    Component,
    Utils,
} = Shopware;
const { isEmpty } = Utils.types;

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
            checkoutConfigs: {},
            isLoadingComponent: false,
            parentCheckoutMediaPreviews: {},
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
            return this.getCheckoutPaymentMethodConfig(this.salesChannelId);
        },

        parentCheckoutPaymentMethodConfig() {
            return this.getCheckoutPaymentMethodConfig(null);
        },

        checkoutDomainPaymentMethod() {
            if (!this.paymentMethodCheckoutConfig) {
                return '';
            }

            return `${CHECKOUT_DOMAIN_PAYMENT_METHOD}.${this.paymentMethodCheckoutConfig.methodType}`;
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
            // If the checkoutConfigs is not set, we handle regular onSave
            if (isEmpty(this.checkoutConfigs)) {
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
                this.saveSystemConfig(),
                this.$super('onSave'),
            ]).finally(() => {
                this.isLoading = false;
            });
        },

        readAll(salesChannelId) {
            this.isLoading = true;
            // Return when data for this salesChannel was already loaded
            if (this.checkoutConfigs.hasOwnProperty(this.salesChannelId)) {
                this.isLoading = false;
                return Promise.resolve();
            }

            return this.loadCheckoutConfigs(salesChannelId);
        },

        async loadCheckoutConfigs(salesChannelId) {
            this.setLoading(true);
            try {
                const checkoutConfigs = await this.systemConfigApiService.getValues(
                    this.checkoutDomainPaymentMethod,
                    salesChannelId,
                );

                this.setCheckoutConfigs(salesChannelId, checkoutConfigs);
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

            this.setCheckoutConfigs(
                this.salesChannelId,
                {
                    ...this.checkoutConfigs[this.salesChannelId],
                    [`${this.checkoutDomainPaymentMethod}.${paymentConfigProperty}`]: data,
                },
            );
        },

        setParentCheckoutMediaPreview(propertyKey, media) {
            this.$set(this.parentCheckoutMediaPreviews, propertyKey, media);
        },

        setCheckoutConfigs(salesChannelId, data) {
            this.$set(
                this.checkoutConfigs,
                salesChannelId,
                data,
            );
        },

        getCheckoutPaymentMethodConfig(salesChannelId) {
            const configs = {};
            if (!this.isCheckout) {
                return configs;
            }

            const salesChannelConfigs = this.checkoutConfigs[salesChannelId];
            if (!salesChannelConfigs) {
                return configs;
            }

            Object.keys(salesChannelConfigs).forEach((checkoutConfigKey) => {
                const propertyKey = checkoutConfigKey.replace(`${this.checkoutDomainPaymentMethod}.`, '');
                configs[propertyKey] = salesChannelConfigs[checkoutConfigKey];
            });

            return configs;
        },

        saveSystemConfig() {
            return this.systemConfigApiService.batchSave(this.checkoutConfigs);
        },

        setLoading(value) {
            this.isLoadingComponent = value;
        },
    },
});
