import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import shopware from '../../support/services/shopware/Shopware';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'Cko\\Shopware6\\Handler\\Method\\SofortHandler';

// We can not test the whole Sofort payment process
// because for Sofort we need to visit an external site - the Sofort site.
// Sofort has some code that breaks their page out of the iframe and Cypress vanishes altogether and the tests stop.
// https://github.com/cypress-io/cypress/issues/1496
describe('Testing Storefront Sofort visibility', () => {
    before(() => {
        cy.setToInitialState().then(() => {
            return cy.loginViaApi();
        }).then(() => {
            return cy.createProductFixture();
        }).then(() => {
            shopConfigurationAction.setupShop();
            cy.createCustomerFixtureStorefront();
        });
    });

    describe('Testing Sofort on checkout page', () => {
        it('show Sofort option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Sofort').should('exist');
        });

        it('hide Sofort option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Sofort').should('not.exist');
        });
    });

    describe('Testing Sofort on account page', () => {
        it('show Sofort option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Sofort').should('exist');
        });

        it('hide Sofort option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Sofort').should('not.exist');
        });
    });
});
