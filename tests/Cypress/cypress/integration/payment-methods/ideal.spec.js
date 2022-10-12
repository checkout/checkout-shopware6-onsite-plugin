import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';

const paymentEndpoint = 'payment-method';
const paymentHandler = 'Cko\\Shopware6\\Handler\\Method\\IdealHandler';

describe('Testing Storefront iDEAL Payment', () => {
    before(() => {
        cy.setToInitialState().then(() => {
            return cy.loginViaApi();
        }).then(() => {
            return cy.createProductFixture();
        }).then(() => {
            shopConfigurationAction.setupShop();
            cy.createCustomerFixtureByCountry({}, 'NL');
        });
    });

    describe('Testing iDEAL on checkout page', () => {
        it('hide iDEAL option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            dummyCheckoutScenario.execute();

            cy.get('.payment-methods').contains('iDEAL').should('not.exist');
        });

        it('complete payment with iDEAL', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            dummyCheckoutScenario.execute();

            checkoutAction.selectPaymentMethod('iDEAL');

            // Fill in the iDEAL form
            cy.get('#idealBic').type('INGBNL2A');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            checkoutAction.goToOrderScreen();

            accountOrderRepository.getFirstOrderStatus().should('have.text', 'Paid');
        });
    });

    describe('Testing iDEAL on account page', () => {
        it('show iDEAL option if payment method is active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: true });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('iDEAL').should('exist');
        });

        it('hide iDEAL option if payment method is not active', () => {
            cy.updateViaAdminApiWithIdentifier(paymentEndpoint, paymentHandler, { active: false });

            storefrontLoginAction.login('test@example.com', 'shopware');
            cy.visit('/account/payment');

            cy.get('.payment-methods').contains('iDEAL').should('not.exist');
        });
    });
});
