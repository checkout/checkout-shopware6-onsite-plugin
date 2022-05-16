import deepmerge from 'deepmerge';
import { APPLE_PAY } from '../../helper/constants';
import ApplePayService from '../../services/apple-pay.service';
import CheckoutComDirectPaymentHandler from '../../core/checkout-com-direct-payment-handler';

/**
 * This Class is responsible for handling Apple Pay direct payment
 */
export default class CheckoutComApplePayDirect extends CheckoutComDirectPaymentHandler {
    static options = deepmerge(CheckoutComDirectPaymentHandler.options, {
        validateMerchantEndpoint: null,
        directButtonClass: '.checkout-com-direct-apple-pay',
    });

    init() {
        super.init();
        this.applePayService = new ApplePayService();

        const directButtons = this.getDirectButtons();
        if (!directButtons) {
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
        session.onshippingmethodselected = this.onShippingMethodSelected.bind(this);
        session.onvalidatemerchant = this.onValidateMerchant.bind(this);
        session.onpaymentauthorized = this.onPaymentAuthorized.bind(this);
        session.oncancel = this.cancelDirectPayment.bind(this);

        this.appleSession = session;
    }

    onValidateMerchant({ validationURL }) {
        const { validateMerchantEndpoint } = this.options;

        this.applePayService.validateMerchant(validateMerchantEndpoint, validationURL, (merchant) => {
            if (!merchant) {
                this.abortApplePay();
                return;
            }

            this.appleSession.completeMerchantValidation(merchant);
        });
    }

    onShippingContactSelected({ shippingContact }) {
        this.getShippingMethods(shippingContact.countryCode)
            .then((result) => {
                const {
                    success,
                    shippingPayload,
                } = result;

                if (!success || !shippingPayload) {
                    this.abortApplePay();
                    return;
                }

                this.appleSession.completeShippingContactSelection(
                    ApplePaySession.STATUS_SUCCESS,
                    shippingPayload.newShippingMethods,
                    shippingPayload.newTotal,
                    shippingPayload.newLineItems,
                );
            });
    }

    onShippingMethodSelected({ shippingMethod }) {
        this.updateShippingPayload(shippingMethod.identifier)
            .then((result) => {
                const {
                    success,
                    shippingPayload,
                } = result;

                if (!success || !shippingPayload) {
                    this.abortApplePay();
                    return;
                }

                this.appleSession.completeShippingMethodSelection(
                    ApplePaySession.STATUS_SUCCESS,
                    shippingPayload.newTotal,
                    shippingPayload.newLineItems,
                );
            });
    }

    onPaymentAuthorized({ payment }) {
        const {
            token,
            shippingContact,
        } = payment;
        this.paymentAuthorized(
            token,
            this.getShippingContactPayload(shippingContact)
        ).then((result) => {
            const {
                redirectUrl,
                success,
            } = result;

            if (redirectUrl) {
                if (success) {
                    this.appleSession.completePayment(ApplePaySession.STATUS_SUCCESS);
                } else {
                    this.appleSession.completePayment(ApplePaySession.STATUS_FAILURE);
                }

                window.location.href = redirectUrl;
            } else {
                this.abortApplePay();
            }
        });
    }

    getShippingContactPayload(appleShippingContact){
        return {
            email: appleShippingContact.emailAddress,
            firstName: appleShippingContact.givenName,
            lastName: appleShippingContact.familyName,
            phoneNumber: appleShippingContact.phoneNumber,
            street: appleShippingContact.addressLines[0] || '',
            additionalAddressLine1: appleShippingContact.addressLines[1] || '',
            zipCode: appleShippingContact.postalCode,
            countryStateCode: appleShippingContact.administrativeArea,
            city: appleShippingContact.locality,
            countryCode: appleShippingContact.countryCode,
        };
    }

    abortApplePay() {
        this.appleSession.abort();
        this.cancelDirectPayment();
    }
}
