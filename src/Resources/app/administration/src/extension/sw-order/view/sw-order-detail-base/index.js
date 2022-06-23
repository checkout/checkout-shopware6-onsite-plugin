import template from './sw-order-detail-base.html.twig';
import { ORDER_CHECKOUT_COM_CUSTOM_FIELDS } from '../../../../constant/settings';

const {
    Component,
    Mixin,
} = Shopware;

Component.override('sw-order-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: [
        'checkoutOrderService',
    ],

    computed: {
        hasCheckoutComConfig() {
            const customFields = this.order.customFields;
            if (!customFields) {
                return false;
            }

            return customFields.hasOwnProperty(ORDER_CHECKOUT_COM_CUSTOM_FIELDS);
        },
    },

    watch: {
        order() {
            this.getCheckoutComPayment();
        },
    },

    data() {
        return {
            checkoutComPayment: null,
        };
    },

    created() {
        this.registerCheckoutComListener();
    },

    destroyed() {
        this.destroyedCheckoutComComponent();
    },

    methods: {
        registerCheckoutComListener() {
            this.$root.$on('checkout-order-update', this.onCheckoutOrderUpdate);
        },

        destroyedCheckoutComComponent() {
            this.$root.$off('checkout-order-update', this.onCheckoutOrderUpdate);
        },

        onCheckoutOrderUpdate() {
            this.reloadEntityData();
        },

        async getCheckoutComPayment() {
            if (!this.hasCheckoutComConfig) {
                return;
            }

            try {
                this.$emit('loading-change', true);
                const checkoutComPayment = await this.checkoutOrderService.getCheckoutComPayment(this.order.id);

                this.$root.$emit('checkout-payment-change', checkoutComPayment, this.order);
                this.checkoutComPayment = checkoutComPayment;
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            } finally {
                this.$emit('loading-change', false);
            }
        },
    },
});
