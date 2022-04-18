import Session from 'Services/utils/Session';

it('Verify Buddy was here', () => {
    Session.resetBrowserSession();
    cy.visit('/');
})
