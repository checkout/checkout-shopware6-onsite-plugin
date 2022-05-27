import deepmerge from 'deepmerge';
import { createSourceInput, getInputValue } from '../helper/utils';
import CheckoutComConfirmPaymentHandler from '../core/checkout-com-confirm-payment-handler';

/**
 * This Class is responsible for the IDEAL integration
 */
export default class CheckoutComIdeal extends CheckoutComConfirmPaymentHandler {
    static options = deepmerge(CheckoutComConfirmPaymentHandler.options, {
        bicId: '#idealBic',
    });

    BIC_SOURCE = 'bic';

    onConfirmFormSubmit() {
        // Get the closest form to check if it is valid
        const closetForm = this.el.closest('form');
        if (!closetForm) {
            this.removeLoading();
            return;
        }

        if (!closetForm.checkValidity()) {
            this.addElementValidate();
            this.removeLoading();
            return;
        }

        this.removeElementValidate();
        this.submitForm();
    }

    submitForm() {
        const { bicId } = this.options;
        const bic = getInputValue(this.el, bicId);
        const input = createSourceInput(this.BIC_SOURCE, bic);

        // Add the bic input to the form
        // It will be sent to the server along with the form.
        this.paymentForm.append(input);

        // Continue to submit shopware payment form
        this.paymentForm.submit();
    }

    addElementValidate() {
        this.el.classList.add('was-validated');
    }

    removeElementValidate() {
        this.el.classList.remove('was-validated');
    }
}
