class CheckoutConfirmRepository {

    /**
     * get term and condition checkbox
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getTermAndConditionCheckbox() {
        return cy.get('.confirm-tos .custom-checkbox label');
    }

    /**
     * get confirm submit button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getConfirmSubmitButton() {
        return cy.get('#confirmFormSubmit').scrollIntoView();
    }
}

export default new CheckoutConfirmRepository();
