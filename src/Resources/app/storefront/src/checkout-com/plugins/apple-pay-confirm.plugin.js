import deepmerge from 'deepmerge';
import StoreApiClient from 'src/service/store-api-client.service';
import CheckoutPaymentHandler from '../core/checkout-payment-handler';

/**
 * This Class is responsible for the Apple Pay payment
 * It will handle the payment process on the checkout page when the user clicks on the `Confirm` button
 */
export default class CheckoutComApplePayConfirm extends CheckoutPaymentHandler {
    APPLE_PAY_VERSION = 3;
    MERCHANT_CAPABILITIES = [
        'supports3DS',
    ];
    SUPPORTED_NETWORKS = [
        'visa',
        'masterCard',
        'amex',
        'discover',
    ];

    static options = deepmerge(CheckoutPaymentHandler.options, {
        amount: null,
        currencyCode: null,
        countryCode: null,
        shopName: null,
        validateMerchantPath: null,
    });

    init() {
        this.storeApiClient = new StoreApiClient();
        super.init();
    }

    // Begin apple pay session
    onConfirmFormSubmit() {
        const {
            amount,
            currencyCode,
            countryCode,
            shopName,
        } = this.options;

        const session = new ApplePaySession(this.APPLE_PAY_VERSION, {
            countryCode,
            currencyCode,
            merchantCapabilities: this.MERCHANT_CAPABILITIES,
            supportedNetworks: this.SUPPORTED_NETWORKS,
            total: {
                label: shopName,
                amount,
            },
        });
        session.onvalidatemerchant = this.onValidateMerchant.bind(this);
        session.onpaymentauthorized = this.onPaymentAuthorized.bind(this);
        session.oncancel = this.onApplePayCancel.bind(this);
        session.begin();

        this.appleSession = session;
    }

    /**
     * This will be called when the Apple Pay session is starting to validate the merchant.
     */
    onValidateMerchant({ validationURL }) {
        const { validateMerchantPath } = this.options;
        const data = JSON.stringify({
            validationURL,
        });

        this.storeApiClient.post(validateMerchantPath, data, (result) => {
            if (!result) {
                this.abortApplePay();
                return;
            }

            let { merchant } = JSON.parse(result);
            if (!merchant) {
                this.abortApplePay();
                return;
            }

            // Need to commit the merchant to the Apple Pay session to let it know that we are ready to process the payment
            this.appleSession.completeMerchantValidation(merchant);
        });
    }

    onPaymentAuthorized({ payment }) {
        const { token } = payment;

        // Submit the token to the server to process the payment
        this.submitPaymentForm(token);
    }

    onPaymentResponse(success, redirectUrl) {
        // @TODO Will implement this in the future to complete the payment
        this.appleSession.completePayment(ApplePaySession.STATUS_FAILURE);
        this.onApplePayCancel();
    }

    abortApplePay() {
        this.appleSession.abort();
        this.removeLoading();
    }

    onApplePayCancel() {
        this.storeApiClient.abort();
        this.removeLoading();
    }
}
