class CheckoutConfirmRepository {

    /**
     * get confirm submit button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getConfirmSubmitButton() {
        return cy.get('#confirmFormSubmit').scrollIntoView();
    }
}

export default new CheckoutConfirmRepository();
