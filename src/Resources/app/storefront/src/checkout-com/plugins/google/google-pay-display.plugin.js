import deepmerge from 'deepmerge';
import CookieStorage from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';
import DisplayPaymentHandler from '../../core/display-payment-handler';
import { COOKIE_KEY, GOOGLE_PAY } from '../../helper/constants';

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

        // If the Google Pay client is not defined, hide everything related to the payment method
        if (!window.googlePayClient) {
            this.toggleDisplayPaymentMethod(false, true);

            return;
        }

        const googlePayAnalytics = CookieStorage.getItem(COOKIE_KEY.GOOGLE_PAY);
        if (!googlePayAnalytics) {
            this.toggleDisplayPaymentMethod(false, false);
            document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this._onCookieConfigurationUpdate.bind(this));

            return;
        }

        this.googleReadyToPay();
    }

    googleReadyToPay() {
        window.googlePayClient
            .isReadyToPay(this.getGoogleIsReadyToPayRequest())
            .then(({ result }) => {
                if (!result) {
                    return;
                }

                this.toggleDisplayPaymentMethod(false, true);
            })
            .catch(() => {
                this.toggleDisplayPaymentMethod(false, true);
            });
    }

    getGoogleIsReadyToPayRequest() {
        return {
            apiVersion: GOOGLE_PAY.API_VERSION,
            apiVersionMinor: GOOGLE_PAY.API_VERSION_MINOR,
            allowedPaymentMethods: [GOOGLE_PAY.BASE_CARD_PAYMENT_METHOD],
        };
    }

    _onCookieConfigurationUpdate(cookieUpdatedEvent) {
        if(!cookieUpdatedEvent.detail){
            return;
        }

        const googlePayCookie = cookieUpdatedEvent.detail[COOKIE_KEY.GOOGLE_PAY];
        if(!googlePayCookie){
            return;
        }

        this.toggleDisplayPaymentMethod(true, false);
        document.$emitter.unsubscribe(COOKIE_CONFIGURATION_UPDATE);
    }
}
