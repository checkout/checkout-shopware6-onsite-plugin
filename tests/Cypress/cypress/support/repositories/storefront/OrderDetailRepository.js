class OrderDetailRepository {
    /**
     * "Capture" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getCaptureButton() {
        return cy.get('.checkout-com-order-button-capture');
    }

    /**
     * "Void" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getVoidButton() {
        return cy.get('.checkout-com-order-button-void');
    }

    /**
     * current payment status
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getCurrentPaymentStatus() {
        return cy.get('.sw-field--select__placeholder-option');
    }

    /**
     * delivery status select
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getDeliveryStatusSelect() {
        return cy.get('.sw-order-state-select__delivery-state select');
    }

    /**
     * order state modal
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getOrderStateModal() {
        return cy.get('.sw-order-state-change-modal');
    }

    /**
     * "Update status" button inside order state modal
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getUpdateStatusButton() {
        return cy.get('.sw-order-state-change-modal-attach-documents__button');
    }
}

export default new OrderDetailRepository();
