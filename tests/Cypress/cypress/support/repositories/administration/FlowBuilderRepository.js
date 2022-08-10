class FlowBuilderRepository {
    /**
     * get flow list
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFlowList() {
        return cy.get('.sw-flow-list');
    }

    /**
     * get "Add flow" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getCreateFlowButton() {
        return cy.get('.sw-flow-list__create');
    }

    /**
     * get flow name input field
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFlowNameField() {
        return cy.get('#sw-field--flow-name');
    }

    /**
     * get flow priority input field
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFlowPriorityField() {
        return cy.get('#sw-field--flow-priority');
    }

    /**
     * get active switch
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getActiveSwitch() {
        return cy.get('.sw-flow-detail-general__general-active .sw-field--switch__input');
    }

    /**
     * get flow tab
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFlowTab() {
        return cy.get('.sw-flow-detail__tab-flow');
    }

    /**
     * get trigger input field
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getTriggerInput() {
        return cy.get('.sw-flow-trigger__input-field');
    }

    /**
     * get trigger result list
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getTriggerResult() {
        return cy.get('.sw-flow-trigger__search-result');
    }

    /**
     * get flow sequence
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getFlowSequence() {
        return cy.get('.sw-flow-sequence-selector');
    }

    /**
     * get "Add action" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getAddActionButton() {
        return cy.get('.sw-flow-sequence-selector__add-action');
    }

    /**
     * get action input field
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getActionInput() {
        return cy.get('.sw-flow-sequence-action__selection-action');
    }

    /**
     * get "Save" button
     * @returns {Cypress.Chainable<JQuery<HTMLElement>>}
     */
    getSaveFlowButton() {
        return cy.get('.sw-flow-detail__save');
    }
}

export default new FlowBuilderRepository();
