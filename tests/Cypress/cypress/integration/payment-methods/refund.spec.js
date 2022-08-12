import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import klarnaScenario from '../../support/scenarios/KlarnaScenario';
import dummyFlowBuilderScenario from '../../support/scenarios/DummyFlowBuilderScenario';

import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import orderStateAction from '../../support/actions/admin/OrderStateAction';

import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';
import refundRepository from '../../support/repositories/administration/RefundRepository';
import orderListRepository from '../../support/repositories/administration/OrderListRepository';
import shopware from '../../support/services/shopware/Shopware';

describe('Testing Refund Manager', () => {
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

    describe('Check Refund Manager visibility', () => {
        before(() => {
            shopConfigurationAction.toggle3ds(false);
        })

        beforeEach(() => {
            dummyCheckoutScenario.execute();
        });

        it('should hide Refund Manager', () => {
            klarnaScenario.payWithFlexibleAccount();

            cy.url().should('include', '/checkout/finish');

            cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getCurrentPaymentStatus().contains('Authorized');

            // Checkout.com Refund button should not exist when payment status is not "Paid"
            refundRepository.getRefundButton().should('not.exist');
        });

        it('should show Refund Manager', () => {
            cy.intercept({
                url: 'https://api.sandbox.checkout.com/tokens',
                method: 'POST'
            }).as('makePayment');

            // Place an order using Card payments
            checkoutAction.selectPaymentMethod('Card Payments');
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');
            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.wait('@makePayment');

            cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            // Check if payment is succeeded
            orderDetailRepository.getCurrentPaymentStatus().contains('Paid');

            // Checkout.com Refund button and refund modal should exist when payment status is "Paid"
            refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
            refundRepository.getRefundModal().should('exist');
        });
    });

    describe('Check Refund Manager functionality', () => {
        before(() => {
            shopConfigurationAction.toggle3ds(false);

            cy.intercept({
                url: 'https://api.sandbox.checkout.com/tokens',
                method: 'POST'
            }).as('makePayment');

            // Place an order using Card payments
            dummyCheckoutScenario.execute(true, 2);
            checkoutAction.selectPaymentMethod('Card Payments');
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.wait('@makePayment');
        });

        beforeEach(() => {
            cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);

            cy.intercept({
                url: `${Cypress.env('apiPath')}/_action/checkout-com/order/refund`,
                method: 'POST'
            }).as('refund');

            cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();

            cy.url().should('include', 'order/detail');

            // Open "Refund manager" modal
            refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
        });

        it('partial refund', () => {
            // Return 1 item
            refundRepository.getFirstRowReturnQuantityInput()
                .typeSingleSelectAndCheck(1, '.sw-data-grid__cell--returnQuantity');

            // Confirm "Yes, I want to refund"
            refundRepository.getConfirmRefundCheckbox().click();

            // Click "Refund selected items"
            refundRepository.getModalRefundButton().should('not.be.disabled').click();

            cy.wait('@refund');

            orderDetailRepository.getCurrentPaymentStatus().contains('Refunded (partially)');
        });

        it('full refund', () => {
            refundRepository.getSelectAllCheckbox().click();

            // Return the remaining item
            refundRepository.getFirstRowReturnQuantityInput()
                .typeSingleSelectAndCheck(1, '.sw-data-grid__cell--returnQuantity');

            // Confirm "Yes, I want to refund"
            refundRepository.getConfirmRefundCheckbox().click();

            // Click "Refund selected items"
            refundRepository.getModalRefundButton().should('not.be.disabled').click();

            cy.wait('@refund');

            orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');
        });
    });

    describe('Check refund in flow builder', () => {
        before(() => {
            shopConfigurationAction.toggle3ds(false);

            cy.intercept({
                url: 'https://api.sandbox.checkout.com/tokens',
                method: 'POST'
            }).as('makePayment');

            // Place an order using Card payments
            dummyCheckoutScenario.execute(2);
            checkoutAction.selectPaymentMethod('Card Payments');
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.wait('@makePayment');

            // Lower Shopware version does not support Flow builder
            if (!shopware.isVersionLower('6.4.9')) {
                dummyFlowBuilderScenario.createRefundFlow();
            }
        });

        it('should do full refund when flow action is triggered', () => {
            // Lower Shopware version does not support Flow builder
            cy.skipOn(shopware.isVersionLower('6.4.9'));

            cy.visit(`${Cypress.env('admin')}#/sw/order/index`);

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            // Change delivery status to "Shipped"
            orderStateAction.changeState('delivery', 'ship');

            // Change delivery status to "Refunded" to trigger the refund flow
            orderStateAction.changeState('delivery', 'retour');

            // Check if payment is refunded
            orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');
        });
    });
})
