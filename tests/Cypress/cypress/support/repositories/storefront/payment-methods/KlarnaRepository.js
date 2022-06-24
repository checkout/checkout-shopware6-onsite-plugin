class KlarnaRepository {
    /**
     * Get "Pay in 30 days" option
     * @returns {*}
     */
    getPayLaterOption() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-main"]').find('#radio-pay_later__label');
    }

    /**
     * Get "Flexible account" option
     * @returns {*}
     */
    getPayOverTimeOption() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-main"]').find('#radio-pay_over_time__label');
    }

    /**
     * Get "Email or phone" input
     * @returns {*}
     */
    getEmailOrPhoneInput() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#email_or_phone');
    }

    /**
     * Get "Continue" button
     * @returns {*}
     */
    getContinueButton() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#onContinue');
    }

    /**
     * Get "OTP" input
     * @returns {*}
     */
    getOtpInput() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#otp_field');
    }

    /**
     * Get "Purchase" button
     * @returns {*}
     */
    getPurchaseButton() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#invoice_kp-purchase-review-continue-button');
    }

    /**
     * Get "Date of birth" input
     * @returns {*}
     */
    getDateOfBirthInput() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('input[name="dateOfBirth"]');
    }

    /**
     * Get "Approve" button
     * @returns {*}
     */
    getApproveButton() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#baseaccount_kp-purchase-approval-form-continue-button');
    }
}

export default new KlarnaRepository();
