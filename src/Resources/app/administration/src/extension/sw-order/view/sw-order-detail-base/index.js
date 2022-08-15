import template from './sw-order-detail-base.html.twig';
import { LINE_ITEM_PAYLOAD, ORDER_CHECKOUT_COM_CUSTOM_FIELDS } from '../../../../constant/settings';

const {
    Component,
    Mixin,
    State,
} = Shopware;
const {
    mapState,
    mapGetters,
} = Component.getComponentHelper();

Component.override('sw-order-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: [
        'checkoutOrderService',
    ],

    computed: {
        ...mapState('checkoutComOrder', [
            'checkoutComPayment',
        ]),

        ...mapGetters('checkoutComOrder', [
            'isPartialRefundedPayment',
            'isFullRefundedPayment',
        ]),

        hasCheckoutComConfig() {
            const customFields = this.order.customFields;
            if (!customFields) {
                return false;
            }

            return customFields.hasOwnProperty(ORDER_CHECKOUT_COM_CUSTOM_FIELDS);
        },

        checkoutTotalBeforeRefund() {
            return Math.abs(this.checkoutComRefundedAmount) + this.order.price.totalPrice;
        },

        checkoutComRefundedAmount() {
            // Count total price of the refunded order line items (has `LINE_ITEM_PAYLOAD` property)
            return this.order.lineItems.reduce((total, lineItem) => {
                if (!lineItem.payload) {
                    return total;
                }

                if (!lineItem.payload.hasOwnProperty(LINE_ITEM_PAYLOAD)) {
                    return total;
                }

                return total + lineItem.totalPrice;
            }, 0);
        },
    },

    watch: {
        order() {
            this.getCheckoutComPayment();
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

                State.commit('checkoutComOrder/setCheckoutComPayment', checkoutComPayment);
                State.commit('checkoutComOrder/setOrder', this.order);
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
