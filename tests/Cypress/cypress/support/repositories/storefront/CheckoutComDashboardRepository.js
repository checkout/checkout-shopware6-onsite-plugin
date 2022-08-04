class CheckoutComDashboardRepository {

    /**
     * get the Checkout dashboard card
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getDashboardCard() {
        return cy.get('.checkout-com-order-detail-payment');
    }

    /**
     * get payment status
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getPaymentStatus() {
        return cy.get('.checkout-com-order-detail-payment-history-text');
    }

    /**
     * get payment action
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getPaymentAction() {
        return cy.get('.checkout-com-order-detail-payment-action');
    }

    /**
     * get payment icon
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getPaymentIcon() {
        return this.getPaymentAction().find('.checkout-com-order-detail-payment-history-failed-icon-bg');
    }
}

export default new CheckoutComDashboardRepository();
