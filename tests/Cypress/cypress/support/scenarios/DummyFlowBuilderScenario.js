import flowBuilderRepository from '../repositories/administration/FlowBuilderRepository';

class DummyFlowBuilderScenario {
    createRefundFlow() {
        cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/flow/index`);

        cy.get('.sw-skeleton').should('not.exist');
        cy.get('.sw-loader').should('not.exist');

        cy.intercept({
            url: `${Cypress.env('apiPath')}/flow`,
            method: 'POST'
        }).as('saveData');

        flowBuilderRepository.getFlowList().should('be.visible');
        flowBuilderRepository.getCreateFlowButton().click();

        // Verify "create" page
        cy.contains('.smart-bar__header h2', 'New flow');

        // Fill in all required fields to create a flow builder
        flowBuilderRepository.getFlowNameField().type('Full refund when returning orders');
        flowBuilderRepository.getFlowPriorityField().type('10');
        flowBuilderRepository.getActiveSwitch().click();

        flowBuilderRepository.getFlowTab().click();
        flowBuilderRepository.getTriggerInput().type('State Enter / Order Delivery / State / Returned');
        flowBuilderRepository.getTriggerResult().should('be.visible').eq(0).click();

        flowBuilderRepository.getFlowSequence().should('be.visible');
        flowBuilderRepository.getAddActionButton().click();

        flowBuilderRepository.getActionInput()
            .typeSingleSelect('Full refund', '.sw-flow-sequence-action__selection-action');

        // Check confirm modal and add action
        cy.get('.sw-modal').should('be.visible');
        cy.get('.sw-modal .sw-button--primary').click();
        cy.get('.sw-modal ').should('not.exist');

        // Save flow
        flowBuilderRepository.getSaveFlowButton().click();
        cy.wait('@saveData').its('response.statusCode').should('equal', 204);
    }
}

export default new DummyFlowBuilderScenario();
