import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';

import { applePaySessionMockFactory } from '../../support/services/applepay/ApplePay.Mock';

// Register Apple Pay and proceed to checkout
const setupApplePayCheckout = (applePay = true) => {
    applePaySessionMockFactory.registerApplePay(applePay);

    storefrontLoginAction.login('test@example.com', 'shopware');

    checkoutAction.addFirstProductToCart(1);
    checkoutAction.checkoutFromOffcanvas();

    cy.get('.confirm-tos .custom-checkbox label').click(1, 1);
};

describe('Testing Storefront Apple Pay visibility', () => {
    before(() => {
        // Set the Shopware instance to initial state only on local environment
        const promiseChain = Cypress.env('localUsage') ? cy.setToInitialState() : cy;

        promiseChain.then(() => {
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
            setupApplePayCheckout();

            cy.get('.payment-methods').contains('Apple Pay').should('exist');
        });

        it('hide Apple Pay option if browser does not support', () => {
            setupApplePayCheckout(false);

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
