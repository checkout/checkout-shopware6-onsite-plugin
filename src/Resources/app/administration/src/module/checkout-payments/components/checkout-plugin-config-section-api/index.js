import template from './checkout-plugin-config-section-api.html.twig';
import './checkout-plugin-config-section-api.scss';
import { DASHBOARD_LINK } from '../../../../constant/settings';

const {
    Component,
    Mixin,
    Utils,
} = Shopware;
const { isEmpty } = Utils.types;

Component.register('checkout-plugin-config-section-api', {
    template,

    inject: ['checkoutConfigService'],

    mixins: [Mixin.getByName('notification')],

    props: {
        inheritedValue: {
            type: Object,
            required: true,
        },
        actualConfigData: {
            type: Object,
            required: false,
        },
        isNotDefaultSalesChannel: {
            type: Boolean,
            required: true,
        },
    },

    data() {
        return {
            config: this.actualConfigData || {},
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
        updateInheritedValue(field, currentValue, value, updateCurrentValueCB) {
            updateCurrentValueCB({
                ...currentValue,
                [field]: value,
            });
        },

        checkInheritance(value) {
            return isEmpty(value);
        },

        async onTestButtonClicked() {
            this.isLoading = true;
            const {
                secretKey,
                publicKey,
                sandboxMode,
            } = isEmpty(this.config) ? this.inheritedValue : this.config;

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
            const {
                isSecretKey,
                valid,
            } = result;

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
