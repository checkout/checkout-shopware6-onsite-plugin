import template from './checkout-plugin-config-webhook-section.html.twig';

const { Component } = Shopware;

Component.register('checkout-plugin-config-webhook-section', {
    template,

    props: {
        value: {
            type: Object,
            required: false,
        },
    },

    data() {
        return {
            config: {
                id: this.getConfigPropsValue('id', ''),
                authorization: this.getConfigPropsValue('authorization', ''),
            },
        };
    },

    methods: {
        // We need to use the getConfigPropsValue function to get the value from the config props.
        getConfigPropsValue(field, defaultValue = null) {
            if (!this.value) {
                return defaultValue;
            }

            return this.value[field] ?? defaultValue;
        },
    },
});
