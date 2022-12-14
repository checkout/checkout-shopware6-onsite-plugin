import deepmerge from 'deepmerge';
import CheckoutComConfirmPaymentHandler from '../../core/checkout-com-confirm-payment-handler';
import { GOOGLE_PAY } from '../../helper/constants';

/**
 * This Class is responsible for the Google Pay payment
 * It will handle the payment process on the checkout page when the user clicks on the `Confirm` button
 */
export default class CheckoutComGooglePayConfirm extends CheckoutComConfirmPaymentHandler {
    static options = deepmerge(CheckoutComConfirmPaymentHandler.options, {
        amount: null,
        currencyCode: null,
        countryCode: null,
        shopName: null,
        publicKey: null,
        merchantId: null,
    });

    // Begin submit Google Pay
    onConfirmFormSubmit() {
        window.googlePayClient
            .loadPaymentData(this.getGooglePaymentDataRequest())
            .then(({ paymentMethodData }) => {
                const {
                    tokenizationData: { token },
                } = paymentMethodData;

                return this.submitAjaxPaymentForm(JSON.parse(token));
            })
            .then((result) => {
                if (!result) {
                    this.onGooglePayCancel();
                    return;
                }

                const { redirectUrl } = result;

                // Redirect to the payment result page for both success and failure
                window.location.href = redirectUrl;
            })
            .catch(this.onGooglePayCancel.bind(this));
    }

    onGooglePayCancel() {
        this.removeLoading();
    }

    getCardPaymentMethod() {
        const { publicKey } = this.options;

        return {
            ...GOOGLE_PAY.BASE_CARD_PAYMENT_METHOD,
            tokenizationSpecification: {
                type: GOOGLE_PAY.PAYMENT_GATEWAY,
                parameters: {
                    gateway: GOOGLE_PAY.GATEWAY,
                    gatewayMerchantId: publicKey,
                },
            },
        };
    }

    getGooglePaymentDataRequest() {
        const {
            amount,
            currencyCode,
            countryCode,
            shopName,
            merchantId,
        } = this.options;

        return {
            apiVersion: GOOGLE_PAY.API_VERSION,
            apiVersionMinor: GOOGLE_PAY.API_VERSION_MINOR,
            allowedPaymentMethods: [this.getCardPaymentMethod()],
            transactionInfo: {
                countryCode,
                currencyCode,
                totalPriceStatus: GOOGLE_PAY.TOTAL_PRICE_STATUS,
                totalPrice: amount,
            },
            merchantInfo: {
                merchantId,
                merchantName: shopName,
            },
        };
    }
}
