import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import shopware from '../../support/services/shopware/Shopware';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import sepaRepository from '../../support/repositories/storefront/payment-methods/SepaRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'CheckoutCom\\Shopware6\\Handler\\Method\\SepaHandler';

describe('Testing Storefront SEPA payment', () => {
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

    describe('Testing SEPA on checkout page', () => {
        it('hide SEPA option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('SEPA Direct Debit').should('not.exist');
        });

        it('complete payment with SEPA', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            checkoutAction.selectPaymentMethod('SEPA Direct Debit');

            // Fill in the SEPA form
            sepaRepository.getFirstName().type('John');
            sepaRepository.getLastName().type('Doe');
            sepaRepository.getIban().type('DE68100100101234567895');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            checkoutAction.goToOrderScreen();

            accountOrderRepository.getFirstOrderStatus().should('have.text', 'In Progress');
        });
    });

    describe('Testing SEPA on account page', () => {
        it('show SEPA option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('SEPA Direct Debit').should('exist');
        });

        it('hide SEPA option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('SEPA Direct Debit').should('not.exist');
        });
    });
});
