import deepmerge from 'deepmerge';
import CheckoutComDirectPaymentHandler from '../../core/checkout-com-direct-payment-handler';
import { GOOGLE_PAY } from '../../helper/constants';

const googleIsReadyToPayRequest = {
    apiVersion: GOOGLE_PAY.API_VERSION,
    apiVersionMinor: GOOGLE_PAY.API_VERSION_MINOR,
    allowedPaymentMethods: [GOOGLE_PAY.BASE_CARD_PAYMENT_METHOD],
};

/**
 * This Class is responsible for handling Google Pay direct payment
 */
export default class GooglePayDirectPlugin extends CheckoutComDirectPaymentHandler {
    static options = deepmerge(CheckoutComDirectPaymentHandler.options, {
        directButtonClass: '.checkout-com-direct-google-pay',
        buttonColorName: 'checkoutComButtonColor',
        environment: null,
        merchantId: null,
        publicKey: null,
        totalPriceLabel: null,
        subtotalLabel: null,
        unknownError: null,
    });

    init() {
        super.init();
        const { environment } = this.options;

        const directButtons = this.getDirectButtons();
        if (!directButtons) {
            return;
        }

        // Initialize Google Pay client object
        this.googlePayment = new google.payments.api.PaymentsClient({
            environment,
            paymentDataCallbacks: {
                onPaymentAuthorized: this.onPaymentAuthorized.bind(this),
                onPaymentDataChanged: this.onPaymentDataChanged.bind(this),
            },
        });

        this.googlePayment
            .isReadyToPay(googleIsReadyToPayRequest)
            .then(({ result }) => {
                if (!result) {
                    return;
                }

                directButtons.forEach(this.showGoogleDirectPayButton.bind(this));
            });
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

    showGoogleDirectPayButton(directButton) {
        const {
            buttonColorName,
        } = this.options;

        const buttonColor = this.getInputValue(directButton, buttonColorName);

        const googleButton = this.googlePayment.createButton({
            buttonColor,
            buttonSizeMode: GOOGLE_PAY.BUTTON_SIZE_MODE,
            onClick: () => {
                this.setUpDirectOptions(directButton);
                this.onDirectButtonClick();
            },
        });

        directButton.appendChild(googleButton);
    }

    onDirectButtonClick() {
        this.createPageLoading();

        this.googlePayment.loadPaymentData(this.getGooglePaymentDataRequest())
            .catch(this.cancelDirectPayment.bind(this));
    }

    getGoogleTransactionInfo() {
        const {
            currencyCode,
            countryCode,
        } = this;
        const {
            totalPriceLabel,
            subtotalLabel,
        } = this.options;

        return {
            // Initialize the display items to show the items' information in the Google Pay sheet
            displayItems: [
                {
                    label: subtotalLabel,
                    type: GOOGLE_PAY.TYPE_LINE_ITEM.SUBTOTAL,
                    price: '0',
                },
            ],
            totalPriceLabel,
            countryCode,
            currencyCode,
            totalPriceStatus: GOOGLE_PAY.TOTAL_PRICE_STATUS,
            totalPrice: '0',
        };
    }

    getGooglePaymentDataRequest() {
        const {
            shopName,
            merchantId,
        } = this.options;

        return {
            ...googleIsReadyToPayRequest,
            allowedPaymentMethods: [this.getCardPaymentMethod()],
            transactionInfo: this.getGoogleTransactionInfo(),
            merchantInfo: {
                merchantId,
                merchantName: shopName,
            },
            callbackIntents: [
                GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_ADDRESS,
                GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_OPTION,
                GOOGLE_PAY.CALLBACK_TRIGGER.PAYMENT_AUTHORIZATION,
            ],
            emailRequired: true,
            shippingAddressParameters: {
                phoneNumberRequired: true,
            },
            shippingAddressRequired: true,
            shippingOptionRequired: true,
        };
    }

    onPaymentAuthorized(paymentData) {
        const {
            email,
            paymentMethodData,
            shippingAddress,
        } = paymentData;
        return this.paymentAuthorized(
            JSON.parse(paymentMethodData.tokenizationData.token),
            this.getShippingContactPayload(email, shippingAddress),
        ).then((result) => {
            const { redirectUrl } = result;

            if (!redirectUrl) {
                return {
                    transactionState: 'ERROR',
                    error: this.getErrorResponse(GOOGLE_PAY.CALLBACK_TRIGGER.PAYMENT_AUTHORIZATION),
                };
            }

            window.location.href = redirectUrl;

            return { transactionState: 'SUCCESS' };
        });
    }

    onPaymentDataChanged(intermediatePaymentData) {
        const {
            shippingAddress,
            shippingOptionData,
            callbackTrigger,
        } = intermediatePaymentData;

        switch (callbackTrigger) {
            case GOOGLE_PAY.CALLBACK_TRIGGER.INITIALIZE:
                return this.initDirectPayment().then((success) => {
                    if (!success) {
                        return {};
                    }

                    return this.getNewShippingMethods(shippingAddress.countryCode);
                });
            case GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_ADDRESS:
                return this.getNewShippingMethods(shippingAddress.countryCode);
            case GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_OPTION:
                return this.calculateCartByShippingMethod(shippingOptionData.id);
            default:
                return Promise.resolve({});
        }
    }

    /**
     * Get new shipping methods and calculate the direct cart
     *
     * @param {string} countryCode
     * @returns {Promise<Object>}
     */
    getNewShippingMethods(countryCode) {
        return this.getShippingMethods(countryCode)
            .then((result) => {
                const {
                    success,
                    shippingPayload,
                } = result;

                if (!success || !shippingPayload) {
                    return this.getErrorResponse(GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_ADDRESS);
                }

                return {
                    newTransactionInfo: {
                        ...this.getGoogleTransactionInfo(),
                        totalPrice: shippingPayload.totalPrice,
                        displayItems: shippingPayload.displayItems,
                    },
                    newShippingOptionParameters: {
                        shippingOptions: shippingPayload.shippingOptions,
                    },
                };
            });
    }

    /**
     * Calculate the direct cart by shipping method ID
     *
     * @param {string} shippingMethodId
     * @returns {Promise<Object>}
     */
    calculateCartByShippingMethod(shippingMethodId) {
        return this.updateShippingPayload(shippingMethodId)
            .then((result) => {
                const {
                    success,
                    shippingPayload,
                } = result;

                if (!success || !shippingPayload) {
                    return this.getErrorResponse(GOOGLE_PAY.CALLBACK_TRIGGER.SHIPPING_OPTION);
                }

                return {
                    newTransactionInfo: {
                        ...this.getGoogleTransactionInfo(),
                        totalPrice: shippingPayload.totalPrice,
                        displayItems: shippingPayload.displayItems,
                    },
                };
            });
    }

    /**
     * Get Google Pay error response
     * @param {string} intent
     * @returns {{error: {reason: string, message: string, intent: string}}}
     */
    getErrorResponse(intent) {
        const { unknownError } = this.options;

        return {
            error: {
                message: unknownError,
                reason: 'OTHER_ERROR',
                intent,
            },
        };
    }

    /**
     * Get Google Pay name data
     *
     * @param {string} fullName
     * @returns {{firstName: string, lastName: string}}
     */
    getNameData(fullName) {
        const [firstName, ...lastNameData] = fullName.split(' ');

        return {
            firstName: firstName || '',
            lastName: lastNameData.join(' ') || '',
        };
    }

    getShippingContactPayload(email, googleShippingAddress){
        const nameData = this.getNameData(googleShippingAddress.name);

        return {
            email,
            firstName: nameData.firstName,
            lastName: nameData.lastName,
            phoneNumber: googleShippingAddress.phoneNumber,
            street: googleShippingAddress.address1,
            additionalAddressLine1: googleShippingAddress.address2,
            zipCode: googleShippingAddress.postalCode,
            countryStateCode: googleShippingAddress.administrativeArea,
            city: googleShippingAddress.locality,
            countryCode: googleShippingAddress.countryCode,
        };
    }
}
