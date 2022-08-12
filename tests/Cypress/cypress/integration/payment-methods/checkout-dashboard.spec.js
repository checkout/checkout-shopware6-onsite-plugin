import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import checkoutComDashboardRepository from '../../support/repositories/storefront/CheckoutComDashboardRepository';

describe('Testing CheckoutCom dashboard view', () => {
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

    it('should not show CheckoutCom dashboard when using non-CheckoutCom payment method', () => {
        dummyCheckoutScenario.execute();

        checkoutAction.selectPaymentMethod('Cash on delivery');

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', '/checkout/finish');

        cy.loginViaApi()
            .then(() => {
                cy.openInitialPage(`${Cypress.env('admin')}#/sw/order/index`);
                cy.get('.sw-skeleton').should('not.exist');
                cy.get('.sw-loader').should('not.exist');
            });

        cy.clickContextMenuItem(
            '.sw-order-list__order-view-action',
            '.sw-context-button__button',
            '.sw-data-grid__row--0'
        );

        checkoutComDashboardRepository.getDashboardCard().should('not.exist');
    });

    describe('make payment with CheckoutCom payment method', () => {
        beforeEach(() => {
            shopConfigurationAction.toggle3ds(false);

            dummyCheckoutScenario.execute();

            checkoutAction.selectPaymentMethod('Card Payments');
        });

        it('make failed payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '111');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.url().should('include', '/order/edit');

            cy.loginViaApi()
                .then(() => {
                    cy.openInitialPage(`${Cypress.env('admin')}#/sw/order/index`);
                    cy.get('.sw-skeleton').should('not.exist');
                    cy.get('.sw-loader').should('not.exist');
                });

            // Click on the first order in the list
            cy.clickContextMenuItem(
                '.sw-order-list__order-view-action',
                '.sw-context-button__button',
                '.sw-data-grid__row--0'
            );

            checkoutComDashboardRepository.getDashboardCard().should('exist');

            // Payment history should be "Authorization" and the icon should indicate "Failed"
            checkoutComDashboardRepository.getPaymentStatus().its('length').should('eq', 1);
            checkoutComDashboardRepository.getPaymentIcon().should('exist');
            checkoutComDashboardRepository.getPaymentAction().should('contain', 'Authorization');
        });

        it('make successful payment', () => {
            checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');

            checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

            cy.url().should('include', '/checkout/finish');

            cy.loginViaApi()
                .then(() => {
                    cy.openInitialPage(`${Cypress.env('admin')}#/sw/order/index`);
                    cy.get('.sw-skeleton').should('not.exist');
                    cy.get('.sw-loader').should('not.exist');
                });

            // Click on the first order in the list
            cy.clickContextMenuItem(
                '.sw-order-list__order-view-action',
                '.sw-context-button__button',
                '.sw-data-grid__row--0'
            );

            checkoutComDashboardRepository.getDashboardCard().should('exist');

            // Payment history should be "Authorization", "Capture"
            checkoutComDashboardRepository.getPaymentStatus().its('length').should('eq', 2);
            checkoutComDashboardRepository.getPaymentAction().should('contain', 'Authorization');
            checkoutComDashboardRepository.getPaymentAction().should('contain', 'Capture');
        });
    });
});
