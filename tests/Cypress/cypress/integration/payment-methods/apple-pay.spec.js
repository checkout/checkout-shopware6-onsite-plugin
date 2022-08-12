import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

import { applePaySessionMockFactory } from '../../support/services/applepay/ApplePay.Mock';

describe('Testing Storefront Apple Pay visibility', () => {
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

    describe('Testing Apple Pay on checkout page', () => {
        it('show Apple Pay option if browser supports', () => {
            applePaySessionMockFactory.registerApplePay(true);

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Apple Pay').should('exist');
        });

        it('hide Apple Pay option if browser does not support', () => {
            applePaySessionMockFactory.registerApplePay(false);

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Apple Pay').should('not.exist');
        });
    });

    describe('Testing Apple Pay on account page', () => {
        it('show Apple Pay option if browser supports', () => {
            applePaySessionMockFactory.registerApplePay(true);
            storefrontLoginAction.login('test@example.com', 'shopware');

            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Apple Pay').should('exist');
        });

        it('hide Apple Pay option if browser does not support', () => {
            applePaySessionMockFactory.registerApplePay(false);
            storefrontLoginAction.login('test@example.com', 'shopware');

            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Apple Pay').should('not.exist');
        });
    });
})
