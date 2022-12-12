import checkoutAction from '../storefront/CheckoutAction';
import checkoutConfirmRepository from '../../repositories/storefront/CheckoutConfirmRepository';

class CardPaymentAction {
    makePayment() {
        // Place an order using Card payments
        checkoutAction.selectPaymentMethod('Card Payments');
        checkoutAction.fillCardPayment(null, '4242424242424242', '0224', '100');
        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        cy.url().should('include', 'checkout/finish');
    }
}

export default new CardPaymentAction()
