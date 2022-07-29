class OrderListRepository {
    getFirstRowOrderNumber() {
        return cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a');
    }
}

export default new OrderListRepository();
