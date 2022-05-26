import P24Repository from '../../repositories/storefront/payment-methods/P24Repository';

class P24Action {

    /**
     * Proceed payment with P24
     */
    makePayment() {
        P24Repository.getSubmitButton().should('have.text', 'Next').click();

        P24Repository.getSubmitButton().should('have.text', 'Login').click();

        P24Repository.getSubmitButton().should('have.text', 'Make Payment').click();

        // Wait until the P24 payment page is fully loaded.
        cy.wait(1000);

        P24Repository.getBackButton().click();
    }
}

export default new P24Action();
