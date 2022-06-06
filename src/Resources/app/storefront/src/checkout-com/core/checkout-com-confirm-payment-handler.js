import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from '../services/http-client.service';
import { DATA_BAG_KEY } from '../helper/constants';

/**
 * This plugin handles the payment process for the checkout order form.
 */
export default class CheckoutComConfirmPaymentHandler extends Plugin {
    static options = {
        paymentFormId: '#confirmOrderForm',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
    };

    init() {
        const {
            submitPaymentButtonId,
            paymentFormId,
        } = this.options;

        this.submitPaymentButton = DomAccess.querySelector(document, submitPaymentButtonId, false);
        this.paymentForm = DomAccess.querySelector(document, paymentFormId, false);
        if (!this.submitPaymentButton || !this.paymentForm) {
            return;
        }

        this.submitButtonLoader = new ButtonLoadingIndicator(this.submitPaymentButton);
        this.withoutHttpClient = new HttpClient(false);
        this.client = new HttpClient();

        this.registerEvents();
    }

    registerEvents() {
        // Submit payment form handler
        this.submitPaymentButton.addEventListener('click', (event) => {
            event.preventDefault();

            // checks form validity before submit
            if (!this.paymentForm.checkValidity()) {
                return;
            }

            this.createLoading();
            this.onConfirmFormSubmit(event);
        });
    }

    /**
     * This method is called when the user clicks on the submit button on the payment form.
     */
    onConfirmFormSubmit() {
        throw new Error(`The "onConfirmFormSubmit" method for the plugin "${this._pluginName}" is not defined.`);
    }

    /**
     * Prepare the handler for submitting the payment data to the backend.
     * It will submit the payment data to the backend using AJAX instead of Regular form submission
     */
    submitAjaxPaymentForm(token) {
        // Get the action url from the form
        const formAction = this.paymentForm.getAttribute('action');

        // Get the form data
        const formData = FormSerializeUtil.serializeJson(this.paymentForm);

        const data = JSON.stringify({
            ...formData,
            [DATA_BAG_KEY]: {
                json: true, // We need to add it to be able to get a JSON response
                token,
            },
        });

        return new Promise((resolve) => {
            this.withoutHttpClient.post(formAction, data, (result) => {
                if (!result) {
                    resolve(null);
                    return;
                }

                resolve(JSON.parse(result));
            });
        });
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
