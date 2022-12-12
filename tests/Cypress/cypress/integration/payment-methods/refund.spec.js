import dummyCheckoutScenario from '../../support/scenarios/DummyCheckoutScenario';
import dummyFlowBuilderScenario from '../../support/scenarios/DummyFlowBuilderScenario';

import shopConfigurationAction from '../../support/actions/admin/ShopConfigurationAction';
import checkoutAction from '../../support/actions/storefront/CheckoutAction';
import orderStateAction from '../../support/actions/admin/OrderStateAction';

import checkoutConfirmRepository from '../../support/repositories/storefront/CheckoutConfirmRepository';
import orderDetailRepository from '../../support/repositories/storefront/OrderDetailRepository';
import refundRepository from '../../support/repositories/administration/RefundRepository';
import orderListRepository from '../../support/repositories/administration/OrderListRepository';
import shopware from '../../support/services/shopware/Shopware';
import cardPaymentAction from '../../support/actions/payment-methods/CardPaymentAction';

describe('Testing Refund Manager', () => {
    describe('Testing Refund Manager with ABC', () => {
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
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);
            })

            beforeEach(() => {
                dummyCheckoutScenario.execute();
            });

            it('should hide Refund Manager', () => {
                checkoutAction.selectPaymentMethod('Cash on delivery');

                checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

                cy.url().should('include', '/checkout/finish');

                cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);

                orderListRepository.getFirstRowOrderNumber().click();

                cy.url().should('include', 'order/detail');

                // Checkout.com Refund button should not exist when payment status is not "Paid"
                refundRepository.getRefundButton().should('not.exist');
            });

            it('should show Refund Manager', () => {
                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                // Place an order using Card payments
                cardPaymentAction.makePayment();

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
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(true, 2);
                cardPaymentAction.makePayment();
            });

            beforeEach(() => {
                cy.intercept({
                    url: `${Cypress.env('apiPath')}/_action/checkout-com/order/refund`,
                    method: 'POST'
                }).as('refund');

                cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);
                cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();
                cy.url().should('include', 'order/detail');

                // Open "Refund manager" modal
                refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
            });

            it('partial refund', () => {
                // Return 1 item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded (partially)');
            });

            it('full refund', () => {
                refundRepository.getSelectAllCheckbox().click();

                // Return the remaining item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');
            });
        });

        describe('check "Include shipping costs for full refund"', () => {
            beforeEach(() => {
                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                cy.intercept({
                    url: `${Cypress.env('apiPath')}/_action/checkout-com/order/refund`,
                    method: 'POST'
                }).as('refund');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(true, 1);
                cardPaymentAction.makePayment();

                cy.wait('@makePayment');

                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);
                cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();
                cy.url().should('include', 'order/detail');

                // Open "Refund manager" modal
                refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
            });

            it('check and include shipping cost to the refunded amount', () => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.includeShippingCostsRefund', true);

                // Return the remaining item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');

                // Check if shipping cost is refunded
                refundRepository.getRefundShippingCostRow().contains('Refunded');
            });

            it('uncheck and exclude shipping cost out of the refunded amount', () => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.includeShippingCostsRefund', false);

                // Return 1 item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');

                // Shipping cost will not be refunded
                refundRepository.getRefundShippingCostRow().should('not.exist');
            });
        });

        describe('Check refund in flow builder', () => {
            before(() => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(2);
                cardPaymentAction.makePayment();

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

    describe('Testing Refund Manager with NAS', () => {
        before(() => {
            cy.setToInitialState().then(() => {
                return cy.loginViaApi();
            }).then(() => {
                return cy.createProductFixture();
            }).then(() => {
                shopConfigurationAction.setupShop('nas');
                cy.createCustomerFixtureStorefront();
            });
        });

        describe('Check Refund Manager functionality', () => {
            before(() => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(true, 2);
                cardPaymentAction.makePayment();

                cy.wait('@makePayment');
            });

            beforeEach(() => {
                cy.intercept({
                    url: `${Cypress.env('apiPath')}/_action/checkout-com/order/refund`,
                    method: 'POST'
                }).as('refund');

                cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);
                cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();
                cy.url().should('include', 'order/detail');

                // Open "Refund manager" modal
                refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
            });

            it('partial refund', () => {
                // Return 1 item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded (partially)');
            });

            it('full refund', () => {
                refundRepository.getSelectAllCheckbox().click();

                // Return the remaining item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');
            });
        });

        describe('check "Include shipping costs for full refund"', () => {
            beforeEach(() => {
                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                cy.intercept({
                    url: `${Cypress.env('apiPath')}/_action/checkout-com/order/refund`,
                    method: 'POST'
                }).as('refund');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(true, 1);
                cardPaymentAction.makePayment();

                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.loginAndOpenAdmin(`${Cypress.env('admin')}#/sw/order/index`);
                cy.get('.sw-data-grid__row--0 .sw-data-grid__cell--orderNumber a').click();
                cy.url().should('include', 'order/detail');

                // Open "Refund manager" modal
                refundRepository.getRefundButton().should('exist').contains('Checkout.com Refund').click();
            });

            it('check and include shipping cost to the refunded amount', () => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.includeShippingCostsRefund', true);

                // Return the remaining item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');

                // Check if shipping cost is refunded
                refundRepository.getRefundShippingCostRow().contains('Refunded');
            });

            it('uncheck and exclude shipping cost out of the refunded amount', () => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.includeShippingCostsRefund', false);

                // Return 1 item
                if (shopware.isVersionLower('6.4.8')) {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .customTypeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                } else {
                    refundRepository.getFirstRowReturnQuantityInput()
                        .typeSingleSelect(1, '.sw-data-grid__cell--returnQuantity');
                }

                // Confirm "Yes, I want to refund"
                refundRepository.getConfirmRefundCheckbox().click();

                // Click "Refund selected items"
                refundRepository.getModalRefundButton().should('not.be.disabled').click();

                cy.wait('@refund');
                cy.wait(3000);

                orderDetailRepository.getCurrentPaymentStatus().contains('Refunded');

                // Shipping cost will not be refunded
                refundRepository.getRefundShippingCostRow().should('not.exist');
            });
        });

        describe('Check refund in flow builder', () => {
            before(() => {
                shopConfigurationAction.setSystemConfig('CkoShopware6.config.enable3dSecure', false);

                cy.intercept({
                    url: 'https://api.sandbox.checkout.com/tokens',
                    method: 'POST'
                }).as('makePayment');

                // Place an order using Card payments
                dummyCheckoutScenario.execute(2);
                cardPaymentAction.makePayment();

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
})
