import deepmerge from 'deepmerge';
import CheckoutComConfirmPaymentHandler from '../../core/checkout-com-confirm-payment-handler';
import { APPLE_PAY } from '../../helper/constants';
import ApplePayService from '../../services/apple-pay.service';

/**
 * This Class is responsible for the Apple Pay payment
 * It will handle the payment process on the checkout page when the user clicks on the `Confirm` button
 */
export default class CheckoutComApplePayConfirm extends CheckoutComConfirmPaymentHandler {
    static options = deepmerge(CheckoutComConfirmPaymentHandler.options, {
        amount: null,
        currencyCode: null,
        countryCode: null,
        shopName: null,
        validateMerchantPath: null,
    });

    init() {
        this.applePayService = new ApplePayService();
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

        const session = new ApplePaySession(APPLE_PAY.APPLE_PAY_VERSION, {
            countryCode,
            currencyCode,
            merchantCapabilities: APPLE_PAY.MERCHANT_CAPABILITIES,
            supportedNetworks: APPLE_PAY.SUPPORTED_NETWORKS,
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

    onValidateMerchant({ validationURL }) {
        const { validateMerchantPath } = this.options;

        this.applePayService.validateMerchant(validateMerchantPath, validationURL, (merchant) => {
            if (!merchant) {
                this.abortApplePay();
                return;
            }

            this.appleSession.completeMerchantValidation(merchant);
        });
    }

    onPaymentAuthorized({ payment }) {
        const { token } = payment;

        // Submit the token to the server to process the payment
        this.submitPaymentForm(token).then(this.onPaymentResponse.bind(this));
    }

    /**
     * Hande payment result and redirect users to the corresponding result page.
     */
    onPaymentResponse(result) {
        if (!result) {
            this.onApplePayCancel();
            return;
        }

        const {
            success,
            redirectUrl,
        } = result;

        if (success) {
            this.appleSession.completePayment(ApplePaySession.STATUS_SUCCESS);
        } else {
            this.appleSession.completePayment(ApplePaySession.STATUS_FAILURE);
            this.onApplePayCancel();
        }

        // Redirect to the payment result page for both success and failure
        window.location.href = redirectUrl;
    }

    abortApplePay() {
        this.appleSession.abort();
        this.onApplePayCancel();
    }

    onApplePayCancel() {
        this.applePayService.abortApiClient();
        this.removeLoading();
    }
}
