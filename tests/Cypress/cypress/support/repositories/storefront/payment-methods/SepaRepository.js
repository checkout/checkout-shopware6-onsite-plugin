class SepaRepository {

    /**
     * Get first name input
     * @returns {Cypress.Chainable<Subject>}
     */
    getFirstName() {
        return cy.get('#sepaFirstName').should('exist');
    }

    /**
     * Get last name input
     * @returns {Cypress.Chainable<Subject>}
     */
    getLastName() {
        return cy.get('#sepaLastName').should('exist');
    }

    /**
     * Get Iban input
     * @returns {Cypress.Chainable<Subject>}
     */
    getIban() {
        return cy.get('#sepaIban').should('exist');
    }
}

export default new SepaRepository();
