import checkoutAction from '../actions/storefront/CheckoutAction';
import klarnaRepository from '../repositories/storefront/payment-methods/KlarnaRepository';
import checkoutConfirmRepository from '../repositories/storefront/CheckoutConfirmRepository';

class KlarnaScenario {
    payIn30Days() {
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
    }

    payWithFlexibleAccount() {
        checkoutAction.selectPaymentMethod('Klarna');

        // Choose "Flexible account"
        klarnaRepository.getPayOverTimeOption().click();

        cy.intercept('POST', 'https://eu.playground.klarnaevt.com/**').as('initKlarna');
        cy.wait('@initKlarna');

        checkoutConfirmRepository.getConfirmSubmitButton().should('not.be.disabled').click();

        // Fill in necessary information
        klarnaRepository.getDateOfBirthInput().type('01011990');
        klarnaRepository.getApproveButton().click();
    }
}

export default new KlarnaScenario()
