import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import shopware from '../../support/services/shopware/Shopware';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'Cko\\Shopware6\\Handler\\Method\\PayPalHandler';

// We can not test the whole PayPal payment process
// because for PayPal we need to visit an external site - the PayPal site.
// PayPal has some code that breaks their page out of the iframe and Cypress vanishes altogether and the tests stop.
// https://github.com/cypress-io/cypress/issues/1496
describe('Testing Storefront PayPal visibility', () => {
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

    describe('Testing PayPal on checkout page', () => {
        it('show PayPal option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('PayPal').should('exist');
        });

        it('hide PayPal option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('PayPal').should('not.exist');
        });
    });

    describe('Testing PayPal on account page', () => {
        it('show PayPal option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('PayPal').should('exist');
        });

        it('hide PayPal option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('PayPal').should('not.exist');
        });
    });
});
