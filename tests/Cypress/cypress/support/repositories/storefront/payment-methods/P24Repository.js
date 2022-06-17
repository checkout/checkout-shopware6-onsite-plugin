class P24Repository {

    /**
     * Get abort button
     * @returns {Cypress.Chainable<Subject>}
     */
    getAbortButton() {
        return cy.get('#col-transaction-payment').find('.btn.btn-secondary')
                .should('be.visible')
                .should('have.text', 'Abort');
    }

    /**
     * Get submit button
     * @returns {Cypress.Chainable<Subject>}
     */
    getSubmitButton() {
        return cy.get('#col-transaction-payment').find('.btn.btn-primary')
                .should('be.visible');
    }

    /**
     * Get back button
     * @returns {Cypress.Chainable<Subject>}
     */
    getBackButton() {
        return cy.get('#sim-container').find('.btn.btn-primary')
                .should('be.visible');
    }
}

export default new P24Repository();
