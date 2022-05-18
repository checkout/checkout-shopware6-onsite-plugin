class LoginAction {

    /**
     * Execute login on storefront
     * @param email
     * @param password
     */
    login(email, password) {
        cy.visit('/account/login');

        // Login
        cy.get('.login-card').should('be.visible');
        cy.get('#loginMail').typeAndCheckStorefront(email);
        cy.get('#loginPassword').typeAndCheckStorefront(password);
        cy.get('.login-submit [type="submit"]').click();

        cy.screenshot('login screen');

        cy.visit('/');
    }
}

export default new LoginAction();
