import template from './checkout-com-payment-method-google-pay.html.twig';
import { getCheckoutConfig } from '../../../../services/utils/system-config.utils';

const { Component } = Shopware;

/**
 * This component is used to handle the configuration of the Google Pay payment method.
 * It will save the configuration and use it to handle payment within Google Pay.
 */
Component.register('checkout-com-payment-method-google-pay', {
    template,

    inject: ['acl'],

    props: {
        checkoutConfigs: {
            type: Object,
            required: false,
        },
    },

    methods: {
        onInputChange(field, value) {
            this.$emit('set-checkout-config', field, value);
        },

        getCheckoutConfigValue(field) {
            return this.checkoutConfigs[getCheckoutConfig(field)];
        },
    },
});
