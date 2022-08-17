import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';
import cardRepository from '../../support/repositories/storefront/payment-methods/CardRepository';
import orderListRepository from '../../support/repositories/administration/OrderListRepository';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';

describe('Testing Storefront Card Payments Payment', () => {
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

    beforeEach(() => {
        dummyCheckoutScenario.execute();

        checkoutAction.selectPaymentMethod('Card Payments');
    });

    describe(('Testing card validation'), () => {
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
    });

    describe('Make payment with 3DS', () => {
        before(() => {
            shopConfigurationAction.setSystemConfig('CheckoutCom.config.enable3dSecure', true);
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
            shopConfigurationAction.setSystemConfig('CheckoutCom.config.enable3dSecure', false);
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

    describe('Enable "Manual capture"', () => {
        before(() => {
            shopConfigurationAction.setSystemConfig('CheckoutCom.config.enable3dSecure', false);
        });

        beforeEach(() => {
            cy.intercept({
                url: 'https://api.sandbox.checkout.com/tokens',
                method: 'POST'
            }).as('makePayment');

            shopConfigurationAction.setSystemConfig('CheckoutCom.config.paymentMethod.card.manualCapture', true);

            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.wait('@makePayment');

            cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);
        });

        it('Capture payment', () => {
            cy.intercept({
                method: 'POST',
                url: 'api/_action/checkout-com/order/capture/**'
            }).as('capturePayment');

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getCurrentPaymentStatus().contains('Authorized');

            orderDetailRepository.getCaptureButton().should('exist').click();

            cy.wait('@capturePayment');

            // Check if payment status was changed to "Paid"
            orderDetailRepository.getCurrentPaymentStatus().contains('Paid');
            orderDetailRepository.getCaptureButton().should('not.exist');
        });

        it('Void payment', () => {
            cy.intercept({
                method: 'POST',
                url: 'api/_action/checkout-com/order/void/**'
            }).as('voidPayment');

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getCurrentPaymentStatus().contains('Authorized');

            orderDetailRepository.getVoidButton().should('exist').click();

            cy.wait('@voidPayment');

            // Check if payment status was changed to "Cancelled"
            orderDetailRepository.getCurrentPaymentStatus().contains('Cancelled');
            orderDetailRepository.getVoidButton().should('not.exist');
        });
    });

    describe('Testing "Save card details for future payments"', () => {
        before(() => {
            shopConfigurationAction.setSystemConfig('CheckoutCom.config.enable3dSecure', false);
        });

        it('Uncheck and make payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.intercept('GET', '/checkout/finish*').as('checkoutFinish');
            cy.wait('@checkoutFinish').its('response.statusCode').should('equal', 200);

            // Make another payment
            cy.visit('/');
            dummyCheckoutScenario.execute(false);
            checkoutAction.selectPaymentMethod('Card Payments');

            // Card options section should not appear when unchecking "Save card details for future payments"
            cardRepository.getCardOptionInput().should('have.length', 0);
        });

        it('Check and make failed payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '111');

            // Check "Save card details for future payments"
            cardRepository.getSaveCardDetailsCheckbox().check({ force: true });

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.url().should('include', '3ds2');

            // Wait until iframe is fully loaded
            cy.wait(8000);

            cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#password').type('Checkout1!');
            cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#txtButton').click();

            // Wait for 3DS verification process to finish
            cy.wait(3000);

            // Make another payment
            cy.visit('/');
            dummyCheckoutScenario.execute(false);
            checkoutAction.selectPaymentMethod('Card Payments');

            // Card options section should not appear when unchecking "Save card details for future payments"
            cardRepository.getCardOptionInput().should('have.length', 0);
        });

        it('Check and make successful payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            // Check "Save card details for future payments"
            cardRepository.getSaveCardDetailsCheckbox().check({ force: true });

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.url().should('include', '3ds2');

            // Wait until iframe is fully loaded
            cy.wait(8000);

            cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#password').type('Checkout1!');
            cy.getIframeBody('iframe[name="cko-3ds2-iframe"]').find('#txtButton').click();

            // Wait for 3DS verification process to finish
            cy.wait(3000);

            // Make another payment
            cy.visit('/');
            dummyCheckoutScenario.execute(false);
            checkoutAction.selectPaymentMethod('Card Payments');

            // Card options section should appear with the recently saved card
            cardRepository.getCardOptionInput().should('have.length', 2);
        });
    });
});
