import template from './checkout-plugin-config-section-api.html.twig';
import './checkout-plugin-config-section-api.scss';
import { DASHBOARD_LINK } from '../../../../constant/settings';

const { Component, Mixin } = Shopware;

Component.register('checkout-plugin-config-section-api', {
    template,

    inject: ['checkoutConfigService'],

    mixins: [Mixin.getByName('notification')],

    props: {
        value: {
            type: Object,
            required: false,
        },
    },

    data() {
        return {
            config: {
                secretKey: this.getConfigPropsValue('secretKey', ''),
                publicKey: this.getConfigPropsValue('publicKey', ''),
                sandboxMode: this.getConfigPropsValue('sandboxMode', true),
            },
            error: {
                secretKey: false,
                publicKey: false,
            },
            isLoading: false,
            testModeInput: null,
            isSandbox: false,
        };
    },

    computed: {
        apiLink() {
            const { sandboxMode } = this.config;

            return sandboxMode ? DASHBOARD_LINK.SANDBOX : DASHBOARD_LINK.LIVE;
        },
    },

    watch: {
        config: {
            handler(configValue) {
                this.$emit('change', configValue);
            },
            deep: true,
        },
    },

    methods: {
        // We need to use the getConfigPropsValue function to get the value from the config props.
        getConfigPropsValue(field, defaultValue = null) {
            if (!this.value) {
                return defaultValue;
            }

            return this.value[field] ?? defaultValue;
        },

        async onTestButtonClicked() {
            this.isLoading = true;
            const { secretKey, publicKey, sandboxMode } = this.config;

            // We reset the error state
            this.error = {
                secretKey: false,
                publicKey: false,
            };

            try {
                const results = await this.checkoutConfigService.testApiKey(
                    secretKey,
                    publicKey,
                    sandboxMode,
                );

                results.forEach(this._showMessageResult);
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.isLoading = false;
        },

        _showMessageResult(result) {
            const { isSecretKey, valid } = result;

            const inputError = isSecretKey ? 'secretKey' : 'publicKey';
            const validKey = valid ? 'isValid' : 'isInvalid';

            const messageData = {
                title: this.$tc(
                    'checkout-payments.config.api.testApiKeys.title',
                ),
                message: this.$tc(`checkout-payments.config.api.testApiKeys.${inputError}.${validKey}`),
            };

            if (valid) {
                this.createNotificationSuccess(messageData);
            } else {
                this.createNotificationError(messageData);
                this.error[inputError] = true;
            }
        },
    },
});
