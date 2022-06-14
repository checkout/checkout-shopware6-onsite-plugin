import deepmerge from 'deepmerge';
import { createSourceInput, getInputValue } from '../helper/utils';
import CheckoutComConfirmPaymentHandler from '../core/checkout-com-confirm-payment-handler';

/**
 * This Class is responsible for the SEPA integration
 */
export default class CheckoutComSepa extends CheckoutComConfirmPaymentHandler {
    static options = deepmerge(CheckoutComConfirmPaymentHandler.options, {
        firstNameId: '#sepaFirstName',
        lastNameId: '#sepaLastName',
        ibanId: '#sepaIban',
    });

    onConfirmFormSubmit() {
        // Get the closest form to check if it is valid
        const closestForm = this.el.closest('form');
        if (!closestForm) {
            this.removeLoading();
            return;
        }

        if (!closestForm.checkValidity()) {
            this.addElementValidate();
            this.removeLoading();
            return;
        }

        this.removeElementValidate();
        this.submitForm();
    }

    submitForm() {
        const inputFields = this.getInputFieldsData();

        inputFields.forEach((inputField) => {
            // Add the input to the form
            // It will be sent to the server along with the form.
            this.paymentForm.append(createSourceInput(inputField.field, inputField.value));
        });

        // Continue to submit shopware payment form
        this.paymentForm.submit();
    }

    getInputFieldsData() {
        const {
            firstNameId,
            lastNameId,
            ibanId,
        } = this.options;

        return [
            {
                field: 'firstName',
                value: getInputValue(this.el, firstNameId),
            },
            {
                field: 'lastName',
                value: getInputValue(this.el, lastNameId),
            },
            {
                field: 'iban',
                value: getInputValue(this.el, ibanId),
            },
        ];
    }

    addElementValidate() {
        this.el.classList.add('was-validated');
    }

    removeElementValidate() {
        this.el.classList.remove('was-validated');
    }
}
