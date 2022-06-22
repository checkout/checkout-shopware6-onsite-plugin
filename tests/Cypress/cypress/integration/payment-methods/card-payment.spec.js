import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';

describe('Testing Storefront Card Payments Payment', () => {
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
        dummyCheckoutScenario.execute();

        checkoutAction.selectPaymentMethod('Card Payments');
    });

    it('Empty card', () => {
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty card number', () => {
        checkoutAction.fillCardPayment(null, null, '0224', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Invalid card number', () => {
        checkoutAction.fillCardPayment(null, '2222222222222222', '0224', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty expiry date', () => {
        checkoutAction.fillCardPayment(null, '4242424242424242', null, '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Invalid expiry date', () => {
        checkoutAction.fillCardPayment(null, '4242424242424242', '1111', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    it('Empty CVV', () => {
        checkoutAction.fillCardPayment(null, '4242424242424242', '0224', null);
        checkoutConfirmRepository.getConfirmSubmitButton().should('be.disabled');
    });

    describe('Make payment with 3DS', () => {
        before(() => {
            shopConfigurationAction.toggle3ds(true);
        });

        it('Invalid CVV', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '111');

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
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

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

    describe('Make payment without 3DS', () => {
        before(() => {
            shopConfigurationAction.toggle3ds(false);
        });

        it('Invalid CVV', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '111');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.intercept('POST', 'https://api.sandbox.checkout.com/tokens', (req) => {
                checkoutAction.goToOrderScreen();

                accountOrderRepository.getFirstOrderStatus().should('have.text', 'Failed');
            });
        });

        it('Successful payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.intercept('POST', 'https://api.sandbox.checkout.com/tokens', (req) => {
                checkoutAction.goToOrderScreen();

                accountOrderRepository.getFirstOrderStatus().should('have.text', 'Paid');
            });
        });
    });
});
