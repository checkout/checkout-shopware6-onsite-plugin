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
        paymentMethodConfigs: {
            type: Object,
            required: false,
        },
    },

    methods: {
        onInputChange(field, value) {
            this.$emit('set-checkout-payment-configs', field, value);
        },
    },
});
