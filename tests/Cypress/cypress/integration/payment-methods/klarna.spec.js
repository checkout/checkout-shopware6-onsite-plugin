import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import klarnaRepository from '../../support/repositories/storefront/payment-methods/KlarnaRepository';

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
});
