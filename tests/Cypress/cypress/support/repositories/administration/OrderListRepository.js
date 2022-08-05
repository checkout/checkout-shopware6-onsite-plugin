class OrderListRepository {
    /**
     * get order number of the first row
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFirstRowOrderNumber() {
        return cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a');
    }
}

export default new OrderListRepository();
