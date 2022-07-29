import template from './sw-order-detail.html.twig';
import './sw-order-detail.scss';
import checkoutComOrder from './state';

const {
    Component,
    State,
} = Shopware;
const { mapGetters } = Component.getComponentHelper();

/**
 * Overwrite the default order detail component to
 * add section components for the checkout.com dashboard
 */
Component.override('sw-order-detail', {
    template,

    inject: [
        'checkoutOrderService',
    ],

    computed: {
        ...mapGetters('checkoutComOrder', [
            'isAuthorizedPayment',
            'paymentMethodCheckoutConfig',
        ]),

        canManualCapture() {
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

            return paymentMethodCheckoutConfig.canManualCapture;
        },

        canManualVoid() {
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

            return paymentMethodCheckoutConfig.canManualVoid;
        },
    },

    beforeCreate() {
        State.registerModule('checkoutComOrder', checkoutComOrder);
    },

    beforeDestroy() {
        State.unregisterModule('checkoutComOrder');
    },

    methods: {
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
