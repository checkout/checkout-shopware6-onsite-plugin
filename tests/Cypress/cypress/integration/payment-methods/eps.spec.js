import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import shopware from '../../support/services/shopware/Shopware';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'CheckoutCom\\Shopware6\\Handler\\Method\\EpsHandler';

// We can not test the whole EPS payment process
// because for EPS we need to visit an external site - the EPS site.
// EPS has some code that breaks their page out of the iframe and Cypress vanishes altogether and the tests stop.
// https://github.com/cypress-io/cypress/issues/1496
describe('Testing Storefront EPS payment', () => {
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

    describe('Testing EPS on checkout page', () => {
        it('hide EPS option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('EPS').should('not.exist');
        });

        it('show EPS option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('EPS').should('exist');
        });
    });

    describe('Testing EPS on account page', () => {
        it('show EPS option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('EPS').should('exist');
        });

        it('hide EPS option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('EPS').should('not.exist');
        });
    });
});
