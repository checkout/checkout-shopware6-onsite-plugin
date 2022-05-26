import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import { createSourceInput } from '../helper/utils';

/**
 * This Class is responsible for the SEPA integration
 */
export default class CheckoutComSepa extends Plugin {
    static options = {
        firstNameId: '#sepaFirstName',
        lastNameId: '#sepaLastName',
        ibanId: '#sepaIban',
        paymentFormId: '#confirmOrderForm',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
    };

    init() {
        this.client = new HttpClient();
        const {
            submitPaymentButtonId,
            paymentFormId,
        } = this.options;

        this.submitPaymentButton = DomAccess.querySelector(document, submitPaymentButtonId, false);
        this.submitButtonLoader = new ButtonLoadingIndicator(this.submitPaymentButton);
        this.paymentForm = DomAccess.querySelector(document, paymentFormId, false);

        this.registerEvents();
    }

    registerEvents() {
        // Submit payment form handler
        this.submitPaymentButton.addEventListener('click', this.onSubmitPaymentClick.bind(this));
    }

    onSubmitPaymentClick(event) {
        event.preventDefault();

        // checks form validity before submit
        if (!this.paymentForm.checkValidity()) {
            return;
        }

        // Get the closest form to check if it is valid
        const closestForm = this.el.closest('form');
        if (!closestForm) {
            return;
        }

        if (!closestForm.checkValidity()) {
            this.addElementValidate();
            return;
        }

        this.removeElementValidate();
        this.createLoading();
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
                value: this.getInputValue(firstNameId),
            },
            {
                field: 'lastName',
                value: this.getInputValue(lastNameId),
            },
            {
                field: 'iban',
                value: this.getInputValue(ibanId),
            },
        ];
    }

    getInputValue(elementId) {
        const input = DomAccess.querySelector(this.el, elementId, false);
        if (!input || !input.value) {
            throw new Error(`No ${elementId} found`);
        }

        return input.value;
    }

    addElementValidate() {
        this.el.classList.add('was-validated');
    }

    removeElementValidate() {
        this.el.classList.remove('was-validated');
    }

    createLoading() {
        this.submitButtonLoader.create();
    }

    removeLoading() {
        this.submitButtonLoader.remove();
    }
}
