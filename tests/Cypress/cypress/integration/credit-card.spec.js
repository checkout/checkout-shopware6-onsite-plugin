import shopConfigurationAction from '../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../support/actions/storefront/CheckoutAction';
import storefrontLoginAction from '../support/actions/storefront/LoginAction';

import checkoutConfirmRepository from '../support/repositories/storefront/CheckoutConfirmRepository';

describe('Testing Storefront Credit Card Payment', () => {
    before(() => {
        cy.setToInitialState().then(() => {
            cy.loginViaApi();
        }).then(() => {
            cy.createProductFixture();
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

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled');
        checkoutConfirmRepository.getConfirmSubmitButton().click();

        cy.intercept('POST', '/checkout/order').as('orderSubmit');

        cy.wait('@orderSubmit');

        cy.url({ decode: true }).should('contain', 'CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED');
        cy.get('.alert-content').should('exist');
    });

    it('Successful payment', () => {
        checkoutAction.fillCreditCard(null, '4242424242424242', '0224', '100');

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled');
        checkoutConfirmRepository.getConfirmSubmitButton().click();

        cy.intercept('POST', '/checkout/order').as('orderSubmit');

        cy.wait('@orderSubmit');

        cy.location('href').should('include', '/checkout/finish');
    });
});
