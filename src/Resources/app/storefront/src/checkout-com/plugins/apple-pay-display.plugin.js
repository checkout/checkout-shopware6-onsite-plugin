import Plugin from "src/plugin-system/plugin.class";
import DomAccess from "src/helper/dom-access.helper";

/**
 * This Class is responsible for the Apple Pay Display for Apple Devices.
 * The display only shows if the browser has ApplePaySession
 */
export default class CheckoutComApplePayDisplay extends Plugin {
    static options = {
        selectedPaymentMethodId: null,
        paymentFormId: "form#confirmOrderForm",
        paymentMethodClass: ".payment-method",
        checkoutPaymentMethodClass: ".checkout-com-payment-method",
        prefixCheckoutPaymentMethodId: "#checkoutComPaymentMethod",
        dataPaymentMethodId: "data-payment-method-id",
        dataApplePay: "data-apple-pay",
    };

    init() {
        const applePaySession = window.ApplePaySession;

        // Check if Apple Pay is available, we don't need to do anything
        if (applePaySession && applePaySession.canMakePayments()) {
            return;
        }

        // Otherwise, we need to hide the Apple Pay payment method
        this.hideApplePay();
    }

    /**
     * Hide the Apple Pay payment method option on the checkout page
     * Hide the `Submit button` on the payment form if the Apple Pay payment method is selected
     */
    hideApplePay() {
        const {
            selectedPaymentMethodId,
            prefixCheckoutPaymentMethodId,
            paymentFormId,
            checkoutPaymentMethodClass,
            dataPaymentMethodId,
            dataApplePay,
        } = this.options;

        const checkoutPaymentMethods = DomAccess.querySelectorAll(
            document,
            checkoutPaymentMethodClass,
            false
        );

        if (!checkoutPaymentMethods) {
            return;
        }

        checkoutPaymentMethods.forEach((paymentMethod) => {
            const id = paymentMethod.getAttribute(dataPaymentMethodId);
            const isApplePay = paymentMethod.getAttribute(dataApplePay);

            if (!isApplePay || !id) {
                // If the payment method is not an Apple Pay payment method or empty ID, we don't need to do anything
                return;
            }

            // We only remove the `Submit Form` whenever the payment method ID is the same and the selected payment method ID is not null.
            if (selectedPaymentMethodId && selectedPaymentMethodId === id) {
                this.removeSubmitForm(paymentFormId);
            }

            this.removePaymentMethodById(
                `${prefixCheckoutPaymentMethodId}${id}`
            );
        });
    }

    removePaymentMethodById(innerIdentifier) {
        const { paymentMethodClass } = this.options;
        const element = DomAccess.querySelector(
            document,
            innerIdentifier,
            false
        );
        if (!element) {
            return;
        }

        const rootElement = element.closest(paymentMethodClass);

        if (!!rootElement) {
            rootElement.remove();
        }
    }

    removeSubmitForm(innerIdentifierButton) {
        const element = DomAccess.querySelector(
            document,
            innerIdentifierButton,
            false
        );

        if (!!element) {
            element.remove();
        }
    }
}
