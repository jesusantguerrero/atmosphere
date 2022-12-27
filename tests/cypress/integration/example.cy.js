describe('Example Test', () => {
    it('shows a homepage', () => {
        cy.visit('/');
        cy.contains('Loger DHM');
    });

    it('First time Authenticated is redirected to onboarding', () => {
        cy.login({ email: 'john.doe@example.com' })
        cy.visit('/dashboard').contains('Space Setup')
    })

    it('Authenticated is redirected to dashboard', () => {
        cy.visit('/')
        cy.get('input[data-testid=input-email').type('demo@example.com')
        cy.get('input[data-testid=input-password').type('password')
        cy.get('input[data-testid=btn-submit').click()

        cy.visit('/dashboard').contains('Welcome demo')
    })
});
