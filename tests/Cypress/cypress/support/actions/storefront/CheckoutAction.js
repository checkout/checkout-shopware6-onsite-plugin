import listingRepository from '../../repositories/storefront/ListingRepository';
import offCanvasRepository from '../../repositories/storefront/OffCanvasRepository';

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
     * Fill data into credit card
     * @param name
     * @param number
     * @param expiryDate
     * @param cvv
     */
    fillCreditCard(name, number, expiryDate, cvv) {
        if (name) {
            cy.get('#cardholder-name').type(name);
        }

        if (number) {
            cy.get('iframe#cardNumber').then($element => {
                const $body = $element.contents().find('body')
                cy.wrap($body).find('#frames-element-card-number').click().type(number);
            });
        }

        if (expiryDate) {
            cy.get('iframe#expiryDate').then($element => {
                const $body = $element.contents().find('body')
                cy.wrap($body).find('#checkout-frames-expiry-date').click().type(expiryDate);
            });
        }

        if (cvv) {
            cy.get('iframe#cvv').then($element => {
                const $body = $element.contents().find('body')
                cy.wrap($body).find('#frames-element-cvv').click().type(cvv);
            });
        }
    }

    /**
     * Redirect to order management screen
     */
    goToOrderScreen() {
        cy.get('.header-logo-main-link').click();
        cy.visit('/account/order');
    }
}

export default new CheckoutAction();
