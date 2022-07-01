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

        shouldManualCapture() {
            if (this.isEditing) {
                return false;
            }

            if (!this.isAuthorizedPayment) {
                return false;
            }

            const paymentMethod = this.checkoutComPaymentMethod;
            if (!paymentMethod) {
                return false;
            }

            if (!paymentMethod.customFields.hasOwnProperty('checkoutConfig')) {
                return false;
            }

            return paymentMethod.customFields.checkoutConfig.shouldManualCapture;
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
    },
});
