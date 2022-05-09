import template from './checkout-com-payment-method-google-pay.html.twig';

const { Component } = Shopware;

/**
 * This component is used to handle the configuration of the Google Pay payment method.
 * It will save the configuration and use it to handle payment within Google Pay.
 */
Component.register('checkout-com-payment-method-google-pay', {
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
    },
});
