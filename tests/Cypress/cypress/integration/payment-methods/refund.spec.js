import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import klarnaScenario from '../../support/scenarios/KlarnaScenario';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';
import refundRepository from '../../support/repositories/administration/RefundRepository';
import orderListRepository from '../../support/repositories/administration/OrderListRepository';

describe('Testing Refund Manager', () => {
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

        it('Partial refund', () => {
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

        it('Full refund', () => {
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
})
