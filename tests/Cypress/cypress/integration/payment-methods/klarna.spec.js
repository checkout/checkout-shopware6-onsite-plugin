import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import klarnaRepository from '../../support/repositories/storefront/payment-methods/KlarnaRepository';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'CheckoutCom\\Shopware6\\Handler\\Method\\KlarnaHandler';

describe('Testing Storefront Klarna Payment', () => {
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

            checkoutAction.selectPaymentMethod('Klarna');

            // Choose "Pay in 30 days"
            klarnaRepository.getPayLaterOption().click();

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.intercept('POST', 'https://js.playground.klarna.com/eu/profile/login/**/init').as('initKlarna');
            cy.wait('@initKlarna');

            // Fill in necessary information
            klarnaRepository.getEmailOrPhoneInput().type('017614287464');
            klarnaRepository.getContinueButton().click();
            klarnaRepository.getOtpInput().type('123456');
            klarnaRepository.getPurchaseButton().click();

            cy.url().should('include', '/checkout/finish');
        });

        it('make payment with "Flexible account"', () => {
            dummyCheckoutScenario.execute();

            checkoutAction.selectPaymentMethod('Klarna');

            // Choose "Flexible account"
            klarnaRepository.getPayOverTimeOption().click();

            cy.intercept('POST', 'https://eu.playground.klarnaevt.com/**').as('initKlarna');
            cy.wait('@initKlarna');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            // Fill in necessary information
            klarnaRepository.getDateOfBirthInput().type('01011990');
            klarnaRepository.getApproveButton().click();

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

            checkoutAction.selectPaymentMethod('Klarna');

            // Choose "Pay in 30 days"
            klarnaRepository.getPayLaterOption().click();

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.intercept('POST', 'https://js.playground.klarna.com/eu/profile/login/**/init').as('initKlarna');
            cy.wait('@initKlarna');

            // Fill in necessary information
            klarnaRepository.getEmailOrPhoneInput().type('017614287464');
            klarnaRepository.getContinueButton().click();
            klarnaRepository.getOtpInput().type('123456');
            klarnaRepository.getPurchaseButton().click();

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

            cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();

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

            cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();

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

            cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();

            cy.url().should('include', 'order/detail');

            orderDetailRepository.getVoidButton().should('exist').click();

            cy.wait('@voidPayment');

            // Check if payment status was changed to "Cancelled"
            orderDetailRepository.getCurrentPaymentStatus().contains('Cancelled');
            orderDetailRepository.getVoidButton().should('not.exist');
        });
    });
});
