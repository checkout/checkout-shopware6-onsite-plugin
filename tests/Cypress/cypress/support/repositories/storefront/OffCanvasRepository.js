class OffCanvasRepository {

    /**
     * get checkout button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getCheckoutButton() {
        return cy.get('.begin-checkout-btn');
    }

    /**
     * get quantity select
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getQuantitySelect() {
        return cy.get('select[name=quantity]');
    }
}

export default new OffCanvasRepository();
