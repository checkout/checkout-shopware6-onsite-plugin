import deepmerge from 'deepmerge';
import DomAccess from 'src/helper/dom-access.helper';
import CheckoutComSourcePaymentHandler from '../core/checkout-com-source-payment-handler';
import { createShouldSaveSourceInput, createTokenInput } from '../helper/utils';

/**
 * This Class is responsible for the Card Payments integration
 */
export default class CheckoutComCardPayment extends CheckoutComSourcePaymentHandler {
    // The card logo MIME type is always svg
    LOGO_MIME_TYPE = 'svg';

    static options = deepmerge(CheckoutComSourcePaymentHandler.options, {
        localization: {
            cardNumberPlaceholder: '',
            expiryMonthPlaceholder: '',
            expiryYearPlaceholder: '',
            cvvPlaceholder: '',
        },
        cardIconsPathMatch: 'img/card-icons',
        cardIconsPath: null,
        publicKey: null,
        iconPaymentMethodId: '#checkoutComIconPaymentMethod',
        cardholderNameId: '#cardholder-name',
        prefixFieldClass: '.checkout-com-field__',
    });

    init() {
        super.init();
        this.initFrame();
    }

    getElement(rootSelector, selector) {
        return DomAccess.querySelector(rootSelector, selector, false);
    }

    initFrame() {
        const {
            localization,
            publicKey,
        } = this.options;

        Frames.init({
            publicKey,
            localization,
            ready: this.onReadyFrames.bind(this),
            frameValidationChanged: this.onFrameValidationChanged.bind(this),
            cardValidationChanged: this.onCardValidationChanged.bind(this),
            paymentMethodChanged: this.onPaymentMethodChanged.bind(this),
            cardTokenizationFailed: this.onCardTokenizationFailed.bind(this),
            cardTokenized: this.onCardTokenized.bind(this),
        });
    }

    onSourceChange(sourceValue) {
        if (sourceValue !== null) {
            this.enableForm();
            return;
        }

        this.onCardValidationChanged({
            isValid: Frames.isCardValid(),
        });
    }

    onConfirmFormSubmit() {
        const {
            cardholderNameId,
        } = this.options;
        const cardholderNameInput = this.getElement(this.el, cardholderNameId);

        // We add the cardholder name to the form data (iframe checkout.com)
        if (cardholderNameInput) {
            Frames.cardholder = {
                name: cardholderNameInput.value,
            };
        }

        this.createLoading();

        // Submit the card Frame, to get the token instead of submitting the payment form
        // All the card data is submitted to the checkout.com server by the iframe
        Frames.submitCard();
    }

    onReadyFrames() {
        // Because we add `d-none` class to the card form, we need to remove it when the frame is ready
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
        const sourceValue = this.getSourceInputValue();
        if (sourceValue !== null) {
            return;
        }

        if (isValid) {
            this.enableForm();
        } else {
            this.disableForm();
        }
    }

    onPaymentMethodChanged({ paymentMethod }) {
        const { iconPaymentMethodId } = this.options;
        const iconLogo = this.getElement(this.el, iconPaymentMethodId);

        if (paymentMethod) {
            const cardIconsPath = this.getCardIconsPathWithoutVersion();
            const src = `${cardIconsPath}/${paymentMethod.toLowerCase()}.${this.LOGO_MIME_TYPE}?${new Date().getTime()}`;

            iconLogo.setAttribute('alt', paymentMethod);
            iconLogo.setAttribute('src', src);
        } else {
            iconLogo.removeAttribute('alt');
            iconLogo.removeAttribute('src');
        }
    }

    getCardIconsPathWithoutVersion() {
        const {
            cardIconsPath,
            cardIconsPathMatch,
        } = this.options;

        // Remove string after the `cardIconsPathMatch` variable
        // Example: ...../img/card-icons?v=123456789 => ...../img/card-icons
        return cardIconsPath.split(cardIconsPathMatch)[0] + cardIconsPathMatch;
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
        // It will be sent to the server along with the form.
        this.paymentForm.append(createTokenInput(token));
        this.paymentForm.append(createShouldSaveSourceInput(this.shouldSaveSource()));

        // Continue to submit shopware payment form
        this.paymentForm.submit();
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
}
