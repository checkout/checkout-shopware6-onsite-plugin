import { FLOW_ACTION } from '../../constant/settings';

const { Component } = Shopware;

Component.override('sw-flow-sequence-action', {
    computed: {
        modalName() {
            if (this.selectedAction === FLOW_ACTION.FULL_REFUND) {
                return 'checkout-com-flow-full-refund-modal';
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
                    label: this.$tc('checkout-payments.order.flow.fullRefundLabel'),
                };
            }

            return this.$super('getActionTitle', actionName);
        },
    },
});
