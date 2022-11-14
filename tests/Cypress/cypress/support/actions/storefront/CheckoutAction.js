import listingRepository from '../../repositories/storefront/ListingRepository';
import offCanvasRepository from '../../repositories/storefront/OffCanvasRepository';
import checkoutConfirmRepository from '../../repositories/storefront/CheckoutConfirmRepository';

class CheckoutAction {

    /**
     * Find first product from listing and add to cart with a specific quantity
     * @param quantity
     */
    addFirstProductToCart(quantity) {
        const product = listingRepository.getFirstProduct();
        product.find('.btn-buy').click();

        if (quantity) {
            offCanvasRepository.getQuantitySelect().select(`${quantity}`);
        }
    }

    /**
     * Click checkout button from offcanvas
     */
    checkoutFromOffcanvas() {
        offCanvasRepository.getCheckoutButton().click();
    }

    /**
     * select payment method by method name
     * @param paymentName
     */
    selectPaymentMethod(paymentName) {
        cy.get('.payment-methods').contains(paymentName).click({force: true});
    }

    /**
     * Fill data into Card Payments
     * @param name
     * @param number
     * @param expiryDate
     * @param cvv
     */
     fillCardPayment(name, number, expiryDate, cvv) {
        if (name) {
            cy.get('#cardholder-name').type(name);
        }

        if (number) {
            cy.getIframeBody('iframe#cardNumber').find('#frames-element-card-number').click().type(number);
        }

        if (expiryDate) {
            cy.getIframeBody('iframe#expiryDate').find('#checkout-frames-expiry-date').click().type(expiryDate);
        }

        if (cvv) {
            cy.getIframeBody('iframe#cvv').find('#frames-element-cvv').click().type(cvv);
        }
    }

    /**
     * Redirect to order management screen
     */
    goToOrderScreen() {
        cy.get('.header-logo-main-link').click();
        cy.visit('/account/order');
    }

    /**
     * Check for terms and conditions
     */
    checkTermAndCondition() {
        checkoutConfirmRepository.getTermAndConditionCheckbox().click(1, 1)
    }
}

export default new CheckoutAction();
