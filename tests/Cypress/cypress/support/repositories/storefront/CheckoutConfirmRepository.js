class CheckoutConfirmRepository {

    /**
     * get confirm submit button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getConfirmSubmitButton() {
        return cy.get('#confirmFormSubmit');
    }
}

export default new CheckoutConfirmRepository();
