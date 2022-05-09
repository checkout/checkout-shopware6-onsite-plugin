import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';

import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';

describe('Testing Storefront Credit Card Payment', () => {
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

    beforeEach(() => {
        storefrontLoginAction.login('test@example.com', 'shopware');

        checkoutAction.addFirstProductToCart(1);
        checkoutAction.checkoutFromOffcanvas();

        cy.get('.confirm-tos .custom-checkbox label').click(1, 1);

        checkoutAction.selectPaymentMethod('Credit card');
    });

    it('Empty credit card', () => {
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty card number', () => {
        checkoutAction.fillCreditCard(null, null, '0224', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Invalid card number', () => {
        checkoutAction.fillCreditCard(null, '2222222222222222', '0224', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty expiry date', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', null, '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Invalid expiry date', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', '1111', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty CVV', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', '0224', null);
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Invalid CVV', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', '0224', '111');

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', '3ds2');

        // Wait until iframe is fully loaded
        cy.wait(8000);

        cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#password').type('Checkout1!');
        cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#txtButton').click();

        checkoutAction.goToOrderScreen();

        accountOrderRepository.getFirstOrderStatus().should('have.text', 'Failed');
    });

    it('Successful payment', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', '0224', '100');

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', '3ds2');

        // Wait until iframe is fully loaded
        cy.wait(8000);

        cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#password').type('Checkout1!');
        cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#txtButton').click();

        checkoutAction.goToOrderScreen();

        accountOrderRepository.getFirstOrderStatus().should('have.text', 'Paid');
    });
});
