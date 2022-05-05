import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

/**
 * This handler is responsible for showing/hiding payment methods based on the browser's compatibility.
 * Every payment method handler will extend this base handler to handle the logic for certain payment method.
 */
export default class DisplayPaymentHandler extends Plugin {
    static options = {
        selectedPaymentMethodId: null,
        paymentFormId: 'form#confirmOrderForm',
        paymentMethodClass: '.payment-method',
        checkoutPaymentMethodClass: '.checkout-com-payment-method',
        prefixCheckoutPaymentMethodId: '#checkoutComPaymentMethod',
        dataPaymentMethodId: 'data-payment-method-id',
    };

    init() {
        // This property is required to be set in the plugin options
        // The purpose is to remove the container containing this element.
        // Note that the container must have our defined class in the plugin options
        // Example:
        //   <div class="payment-method"> -- this container will be removed
        //       <div class="checkout-com-payment-method" data-apple-hide="true">
        //       <option>Apple</option>
        //   </div>
        const { paymentMethodIdentify } = this.options;
        if (!paymentMethodIdentify) {
            throw new Error('paymentMethodIdentify is not defined');
        }
    }

    /**
     * Hide the payment method option on the checkout page
     * Hide the `Submit button` on the payment form if the payment method is selected
     */
    hideAllRelativeToPaymentMethod() {
        const {
            selectedPaymentMethodId,
            prefixCheckoutPaymentMethodId,
            paymentFormId,
            checkoutPaymentMethodClass,
            dataPaymentMethodId,
            paymentMethodIdentify,
        } = this.options;

        const checkoutPaymentMethods = DomAccess.querySelectorAll(document, checkoutPaymentMethodClass, false);

        if (!checkoutPaymentMethods) {
            return;
        }

        checkoutPaymentMethods.forEach((paymentMethod) => {
            const id = paymentMethod.getAttribute(dataPaymentMethodId);
            const isPaymentMethodHidden = paymentMethod.getAttribute(paymentMethodIdentify);

            if (!isPaymentMethodHidden || !id) {
                // If the payment method does not have the `isPaymentMethodHidden` attribute or the `id` attribute, we don't need to do anything
                return;
            }

            // We only remove the `Submit Form` whenever the payment method ID is the same as the selected payment method
            // and the selected payment method ID is not null.
            if (selectedPaymentMethodId && selectedPaymentMethodId === id) {
                this._removeSubmitForm(paymentFormId);
            }

            this._removePaymentMethodById(`${prefixCheckoutPaymentMethodId}${id}`);
        });
    }

    _removePaymentMethodById(innerIdentifier) {
        const { paymentMethodClass } = this.options;
        const element = DomAccess.querySelector(document, innerIdentifier, false);
        if (!element) {
            return;
        }

        const rootElement = element.closest(paymentMethodClass);

        if (!!rootElement) {
            rootElement.remove();
        }
    }

    _removeSubmitForm(innerIdentifierButton) {
        const element = DomAccess.querySelector(document, innerIdentifierButton, false);

        if (!!element) {
            element.remove();
        }
    }
}
