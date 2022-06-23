import template from './checkout-com-order-detail-payment.html.twig';
import './checkout-com-order-detail-payment.scss';

const {
    Component,
} = Shopware;

const icons = {
    failed: 'small-default-x-line-small',
    capture: 'small-default-checkmark-line-small',
    refund: 'small-default-checkmark-line-small',
    authorization: 'small-default-circle-small',
};

Component.register('checkout-com-order-detail-payment', {
    template,

    props: {
        isLoading: {
            type: Boolean,
            required: true,
        },

        payment: {
            type: Object,
            required: true,
        },
    },

    computed: {
        hasCardInformation() {
            const source = this.payment.source;
            if (!source) {
                return false;
            }

            return source.hasOwnProperty('last4');
        },

        cardType() {
            const source = this.payment.source;
            if (!source) {
                return '';
            }

            return source.card_wallet_type || source.type;
        },

        cardNumberHint() {
            const source = this.payment.source;
            if (!source) {
                return '';
            }

            const hiddenText = '*'.repeat(12);

            return `${hiddenText}${source.last4}`;
        },

        expiryDate() {
            const { source } = this.payment;
            if (!source) {
                return '';
            }

            return [
                source.expiry_month,
                source.expiry_year,
            ].join('/');
        },
    },

    methods: {
        getStyle(action) {
            let actionType = action.type;
            if (!action.approved) {
                // Change the action type to failed if the action is not approved
                actionType = 'failed';
            }

            actionType = `${actionType}`.toLowerCase();

            const styles = {
                iconStyle: `checkout-com-order-detail-payment-history-${actionType}-icon`,
                iconBackgroundStyle: `checkout-com-order-detail-payment-history-${actionType}-icon-bg`,
            };

            if (icons.hasOwnProperty(actionType)) {
                styles.icon = icons[actionType];
            }

            return styles;
        },

        getIconFromAction(action) {
            return this.getStyle(action).icon;
        },

        getIconColorFromAction(action) {
            return this.getStyle(action).iconStyle;
        },

        getBackgroundColorFromAction(action) {
            return this.getStyle(action).iconBackgroundStyle;
        },
    },
});
