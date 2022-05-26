import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import storefrontLoginAction from '../../support/actions/storefront/LoginAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import accountOrderRepository from '../../support/repositories/storefront/AccountOrderRepository';
import shopware from '../../support/services/shopware/Shopware';
import P24Repository from '../../support/repositories/storefront/payment-methods/P24Repository';
import P24Action from '../../support/actions/payment-methods/P24Action';

describe('Testing Storefront Przelewy24 Payment', () => {
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
        storefrontLoginAction.login('test@example.com', 'shopware');

        checkoutAction.addFirstProductToCart(1);
        checkoutAction.checkoutFromOffcanvas();

        cy.get('.confirm-tos .custom-checkbox label').click(1, 1);

        checkoutAction.selectPaymentMethod('Przelewy24');
    });

    it('Cancel payment', () => {
        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', 'girogate.de');

        P24Repository.getAbortButton().click();

        checkoutAction.goToOrderScreen();

        accountOrderRepository.getFirstOrderStatus().should('have.text', 'Cancelled');
    });

    it('Complete payment', () => {
        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', 'girogate.de');

        P24Action.makePayment();

        checkoutAction.goToOrderScreen();

        accountOrderRepository.getFirstOrderStatus().should('have.text', 'Paid');
    });
});
