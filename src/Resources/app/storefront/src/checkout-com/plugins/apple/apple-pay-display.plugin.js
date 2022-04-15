import deepmerge from 'deepmerge';
import DisplayPaymentHandler from '../../core/display-payment-handler';

/**
 * This Class is responsible for displaying Apple Pay payment method
 */
export default class CheckoutComApplePayDisplay extends DisplayPaymentHandler {
    static options = deepmerge(DisplayPaymentHandler.options, {
        paymentMethodIdentify: 'data-apple-pay',
    })

    init() {
        super.init();
        const applePaySession = window.ApplePaySession;

        // If Apple Pay is available, we don't need to do anything
        if (applePaySession && applePaySession.canMakePayments()) {
            return;
        }

        // Otherwise, we need to hide the Apple Pay payment method
        this.hideAllRelativeToPaymentMethod();
    }
}
