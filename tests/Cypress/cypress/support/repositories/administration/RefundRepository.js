class RefundRepository {
    /**
     * Get 'Checkout.com Refund' button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getRefundButton() {
        return cy.get('.sw-order-line-items-grid__actions-btn');
    }

    /**
     * Get 'Refund manager' modal
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getRefundModal() {
        return cy.get('.checkout-com-refund-modal');
    }

    /**
     * Get 'Yes, I want to refund' checkbox
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getConfirmRefundCheckbox() {
        return cy.get('.checkout-com-refund-modal-footer .sw-field--checkbox input');
    }

    /**
     * Get 'Refund selected items' button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getModalRefundButton() {
        return cy.get('.checkout-com-refund-modal__button-refund');
    }

    /**
     * Get Select all checkbox
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getSelectAllCheckbox() {
        return cy.get('.checkout-com-refund-section .sw-data-grid__select-all .sw-field__checkbox');
    }

    getFirstRowReturnQuantityInput() {
        return cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--returnQuantity .sw-block-field__block');
    }
}

export default new RefundRepository();
