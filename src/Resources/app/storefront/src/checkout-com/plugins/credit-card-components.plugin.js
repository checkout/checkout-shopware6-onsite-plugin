import Plugin from "src/plugin-system/plugin.class";
import HttpClient from "src/service/http-client.service";
import DomAccess from "src/helper/dom-access.helper";

export default class CheckoutComCreditCardComponents extends Plugin {
    static options = {
        localization: {
            cardNumberPlaceholder: "",
            expiryMonthPlaceholder: "",
            expiryYearPlaceholder: "",
            cvvPlaceholder: "",
        },
        publicKey: null,
        storeCardUrl: null,
        csrfToken: null,
        cardholderNameId: "#cardholder-name",
        paymentFormId: "#confirmOrderForm",
        submitPaymentButtonId: '#confirmOrderForm button[type="submit"]',
    };

    init() {
        this.client = new HttpClient();
        const { submitPaymentButtonId } = this.options;

        this.submitPaymentEle = this.getElement(
            document,
            submitPaymentButtonId
        );

        // We disable the form before the Frame is loaded
        this.disableForm();
        this.registerEvents();
    }

    getElement(rootSelector, selector) {
        return DomAccess.querySelector(rootSelector, selector, false);
    }

    registerEvents() {
        const { localization, publicKey, cardholderNameId } = this.options;

        // Submit payment form handler
        this.submitPaymentEle.addEventListener("click", (event) => {
            event.preventDefault();

            const cardholderNameInput = this.getElement(this.el, cardholderNameId);

            // We add the cardholder name to the form data (iframe checkout.com)
            if (cardholderNameInput) {
                Frames.cardholder = {
                    name: cardholderNameInput.value,
                };
            }
            // We submit the card, to get the token instead of submitting the payment form
            Frames.submitCard();
            this.disableForm();
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
        this.el.classList.remove("d-none");
    }

    /**
     * Card validation user input change
     * @param element {string}
     * @param isValid {boolean}
     * @param isEmpty {boolean}
     */
    onFrameValidationChanged({ element, isValid, isEmpty }) {
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
        this.enableFormWithCard();
    }

    /**
     * Receive token from checkout.com
     * @param token {string}
     */
    onCardTokenized({ token }) {
        const { csrfToken, storeCardUrl, paymentFormId } = this.options;

        const data = JSON.stringify({
            _csrf_token: csrfToken,
            cardToken: token,
        });

        // We call the api to save the token
        this.client.post(storeCardUrl, data, (response) => {
            const { result } = JSON.parse(response);

            // If it fails, then we enable the form so the user can enter the details again.
            if (!result) {
                this.enableFormWithCard();
                return;
            }

            // We continue to submit shopware payment form
            const paymentForm = this.getElement(document, paymentFormId);
            paymentForm.submit();
        });
    }

    clearErrorMessage(element) {
        const field = this.getElement(
            this.el,
            `.checkout-com-field__${element}`
        );
        field.classList.remove("is-invalid");
    }

    setErrorMessage(element) {
        const field = this.getElement(
            this.el,
            `.checkout-com-field__${element}`
        );
        field.classList.add("is-invalid");
    }

    disableForm() {
        this.submitPaymentEle.disabled = true;
    }

    enableForm() {
        this.submitPaymentEle.disabled = false;
    }

    enableFormWithCard() {
        this.enableForm();
        Frames.enableSubmitForm();
    }
}
