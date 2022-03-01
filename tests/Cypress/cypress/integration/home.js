it('Verify Buddy was here', () => {
    cy.visit('/');

    cy.contains('Buddy was here');
})
