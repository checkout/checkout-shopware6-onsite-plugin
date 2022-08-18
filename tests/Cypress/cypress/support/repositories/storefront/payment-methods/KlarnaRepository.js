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
     * Fill "OTP" input
     * @returns {*}
     */
    fillOtpInput() {
        cy.get('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').then($element => {
            const $body = $element.contents().find('body');
            cy.wrap($body).find('#otp_field').typeMask('123456', { force: true });
        });
    }

    /**
     * Get "Purchase" button
     * @returns {*}
     */
    getPurchaseButton() {
        return cy.getIframeBody('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').find('#invoice_kp-purchase-review-continue-button');
    }

    /**
     * Fill "Date of birth" input
     * @returns {*}
     */
    fillDateOfBirthInput() {
        cy.get('iframe[id="klarna-checkoutcomklarnainstance-fullscreen"]').then($element => {
            const $body = $element.contents().find('body');
            cy.wrap($body).find('#baseaccount_kp-purchase-approval-form-date-of-birth').typeMask('01011990', { force: true });
        });
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
