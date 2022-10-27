import deepmerge from 'deepmerge';
import CookieStorage from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';
import DisplayPaymentHandler from '../../core/display-payment-handler';
import { COOKIE_KEY, GOOGLE_PAY } from '../../helper/constants';
import { loadScript } from '../../helper/utils';

export const GOOGLE_PAY_READY_PAY = 'CheckoutCom_GooglePayReadyPay'

/**
 * This Class is responsible for displaying Google Pay payment method
 */
export default class CheckoutComGooglePayDisplay extends DisplayPaymentHandler {
    static options = deepmerge(DisplayPaymentHandler.options, {
        paymentMethodIdentify: 'data-google-pay',
        environment: null,
    });

    init() {
        const active = super.init();
        if (!active) {
            return;
        }

        const googlePayAnalytics = CookieStorage.getItem(COOKIE_KEY.GOOGLE_PAY);
        if (googlePayAnalytics) {
            this.googleReadyToPay().then(() => {
                this.showDirectButtons();
            });

            return;
        }

        // If Google Pay Analytics from Cookie is not accepted, we hide all relative to this payment method,
        // and listen to `cookie configuration update` event
        this.toggleDisplayPaymentMethod(false, false);
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this._onCookieConfigurationUpdate.bind(this));
    }

    async googleReadyToPay() {
        await this.loadGoogleScript()

        return new Promise((resolve) => {
            window.googlePayClient
                .isReadyToPay(this.getGoogleIsReadyToPayRequest())
                .then(({ result }) => {
                    if (!result) {
                        return;
                    }

                    // Emit event Google Pay is ready to Pay
                    document.$emitter.publish(GOOGLE_PAY_READY_PAY);
                    resolve()
                })
                .catch(() => {
                    this.toggleDisplayPaymentMethod(false, true);
                });
        })
    }

    async loadGoogleScript() {
        const { environment } = this.options
        await loadScript(GOOGLE_PAY.LIBRARY_URL);

        window.googlePayClient = new google.payments.api.PaymentsClient({
            environment,
        })
    }

    getGoogleIsReadyToPayRequest() {
        return {
            apiVersion: GOOGLE_PAY.API_VERSION,
            apiVersionMinor: GOOGLE_PAY.API_VERSION_MINOR,
            allowedPaymentMethods: [GOOGLE_PAY.BASE_CARD_PAYMENT_METHOD],
        };
    }

    _onCookieConfigurationUpdate(cookieUpdatedEvent) {
        if (!cookieUpdatedEvent.detail) {
            return;
        }

        const googlePayCookie = cookieUpdatedEvent.detail[COOKIE_KEY.GOOGLE_PAY];
        if (!googlePayCookie) {
            return;
        }

        document.$emitter.unsubscribe(COOKIE_CONFIGURATION_UPDATE);

        this.googleReadyToPay().then(() => {
            this.toggleDisplayPaymentMethod(true, false);
        })
    }
}
