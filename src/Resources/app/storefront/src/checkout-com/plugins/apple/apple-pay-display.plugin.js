import deepmerge from 'deepmerge';
import DisplayPaymentHandler from '../../core/display-payment-handler';

/**
 * This Class is responsible for displaying Apple Pay payment method
 */
export default class CheckoutComApplePayDisplay extends DisplayPaymentHandler {
    static options = deepmerge(DisplayPaymentHandler.options, {
        paymentMethodIdentify: 'data-apple-pay',
    });

    init() {
        const active = super.init();
        if (!active) {
            return;
        }

        const applePaySession = window.ApplePaySession;

        if (applePaySession && applePaySession.canMakePayments()) {
            this.showDirectButtons();
            return;
        }

        // Otherwise, we need to hide the Apple Pay payment method
        this.hideUnavailablePaymentMethod();
    }
}
