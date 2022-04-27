class AccountOrderRepository {

    /**
     * Get first order status
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFirstOrderStatus() {
        return this._getFirstOrder().find('.order-table-body-value').eq(2);
    }

    /**
     * Get first order complete payment button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFirstOrderCompletePaymentButton() {
        return this._getFirstOrder().find('.order-table-header-order-status');
    }

    /**
     * Get first order
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     * @private
     */
    _getFirstOrder() {
        return cy.get('.order-table:first');
    }
}

export default new AccountOrderRepository();
