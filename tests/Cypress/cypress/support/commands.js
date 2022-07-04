// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('getIframeBody', (selector) => {
    // get the iframe > document > body
    // and retry until the body element is not empty
    return cy
        .get(selector)
        .its('0.contentDocument.body').should('not.be.empty')
        // wraps "body" DOM element to allow
        // chaining more Cypress commands, like ".find(...)"
        // https://on.cypress.io/wrap
        .then(cy.wrap);
});

/**
 * Updates an existing entity using Shopware API at the given endpoint and handler identifier.
 * @memberOf Cypress.Chainable#
 * @name updateViaAdminApiWithIdentifier
 * @function
 * @param {String} endpoint - API endpoint for the request
 * @param {String} identifier - Id of the entity to be updated
 * @param {Object} data - Necessary data for the API request
 */
Cypress.Commands.add('updateViaAdminApiWithIdentifier', (endpoint, identifier, data) => {
    return cy.searchViaAdminApi({
        endpoint: endpoint,
        data: {
            field: 'handlerIdentifier',
            value: identifier,
        },
    })
        .then((entity) => {
            return cy.updateViaAdminApi(endpoint, entity.id, { data });
        });
});

/**
 * Get sales channel info by name
 * @memberOf Cypress.Chainable#
 * @name getSalesChannelByName
 * @function
 * @param {String} salesChannelName - sales channel name
 */
Cypress.Commands.add('getSalesChannelByName', (salesChannelName = 'Storefront') => {
    return cy.searchViaAdminApi({
        endpoint: 'sales-channel',
        data: {
            field: 'name',
            value: salesChannelName,
        },
    });
});
