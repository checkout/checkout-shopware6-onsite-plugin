import deepmerge from 'deepmerge';
import DisplayPaymentHandler from '../../core/display-payment-handler';
import { GOOGLE_PAY } from '../../helper/constants';

/**
 * This Class is responsible for displaying Google Pay payment method
 */
export default class CheckoutComGooglePayDisplay extends DisplayPaymentHandler {
    static options = deepmerge(DisplayPaymentHandler.options, {
        paymentMethodIdentify: 'data-google-pay',
    });

    init() {
        const active = super.init();
        if (!active) {
            return;
        }

        const googlePayClient = window.googlePayClient;

        // If the Google Pay client is not defined, hide everything related to the payment method
        if (!googlePayClient) {
            this.hideUnavailablePaymentMethod();

            return;
        }

        googlePayClient
            .isReadyToPay(this.getGoogleIsReadyToPayRequest())
            .then(({ result }) => {
                if (!result) {
                    return;
                }

                this.showDirectButtons();
            })
            .catch(() => {
                this.hideUnavailablePaymentMethod();
            });
    }

    getGoogleIsReadyToPayRequest() {
        return {
            apiVersion: GOOGLE_PAY.API_VERSION,
            apiVersionMinor: GOOGLE_PAY.API_VERSION_MINOR,
            allowedPaymentMethods: [GOOGLE_PAY.BASE_CARD_PAYMENT_METHOD],
        };
    }
}
