import { FLOW_ACTION } from '../../constant/settings';

const { Component } = Shopware;

Component.override('sw-flow-sequence-action', {
    computed: {
        modalName() {
            if (this.selectedAction === FLOW_ACTION.FULL_REFUND) {
                return 'checkout-com-flow-full-refund-modal';
            }

            if (this.selectedAction === FLOW_ACTION.CAPTURE_PAYMENT) {
                return 'checkout-com-flow-capture-payment-modal';
            }

            return this.$super('modalName');
        },
    },

    methods: {
        getActionTitle(actionName) {
            if (actionName === FLOW_ACTION.FULL_REFUND) {
                return {
                    value: actionName,
                    icon: 'default-arrow-360-left',
                    label: this.$tc('checkout-payments.order.flow.refund.fullRefundLabel'),
                };
            }

            if (actionName === FLOW_ACTION.CAPTURE_PAYMENT) {
                return {
                    value: actionName,
                    icon: 'default-arrow-360-left',
                    label: this.$tc('checkout-payments.order.flow.capture.captureLabel'),
                };
            }

            return this.$super('getActionTitle', actionName);
        },
    },
});
