import Plugin from 'src/plugin-system/plugin.class';
import isEmpty from 'lodash/isEmpty';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import { createSourceIdInput } from '../helper/utils';

/**
 * This plugin handles the payment process for source payments if the customer selects the stored card
 */
export default class CheckoutComSourcePaymentHandler extends Plugin {
    static options = {
        checkoutSourceIdInput: 'input[name="checkoutComSourceId"]',
        checkoutComShouldSaveSourceInput: 'input[name="checkoutComShouldSaveSource"]',
        paymentFormId: '#confirmOrderForm',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
    };

    init() {
        const {
            checkoutSourceIdInput,
            submitPaymentButtonId,
            paymentFormId,
        } = this.options;

        this.submitPaymentButton = DomAccess.querySelector(document, submitPaymentButtonId, false);
        this.paymentForm = DomAccess.querySelector(document, paymentFormId, false);
        this.radioSourceIdInput = DomAccess.querySelectorAll(document, checkoutSourceIdInput, false);
        if (!this.submitPaymentButton || !this.paymentForm) {
            return;
        }

        this.client = new HttpClient();
        this.submitButtonLoader = new ButtonLoadingIndicator(this.submitPaymentButton);

        this.registerRadioButtonsEvent();
        this.registerSubmitButtonEvent();
    }

    registerRadioButtonsEvent() {
        // Init the source change on plugin init
        this.onSourceChange(this.getSourceInputValue());
        if (!this.radioSourceIdInput) {
            return;
        }

        this.radioSourceIdInput.forEach(radioButton => {
            radioButton.addEventListener('change', () => {
                this.onSourceChange(this.getSourceInputValue());
            });
        });
    }

    registerSubmitButtonEvent() {
        this.submitPaymentButton.addEventListener('click', (event) => {
            event.preventDefault();

            // checks form validity before submit
            if (!this.paymentForm.checkValidity()) {
                return;
            }

            this.createLoading();

            const sourceId = this.getSourceInputValue();
            // If the sourceId is not empty, submit the payment form with sourceId to the server
            if (sourceId) {
                this.submitPaymentSourceIdForm(sourceId);
                return;
            }

            this.onConfirmFormSubmit(event);
        });
    }

    submitPaymentSourceIdForm(sourceId) {
        const input = createSourceIdInput(sourceId);

        // It will be sent to the server along with the form.
        this.paymentForm.append(input);

        // Continue to submit shopware payment form
        this.paymentForm.submit();
    }

    getSourceInputValue() {
        const { checkoutSourceIdInput } = this.options;

        const sourceCheckedInput = DomAccess.querySelector(document, `${checkoutSourceIdInput}:checked`, false);
        if (!sourceCheckedInput) {
            return null;
        }

        if (isEmpty(sourceCheckedInput.value) || sourceCheckedInput.value === 'null') {
            return null;
        }

        return sourceCheckedInput.value;
    }

    shouldSaveSource() {
        const { checkoutComShouldSaveSourceInput } = this.options;

        const shouldSaveSourceInput = DomAccess.querySelector(document, checkoutComShouldSaveSourceInput, false);
        if (!shouldSaveSourceInput) {
            return false;
        }

        return shouldSaveSourceInput.checked;
    }

    /**
     * This method is called when the user clicks on the stored card radio button
     */
    onSourceChange() {
        throw new Error(`The "onSourceChange" method for the plugin "${this._pluginName}" is not defined.`);
    }

    /**
     * This method is called when the user clicks on the submit button on the payment form.
     */
    onConfirmFormSubmit() {
        throw new Error(`The "onConfirmFormSubmit" method for the plugin "${this._pluginName}" is not defined.`);
    }

    /**
     * Create loading indicator for submit button
     */
    createLoading() {
        this.submitButtonLoader.create();
    }

    /**
     * Remove loading indicator for submit button
     */
    removeLoading() {
        this.submitButtonLoader.remove();
    }
}
