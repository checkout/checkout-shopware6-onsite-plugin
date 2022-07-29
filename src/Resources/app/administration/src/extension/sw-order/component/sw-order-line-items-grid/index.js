import template from './sw-order-line-items-grid.html.twig';
import { LINE_ITEM_PAYLOAD } from '../../../../constant/settings';

const { Component } = Shopware;
const { mapGetters } = Component.getComponentHelper();

Component.override('sw-order-line-items-grid', {
    template,

    computed: {
        ...mapGetters('checkoutComOrder', [
            'isCapturedPayment',
            'isPartialRefundedPayment',
        ]),

        canRefund() {
            if (!this.acl.can('order.editor')) {
                return false;
            }

            return this.isCapturedPayment || this.isPartialRefundedPayment;
        },
    },

    data() {
        return {
            isShowRefundModal: false,
        };
    },

    methods: {
        onOpenRefundModal() {
            this.isShowRefundModal = true;
        },

        closeRefundModal() {
            this.isShowRefundModal = false;
        },

        isCheckoutComRefundedItem(item) {
            return !!item?.payload?.hasOwnProperty(LINE_ITEM_PAYLOAD);
        },
    },
});
