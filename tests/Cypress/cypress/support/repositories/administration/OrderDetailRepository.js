class OrderDetailRepository {
    /**
     * get state select
     * @param state
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getStateSelect(state) {
        return cy.get(`.sw-order-state-select__${state}-state select[name=sw-field--selectedActionName]`);
    }

    /**
     * get state change modal
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getStateChangeModal() {
        return cy.get('.sw-order-state-change-modal');
    }

    /**
     * get "Upload document" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getUploadDocumentButton() {
        return cy.get('.sw-order-state-change-modal-attach-documents__button');
    }
}

export default new OrderDetailRepository();
