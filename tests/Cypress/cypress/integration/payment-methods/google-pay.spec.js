import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'Cko\\Shopware6\\Handler\\Method\\GooglePayHandler';

describe('Testing Storefront Google Pay visibility', () => {
    beforeEach(() => {
        cy.setToInitialState().then(() => {
            return cy.loginViaApi();
        }).then(() => {
            return cy.createProductFixture();
        }).then(() => {
            shopConfigurationAction.setupShop();
            cy.createCustomerFixtureStorefront();
        });
    });

    describe('Testing Google Pay on checkout page', () => {
        it('hide Google Pay option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Google Pay').should('not.exist');
        });

        it('hide Google Pay option if cookie is not set', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Google Pay').parents('.payment-method').should('have.class', 'd-none');
        });

        it('show Google Pay option if payment method is active and cookie is set', () => {
            cy.setCookie('cko-payment_google-pay', '1')

            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('Google Pay').should('exist');
        });
    });

    describe('Testing Google Pay on account page', () => {
        it('hide Google Pay option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Google Pay').should('not.exist');
        });

        it('hide Google Pay option if cookie is not set', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Google Pay').parents('.payment-method').should('have.class', 'd-none');
        });

        it('show Google Pay option if payment method is active and cookie is set', () => {
            cy.setCookie('cko-payment_google-pay', '1')

            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('Google Pay').should('exist');
        });
    });

    describe('Testing Google Pay on product listing page', () => {
        it('hide Google Pay button if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');

            cy.get('.gpay-card-info-container-fill').should('not.exist');
        });

        it('hide Google Pay button if cookie is not set', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');

            cy.get('.gpay-card-info-container-fill').should('not.exist');
        });

        it('show Google Pay button if payment method is active and cookie is set', () => {
            cy.setCookie('cko-payment_google-pay', '1')

            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');

            cy.get('.checkout-com-direct-google-pay').should('exist');
        });
    });
})
