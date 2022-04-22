class ListingRepository {

    /**
     * get first product
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFirstProduct() {
        return cy.get(':nth-child(1) > .card > .card-body .product-action');
    }
}

export default new ListingRepository();
