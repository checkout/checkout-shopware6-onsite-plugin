import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

/**
 * This handler is responsible for showing/hiding payment methods based on the browser's compatibility.
 * Every payment method handler will extend this base handler to handle the logic for certain payment method.
 */
export default class DisplayPaymentHandler extends Plugin {
    static options = {
        active: null,
        selectedPaymentMethodId: null,
        paymentFormId: 'form#confirmOrderForm',
        paymentMethodClass: '.payment-method',
        checkoutPaymentMethodClass: '.checkout-com-payment-method',
        checkoutDirectPayContainerClass: '.checkout-com-direct-pay-container',
        checkoutDirectPayButtonClass: '.checkout-com-direct-pay',
        prefixCheckoutPaymentMethodId: '#checkoutComPaymentMethod',
        dataPaymentMethodId: 'data-payment-method-id',
        hiddenClass: 'd-none',
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
        const {
            paymentMethodIdentify,
            active,
        } = this.options;
        if (!paymentMethodIdentify) {
            throw new Error(`The "paymentMethodIdentify" option for the plugin "${this._pluginName}" is not defined.`);
        }

        if (active === null) {
            throw new Error(`The "active" option for the plugin "${this._pluginName}" is not defined.`);
        }

        if (!active) {
            // If the Payment Method is not active, hide everything related to this Payment Method
            this.hideUnavailablePaymentMethod();

            return false;
        }

        return true;
    }

    update() {
        this.init();
    }

    /**
     * Hide the direct pay buttons
     * Hide the payment method option on the checkout page
     * Hide the `Submit button` on the payment form if the payment method is selected
     */
    hideUnavailablePaymentMethod() {
        this._removePaymentOptions();
        this.showDirectButtons(false);
    }

    /**
     * Show/Remove all direct pay buttons
     *
     * @param {boolean} isShow
     */
    showDirectButtons(isShow = true) {
        const {
            paymentMethodIdentify,
            checkoutDirectPayContainerClass,
            checkoutDirectPayButtonClass,
            hiddenClass,
        } = this.options;

        const checkoutDirectPayButtons = DomAccess.querySelectorAll(document, checkoutDirectPayButtonClass, false);

        if (!checkoutDirectPayButtons) {
            return;
        }

        checkoutDirectPayButtons.forEach((checkoutDirectPayButton) => {
            const hasPaymentMethodIdentify = checkoutDirectPayButton.hasAttribute(paymentMethodIdentify);
            if (!hasPaymentMethodIdentify) {
                // If the payment method does not have the `hasPaymentMethodIdentify` attribute, we don't need to do anything
                return;
            }

            const directPayContainer = checkoutDirectPayButton.closest(checkoutDirectPayContainerClass);

            if (directPayContainer) {
                if (!isShow) {
                    directPayContainer.remove();

                    // No need to do anything because the closest element has already been removed
                    return;
                }

                // Because the container already has the `hiddenClass` class, we need to remove it
                directPayContainer.classList.remove(hiddenClass);
            }

            if (isShow) {
                // Because the button already has the `hiddenClass` class, we need to remove it
                checkoutDirectPayButton.classList.remove(hiddenClass);
                return;
            }

            checkoutDirectPayButton.remove();
        });
    }

    /**
     * Remove all payment options and disable the submit button depending on the selected payment method
     */
    _removePaymentOptions() {
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
            const hasPaymentMethodIdentify = paymentMethod.hasAttribute(paymentMethodIdentify);

            if (!hasPaymentMethodIdentify || !id) {
                // If the payment method does not have the `hasPaymentMethodIdentify` attribute or the `id` attribute, we don't need to do anything
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

        if (rootElement) {
            rootElement.remove();
        }
    }

    _removeSubmitForm(innerIdentifierButton) {
        const element = DomAccess.querySelector(document, innerIdentifierButton, false);

        if (element) {
            element.remove();
        }
    }
}
