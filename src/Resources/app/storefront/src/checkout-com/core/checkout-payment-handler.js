import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from '../services/http-client.service';
import { DATA_BAG_KEY } from '../helper/constants';

/**
 * This plugin handles the payment process for the checkout order form.
 * It is responsible for submitting the payment data to the backend using AJAX instead of Regular form submission.
 * We will redirect the user after the form is submitted.
 * We use a payment session to validate that the payment is successful.
 */
export default class CheckoutPaymentHandler extends Plugin {
    static options = {
        paymentFormId: '#confirmOrderForm',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
    };

    init() {
        const {
            submitPaymentButtonId,
            paymentFormId,
        } = this.options;

        this.withoutHttpClient = new HttpClient(false);
        this.client = new HttpClient();

        this.submitPaymentButton = DomAccess.querySelector(
            document,
            submitPaymentButtonId,
            false,
        );

        this.submitButtonLoader = new ButtonLoadingIndicator(
            this.submitPaymentButton,
        );

        this.paymentForm = DomAccess.querySelector(
            document,
            paymentFormId,
            false,
        );

        this.registerEvents();
    }

    registerEvents() {
        // Submit payment form handler
        this.paymentForm.addEventListener('submit', (event) => {
            event.preventDefault();

            this.createLoading();
            this.onConfirmFormSubmit(event);
        });
    }

    /**
     * This method is called when the user clicks on the submit button on the payment form.
     */
    onConfirmFormSubmit(event) {
        throw new Error(
            `The "onConfirmFormSubmit" method for the plugin "${this._pluginName}" is not defined.`,
        );
    }

    /**
     * Prepare the handler for submitting the payment data to the backend.
     * It will call the backend to process the payment.
     * After the payment is processed, the successful/failure response will be handled by the "onPaymentResponse" method.
     */
    submitPaymentForm(token) {
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

        // Call the backend to process the payment
        // Need to call the backend without the `X-Requested-With` header
        // Because the URL is a regular form action
        this.withoutHttpClient.post(formAction, data, (result) => {
            if (!result) {
                return;
            }

            const {
                success,
                redirectUrl,
            } = JSON.parse(result);
            this.onPaymentResponse(success, redirectUrl);
        });
    }

    /**
     * This method is called when the payment is processed.
     * We need to redirect the user to the payment response page.
     */
    onPaymentResponse(success, redirectUrl) {
        throw new Error(
            `The "onPaymentResponse" method for the plugin "${this._pluginName}" is not defined.`,
        );
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
