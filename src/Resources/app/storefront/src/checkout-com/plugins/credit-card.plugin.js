import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import { DATA_BAG_KEY } from '../helper/constants';

/**
 * This Class is responsible for the Credit Card integration
 */
export default class CheckoutComCreditCard extends Plugin {
    static options = {
        localization: {
            cardNumberPlaceholder: '',
            expiryMonthPlaceholder: '',
            expiryYearPlaceholder: '',
            cvvPlaceholder: '',
        },
        publicKey: null,
        cardholderNameId: '#cardholder-name',
        paymentFormId: '#confirmOrderForm',
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
        prefixFieldClass: '.checkout-com-field__',
    };

    init() {
        this.client = new HttpClient();
        const {
            submitPaymentButtonId,
            paymentFormId,
        } = this.options;

        this.submitPaymentButton = this.getElement(
            document,
            submitPaymentButtonId,
        );

        this.submitButtonLoader = new ButtonLoadingIndicator(
            this.submitPaymentButton,
        );

        this.paymentForm = this.getElement(
            document,
            paymentFormId,
        );

        // We disable the form before the Frame is loaded
        this.disableForm();
        this.registerEvents();
    }

    getElement(rootSelector, selector) {
        return DomAccess.querySelector(rootSelector, selector, false);
    }

    registerEvents() {
        const {
            localization,
            publicKey,
            cardholderNameId,
        } = this.options;

        // Submit payment form handler
        this.submitPaymentButton.addEventListener('click', (event) => {
            event.preventDefault();

            // checks form validity before submit
            if (!this.paymentForm.checkValidity()) {
                return;
            }

            const cardholderNameInput = this.getElement(
                this.el,
                cardholderNameId,
            );

            // We add the cardholder name to the form data (iframe checkout.com)
            if (cardholderNameInput) {
                Frames.cardholder = {
                    name: cardholderNameInput.value,
                };
            }

            this.createLoading();

            // Submit the credit card Frame, to get the token instead of submitting the payment form
            // All the credit card data is submitted to the checkout.com server by the iframe
            Frames.submitCard();
        });

        Frames.init({
            publicKey,
            localization,
            ready: this.onReadyFrames.bind(this),
            frameValidationChanged: this.onFrameValidationChanged.bind(this),
            cardValidationChanged: this.onCardValidationChanged.bind(this),
            cardTokenizationFailed: this.onCardTokenizationFailed.bind(this),
            cardTokenized: this.onCardTokenized.bind(this),
        });
    }

    onReadyFrames() {
        // Because we add `d-none` class to the credit card form, we need to remove it when the frame is ready
        this.el.classList.remove('d-none');
    }

    /**
     * Card validation upon user input change
     * @param event {Object}
     */
    onFrameValidationChanged(event) {
        const {
            element,
            isValid,
            isEmpty,
        } = event;
        if (isValid || isEmpty) {
            this.clearErrorMessage(element);
        } else {
            this.setErrorMessage(element);
        }
    }

    /**
     * Card validation form changed (Invalid form or not)
     * @param isValid {boolean}
     */
    onCardValidationChanged({ isValid }) {
        if (isValid) {
            this.enableForm();
        } else {
            this.disableForm();
        }
    }

    /**
     * Card tokenization failed
     */
    onCardTokenizationFailed() {
        this.removeLoading();
        Frames.enableSubmitForm();
    }

    /**
     * When the card validation is successful, we need to get the card token
     * And send it to the Shopware server
     * @param token {string}
     */
    onCardTokenized({ token }) {
        const { paymentFormId } = this.options;

        const paymentForm = this.getElement(document, paymentFormId);

        const input = this.createTokenInput(token);

        // Add the token input to the form
        // It will be sent to the server along with the form.
        paymentForm.append(input);

        // Continue to submit shopware payment form
        paymentForm.submit();
    }

    createTokenInput(token) {
        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', `${DATA_BAG_KEY}[token]`);
        input.setAttribute('value', token);

        return input;
    }

    clearErrorMessage(element) {
        const { prefixFieldClass } = this.options;
        const field = this.getElement(this.el, `${prefixFieldClass}${element}`);
        field.classList.remove('is-invalid');
    }

    setErrorMessage(element) {
        const { prefixFieldClass } = this.options;
        const field = this.getElement(this.el, `${prefixFieldClass}${element}`);
        field.classList.add('is-invalid');
    }

    disableForm() {
        this.submitPaymentButton.disabled = true;
    }

    enableForm() {
        this.submitPaymentButton.disabled = false;
    }

    createLoading() {
        this.submitButtonLoader.create();
    }

    removeLoading() {
        this.submitButtonLoader.remove();
    }
}
