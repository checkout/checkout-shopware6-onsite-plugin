class SepaRepository {
    getFirstName() {
        return cy.get('#sepaFirstName').should('exist');
    }

    getLastName() {
        return cy.get('#sepaLastName').should('exist');
    }

    getIban() {
        return cy.get('#sepaIban').should('exist');
    }
}

export default new SepaRepository();
