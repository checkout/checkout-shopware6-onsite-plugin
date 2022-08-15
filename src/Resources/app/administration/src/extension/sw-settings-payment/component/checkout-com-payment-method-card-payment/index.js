import template from './checkout-com-payment-method-card-payment.html.twig';

const { Component } = Shopware;

/**
 * This component is used to handle the configuration of the Card Payments payment method.
 * It will save the configuration and use it to handle payment within Card Payments.
 */
Component.register('checkout-com-payment-method-card-payment', {
    template,

    inject: ['acl'],

    props: {
        salesChannelId: {
            type: String,
            required: false,
        },
        paymentMethodConfigs: {
            type: Object,
            required: false,
        },
        parentPaymentMethodConfigs: {
            type: Object,
            required: false,
        },
    },

    computed: {
        isNotDefaultSalesChannel() {
            return this.salesChannelId !== null;
        },
    },

    methods: {
        onInputChange(field, value) {
            this.$emit('set-checkout-payment-configs', field, value);
        },

        removeSwitchInheritance(value) {
            return value === undefined;
        },
    },
});
