import deepmerge from 'deepmerge';
import { APPLE_PAY } from '../../helper/constants';
import ApplePayService from '../../services/apple-pay.service';
import CheckoutComDirectPaymentHandler from '../../core/checkout-com-direct-payment-handler';

/**
 * This Class is responsible for the direct Apple Pay
 */
export default class CheckoutComApplePayDirect extends CheckoutComDirectPaymentHandler {
    static options = deepmerge(CheckoutComDirectPaymentHandler.options, {
        validateMerchantPath: null,
        directButtonClass: '.checkout-com-direct-apple-pay',
    });

    init() {
        super.init();
        this.applePayService = new ApplePayService();

        const directButtons = this.getDirectButtons();
        if (directButtons.length === 0) {
            return;
        }

        const applePaySession = window.ApplePaySession;

        // Stop the process if Apple Pay cannot make a payment
        if (!applePaySession || !applePaySession.canMakePayments()) {
            return;
        }

        directButtons.forEach((directButton) => {
            directButton.addEventListener('click', (event) => {
                this.setUpDirectOptions(directButton);
                this.onDirectButtonClick(event);
            });
        });
    }

    onDirectButtonClick(e) {
        e.preventDefault();
        this.setupApplePaySession();

        this.createPageLoading();
        this.initDirectPayment().then((success) => {
            if (!success) {
                this.cancelDirectPayment();
                return;
            }

            // Start Apple Pay session
            this.appleSession.begin();
        });
    }

    // Setup apple pay session
    setupApplePaySession() {
        const {
            currencyCode,
            countryCode,
        } = this;
        const { shopName } = this.options;

        const session = new ApplePaySession(APPLE_PAY.APPLE_PAY_VERSION, {
            countryCode,
            currencyCode,
            requiredShippingContactFields: APPLE_PAY.requiredShippingContactFields,
            merchantCapabilities: APPLE_PAY.MERCHANT_CAPABILITIES,
            supportedNetworks: APPLE_PAY.SUPPORTED_NETWORKS,
            total: {
                label: shopName,
                amount: 0, // Just put a 0 amount value here, will update it later
                // because the Apple Pay Session must be handling in the user gesture
            },
        });
        session.onshippingcontactselected = this.onShippingContactSelected.bind(this);
        session.onvalidatemerchant = this.onValidateMerchant.bind(this);
        session.onpaymentauthorized = this.onPaymentAuthorized.bind(this);
        session.oncancel = this.cancelDirectPayment.bind(this);

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

    onShippingContactSelected(event) {
        // @TODO: implement shipping contact selected for direct Apple Pay
    }

    onPaymentAuthorized({ payment }) {
        // @TODO: implement payment authorized for direct Apple Pay
    }

    abortApplePay() {
        this.appleSession.abort();
        this.cancelDirectPayment();
    }
}
