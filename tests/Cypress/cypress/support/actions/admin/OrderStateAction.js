import adminOrderDetailRepository from '../../repositories/administration/OrderDetailRepository';

class OrderStateAction {
    /**
     * Change state of order
     * @param state
     * @param value
     */
    changeState(state, value) {
        adminOrderDetailRepository.getStateSelect(state).select(value);
        adminOrderDetailRepository.getStateChangeModal().should('be.visible');
        adminOrderDetailRepository.getUploadDocumentButton().click();

        // Wait until the order status changed and finish reloading order detail page
        cy.intercept({
            url: `${Cypress.env('apiPath')}/search/order`,
            method: 'POST'
        }).as('payment');

        cy.wait('@payment');
    }
}

export default new OrderStateAction();
