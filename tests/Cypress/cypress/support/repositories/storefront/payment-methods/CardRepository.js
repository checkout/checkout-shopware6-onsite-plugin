class CardRepository {
    /**
     * Get saved card option
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getCardOptionInput() {
        return cy.get('input[name="checkoutComSourceId"]');
    }

    /**
     * Get "Save card details for future payments" checkbox
     * @returns {Cypress.Chainable<Subject>}
     */
    getSaveCardDetailsCheckbox() {
        return cy.get('#checkoutComShouldSaveSource').should('exist');
    }
}

export default new CardRepository();
