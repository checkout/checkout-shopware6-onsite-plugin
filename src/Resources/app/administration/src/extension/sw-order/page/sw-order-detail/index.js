import template from './sw-order-detail.html.twig';
import './sw-order-detail.scss';
import { CHECKOUT_STATUS } from '../../../../constant/settings';

const { Component } = Shopware;

/**
 * Overwrite the default order detail component to
 * add section components for the checkout.com dashboard
 */
Component.override('sw-order-detail', {
    template,

    inject: [
        'checkoutOrderService',
    ],

    data() {
        return {
            checkoutComPayment: null,
            checkoutComOrder: null,
        };
    },

    computed: {
        isAuthorizedPayment() {
            if (!this.checkoutComPayment) {
                return false;
            }

            return this.checkoutComPayment.status === CHECKOUT_STATUS.AUTHORIZED;
        },

        checkoutComPaymentMethod() {
            return this.checkoutComOrder?.transactions?.last()?.paymentMethod;
        },

        paymentMethodCheckoutConfig() {
            return this.checkoutComPaymentMethod?.customFields?.checkoutConfig;
        },

        shouldManualCapture() {
            if (this.isEditing) {
                return false;
            }

            if (!this.isAuthorizedPayment) {
                return false;
            }

            const paymentMethodCheckoutConfig = this.paymentMethodCheckoutConfig;
            if (!paymentMethodCheckoutConfig) {
                return false;
            }

            return paymentMethodCheckoutConfig.shouldManualCapture;
        },

        shouldManualVoid() {
            if (this.isEditing) {
                return false;
            }

            if (!this.isAuthorizedPayment) {
                return false;
            }

            const paymentMethodCheckoutConfig = this.paymentMethodCheckoutConfig;
            if (!paymentMethodCheckoutConfig) {
                return false;
            }

            return paymentMethodCheckoutConfig.shouldManualVoid;
        },
    },

    created() {
        this.registerCheckoutComListener();
    },

    destroyed() {
        this.destroyedCheckoutComComponent();
    },

    methods: {
        registerCheckoutComListener() {
            this.$root.$on('checkout-payment-change', this.onCheckoutPaymentChange);
        },

        destroyedCheckoutComComponent() {
            this.$root.$off('checkout-payment-change', this.onCheckoutPaymentChange);
        },

        onCheckoutPaymentChange(checkoutComPayment, order) {
            this.checkoutComPayment = checkoutComPayment;
            this.checkoutComOrder = order;
        },

        async onCapture() {
            try {
                this.isLoading = true;
                await this.checkoutOrderService.capturePayment(this.orderId);

                this.$root.$emit('checkout-order-update');
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
                this.isLoading = false;
            }
        },

        async onVoid() {
            try {
                this.isLoading = true;
                await this.checkoutOrderService.voidPayment(this.orderId);

                this.$root.$emit('checkout-order-update');
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
                this.isLoading = false;
            }
        },
    },
});
