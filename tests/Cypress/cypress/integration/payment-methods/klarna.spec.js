import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';
import klarnaScenario from '../../support/scenarios/KlarnaScenario';
import orderListRepository from '../../support/repositories/administration/OrderListRepository';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'CheckoutCom\\Shopware6\\Handler\\Method\\KlarnaHandler';

describe('Testing Storefront Klarna Payment', () => {
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

    describe('Testing Klarna on checkout page', () => {
        it('hide Klarna option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Klarna').should('not.exist');
        });

        it('show Klarna option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Klarna').should('exist');
        });

        it('make payment with "Pay in 30 days"', () => {
            dummyCheckoutScenario.execute();

            klarnaScenario.payIn30Days();

            cy.url().should('include', '/checkout/finish');
        });

        it('make payment with "Flexible account"', () => {
            dummyCheckoutScenario.execute();

            klarnaScenario.payWithFlexibleAccount();

            cy.url().should('include', '/checkout/finish');
        });
    });

    describe('Testing Klarna on account page', () => {
        it('hide Klarna option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Klarna').should('not.exist');
        });

        it('show Klarna option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Klarna').should('exist');
        });
    });

    describe('Testing capture/void payment', () => {
        beforeEach(() => {
            dummyCheckoutScenario.execute();

            klarnaScenario.payIn30Days();

            cy.url().should('include', '/checkout/finish');

            cy.loginViaApi()
                .then(() => {
                    cy.openInitialPage(`${Cypress.env('admin')}#/sw/order/index`);
                    cy.get('.sw-skeleton').should('not.exist');
                    cy.get('.sw-loader').should('not.exist');
                });
        });

        it('capture payment when clicking "Capture" button', () => {
            cy.intercept({
                method: 'POST',
                url: 'api/_action/checkout-com/order/capture/**'
            }).as('capturePayment');

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getCaptureButton().should('exist').click();

            cy.wait('@capturePayment');

            // Check if payment status was changed to "Paid"
            orderDetailRepository.getCurrentPaymentStatus().contains('Paid');
            orderDetailRepository.getCaptureButton().should('not.exist');
        });

        it('capture payment when changing delivery status', () => {
            cy.intercept({
                url: `**/${Cypress.env('apiPath')}/_action/order_delivery/**/state/ship`,
                method: 'post',
            }).as(`updateDeliveryStatus`);

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            // Change delivery status to "Shipped"
            orderDetailRepository.getDeliveryStatusSelect().select('Shipped');

            // Update status and document
            orderDetailRepository.getOrderStateModal().should('be.visible');
            orderDetailRepository.getUpdateStatusButton().click();

            // Wait until the order status change to "Shipped"
            cy.wait('@updateDeliveryStatus');

            // Check if payment status was changed to "Paid"
            orderDetailRepository.getCurrentPaymentStatus().contains('Paid');
            orderDetailRepository.getCaptureButton().should('not.exist');
        });

        it('void payment when clicking "Void" button', () => {
            cy.intercept({
                method: 'POST',
                url: 'api/_action/checkout-com/order/void/**'
            }).as('voidPayment');

            orderListRepository.getFirstRowOrderNumber().click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getVoidButton().should('exist').click();

            cy.wait('@voidPayment');

            // Check if payment status was changed to "Cancelled"
            orderDetailRepository.getCurrentPaymentStatus().contains('Cancelled');
            orderDetailRepository.getVoidButton().should('not.exist');
        });
    });
});
