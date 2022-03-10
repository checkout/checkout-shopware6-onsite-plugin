import template from "./checkout-plugin-config-section-api.html.twig";
import "./checkout-plugin-config-section-api.scss";
import { DASHBOARD_LINK } from "../../../../constant/settings";

const { Component, Mixin } = Shopware;

Component.register("checkout-plugin-config-section-api", {
    template,

    inject: ["checkoutConfigService"],

    mixins: [Mixin.getByName("notification")],

    data() {
        return {
            isLoading: false,
            testModeInput: null,
            isSandbox: false,
        };
    },

    computed: {
        apiLink() {
            return this.isSandbox
                ? DASHBOARD_LINK.SANDBOX
                : DASHBOARD_LINK.LIVE;
        },
    },

    mounted() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            this.testModeInput = document.querySelector(
                'input[name="CheckoutCom.config.sandboxMode"]'
            );
            if (!this.testModeInput) {
                return;
            }

            this.testModeInput.addEventListener(
                "change",
                this.onTestModeInputChange
            );
            this.isSandbox = this.testModeInput.checked;
        },

        destroyedComponent() {
            this.testModeInput.removeEventListener(
                "change",
                this.onTestModeInputChange
            );
        },

        onTestModeInputChange(e) {
            this.isSandbox = e.target.checked;
        },

        async onTestButtonClicked() {
            this.isLoading = true;
            const secretKeyInput = document.querySelector(
                'input[name="CheckoutCom.config.secretKey"]'
            );
            const publicKeyInput = document.querySelector(
                'input[name="CheckoutCom.config.publicKey"]'
            );

            const secretKey = secretKeyInput ? secretKeyInput.value : null;
            const publicKey = publicKeyInput ? publicKeyInput.value : null;

            try {
                const results = await this.checkoutConfigService.testApiKey(
                    secretKey,
                    publicKey,
                    this.isSandbox
                );

                results.forEach((result) => {
                    const input = result.isSecretKey
                        ? secretKeyInput
                        : publicKeyInput;

                    this._showMessageResult(input, result);
                });
            } catch {}

            this.isLoading = false;
        },

        _showMessageResult(input, result) {
            const { isSecretKey, key, valid } = result;

            const keyTypeMessage = this.$tc(
                `checkout-payments.config.api.testApiKeys.${
                    isSecretKey ? "secretKey" : "publicKey"
                }`
            );

            const validMessage = this.$tc(
                `checkout-payments.config.api.testApiKeys.${
                    valid ? "isValid" : "isInvalid"
                }`
            );

            const messageData = {
                title: this.$tc(
                    "checkout-payments.config.api.testApiKeys.title"
                ),
                message: `${keyTypeMessage} "${key}" ${validMessage}.`,
            };

            // We remove the error class from the input
            if (input) {
                input.parentNode.parentNode.parentNode.classList.remove(
                    "has--error"
                );
            }

            if (valid) {
                this.createNotificationSuccess(messageData);
            } else {
                this.createNotificationError(messageData);

                // We add the error class from the input
                if (input) {
                    input.parentNode.parentNode.parentNode.classList.add(
                        "has--error"
                    );
                }
            }
        },
    },
});
