const { v4: uuid } = require('uuid');

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

/**
 * Login and open administration page
 * @memberOf Cypress.Chainable#
 * @name loginAndOpenAdmin
 * @function
 * @param {String} url - url of the administration page
 */
Cypress.Commands.add('loginAndOpenAdmin', (url) => {
    return cy.loginViaApi()
        .then(() => {
            cy.openInitialPage(url);
            cy.get('.sw-skeleton').should('not.exist');
            cy.get('.sw-loader').should('not.exist');
        });
});

/**
 * Type in a masked input
 * @memberOf Cypress.Chainable#
 * @name typeMask
 * @function
 */
Cypress.Commands.add('typeMask', { prevSubject: true }, (subject, nextValue) => {
        const $input = subject.get(0);

        const lastValue = $input.value;
        $input.value = nextValue;

        $input._valueTracker.setValue(lastValue);
        $input.dispatchEvent(new Event('change', { bubbles: true }));

        return subject;
    }
);

/**
 * Create customer fixture using Shopware API at the given endpoint, tailored for Storefront
 * @memberOf Cypress.Chainable#
 * @name createCustomerFixtureByCountry
 * @function
 * @param {Object} userData - Options concerning creation
 * @param {Object} isoCode - Country ISO code to set in customer address
 */
Cypress.Commands.add('createCustomerFixtureByCountry', (userData, isoCode) => {
    const addressId = uuid().replace(/-/g, '');
    const customerId = uuid().replace(/-/g, '');
    let customerJson = {};
    let customerAddressJson = {};
    let finalAddressRawData = {};
    let countryId = '';
    let groupId = '';
    let paymentId = '';
    let salesChannelId = '';
    let salutationId = '';

    return cy.fixture('customer').then((result) => {
        customerJson = Cypress._.merge(result, userData);

        return cy.fixture('customer-address')
    }).then((result) => {
        customerAddressJson = result;

        return cy.searchViaAdminApi({
            endpoint: 'country',
            data: {
                field: 'iso',
                value: isoCode || 'DE'
            }
        });
    }).then((result) => {
        countryId = result.id;

        return cy.searchViaAdminApi({
            endpoint: 'payment-method',
            data: {
                field: 'name',
                value: 'Invoice'
            }
        });
    }).then((result) => {
        paymentId = result.id;

        return cy.searchViaAdminApi({
            endpoint: 'sales-channel',
            data: {
                field: 'name',
                value: 'Storefront'
            }
        });
    }).then((result) => {
        salesChannelId = result.id;

        return cy.searchViaAdminApi({
            endpoint: 'customer-group',
            data: {
                field: 'name',
                value: 'Standard customer group'
            }
        });
    }).then((result) => {
        groupId = result.id;

        return cy.searchViaAdminApi({
            endpoint: 'salutation',
            data: {
                field: 'displayName',
                value: 'Mr.'
            }
        });
    }).then((salutation) => {
        salutationId = salutation.id;

        let first = true;
        finalAddressRawData = {
            addresses: customerAddressJson.addresses.map((a) => {
                let addrId;;
                if (first) {
                    addrId = addressId;
                    first = false;
                } else {
                    addrId = uuid().replace(/-/g, '');
                }
                cy.log(a.firstName)
                return Cypress._.merge({
                    customerId: customerId,
                    salutationId: salutationId,
                    id: addrId,
                    countryId: countryId
                }, a)
            })
        };
    }).then(() => {
        return Cypress._.merge(customerJson, {
            salutationId: salutationId,
            defaultPaymentMethodId: paymentId,
            salesChannelId: salesChannelId,
            groupId: groupId,
            defaultBillingAddressId: addressId,
            defaultShippingAddressId: addressId
        });
    }).then((result) => {
        return Cypress._.merge(result, finalAddressRawData);
    }).then((result) => {
        return cy.requestAdminApiStorefront({
            endpoint: 'customer',
            data: result
        });
    });
});
