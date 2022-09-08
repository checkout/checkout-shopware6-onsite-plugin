import template from './sw-order-line-items-grid.html.twig';
import './sw-order-line-items-grid.scss';
import { LINE_ITEM_PAYLOAD, ORDER_CHECKOUT_COM_CUSTOM_FIELDS } from '../../../../constant/settings';

const {
    Component,
    Utils,
} = Shopware;
const { isEmpty } = Utils.types;
const { mapGetters } = Component.getComponentHelper();

Component.override('sw-order-line-items-grid', {
    template,

    computed: {
        ...mapGetters('checkoutComOrder', [
            'checkoutOrderCustomFields',
            'isCapturedPayment',
            'isPartialRefundedPayment',
        ]),

        canRefund() {
            if (!this.acl.can('order.editor')) {
                return false;
            }

            if (this.isRefundedAllProducts) {
                return false;
            }

            return this.isCapturedPayment || this.isPartialRefundedPayment;
        },

        isOrderRefundedFromHub() {
            if (!this.order.customFields) {
                return false;
            }

            const checkoutCustomFields = this.order.customFields[ORDER_CHECKOUT_COM_CUSTOM_FIELDS];
            if (!checkoutCustomFields) {
                return false;
            }

            return checkoutCustomFields.isRefundedFromHub;
        },

        isRefundedAllProducts() {
            return this.refundableLineItems.every((refundableLineItem) => refundableLineItem.remainingQuantity === 0);
        },

        isPriceDifferent() {
            if (!this.order) {
                return false;
            }

            if (!this.order.price) {
                return false;
            }

            const checkoutOrderCustomFields = this.checkoutOrderCustomFields;
            if (!checkoutOrderCustomFields) {
                return false;
            }

            let deliveryTotalPrice = 0;
            if (!isEmpty(this.order.deliveries) && !checkoutOrderCustomFields.isShippingCostRefunded) {
                deliveryTotalPrice = this.order.deliveries.reduce((total, delivery) => {
                    return total + delivery.shippingCosts.totalPrice;
                }, 0);
            }

            const totalPriceOrder = this.order.price.totalPrice - deliveryTotalPrice;

            return this.isRefundedAllProducts && totalPriceOrder > 0;
        },
    },

    data() {
        return {
            refundableLineItems: [],
            isShowRefundModal: false,
            isShowFixPriceDifferenceModal: false,
        };
    },


    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.setRefundableLineItems();
        },

        setRefundableLineItems() {
            const refundLineItems = [];
            const refundableLineItems = [];

            // Foreach line items of order to separate 2 types:
            // 1. refundedLineItems = The order line items has property `LINE_ITEM_PAYLOAD`(refunded line items)
            // 2. refundableLineItems = The refundable order line items that can be shown in the refund manager modal
            this.orderLineItems.forEach((orderLineItem) => {
                if (!orderLineItem.payload || !orderLineItem.payload.hasOwnProperty(LINE_ITEM_PAYLOAD)) {
                    if (isEmpty(orderLineItem.productId)) {
                        return;
                    }

                    refundableLineItems.push({ ...orderLineItem });
                } else {
                    refundLineItems.push({ ...orderLineItem });
                }
            });

            refundableLineItems.forEach((item) => {
                // Get all refunded line items of the current order
                const mappingRefundLineItems = refundLineItems.filter(
                    (refundItem) => refundItem.payload[LINE_ITEM_PAYLOAD].refundLineItemId === item.id,
                );

                // Calculate the refunded quantity of the line item
                item.refundedQuantity = mappingRefundLineItems.reduce((
                    totalRefunded,
                    itemRefund,
                ) => totalRefunded + itemRefund.quantity, 0);

                item.remainingQuantity = item.quantity - item.refundedQuantity;
            });

            this.refundableLineItems = refundableLineItems;
        },

        onOpenRefundModal() {
            this.isShowRefundModal = true;
        },

        closeRefundModal() {
            this.isShowRefundModal = false;
        },

        onOpenFixPriceDifferenceModal() {
            this.isShowFixPriceDifferenceModal = true;
        },

        closeFixPriceDifferenceModal() {
            this.isShowFixPriceDifferenceModal = false;
        },

        isCheckoutComRefundedItem(item) {
            if (!item) {
                return false;
            }

            if (!item.payload) {
                return false;
            }

            return item.payload.hasOwnProperty(LINE_ITEM_PAYLOAD);
        },

        getDiscountCompositionsItem(item) {
            if (!item) {
                return [];
            }

            if (!item.payload) {
                return [];
            }

            const payload = item.payload[LINE_ITEM_PAYLOAD];

            if (!payload) {
                return [];
            }

            return payload.discountCompositions || [];
        },
    },
});
