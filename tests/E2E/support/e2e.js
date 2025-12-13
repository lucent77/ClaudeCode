// Cypress E2E Support File

// Custom command to login
Cypress.Commands.add('login', (email = 'admin@cadcam.local', password = 'password123') => {
    cy.request({
        method: 'POST',
        url: '/api/auth/login',
        body: { email, password },
    }).then((response) => {
        expect(response.status).to.eq(200);
        window.localStorage.setItem('token', response.body.access_token);
    });
});

// Custom command to make authenticated API requests
Cypress.Commands.add('apiRequest', (method, url, body = null) => {
    const token = window.localStorage.getItem('token');
    return cy.request({
        method,
        url,
        body,
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        failOnStatusCode: false,
    });
});

// Custom command to create a test case
Cypress.Commands.add('createCase', (caseData = {}) => {
    const defaultData = {
        case_number: `TEST-${Date.now()}`,
        nychv: 'NYC',
        due_date: new Date(Date.now() + 86400000).toISOString().split('T')[0],
        patient: 'Test Patient',
        lab: 'Test Lab',
        routes: ['COCR'],
    };

    return cy.apiRequest('POST', '/api/intake', { ...defaultData, ...caseData });
});

// Custom command to complete a stage with version
Cypress.Commands.add('completeStage', (dept, stageId, version, data = {}) => {
    const url = dept === 'SOLIDEX'
        ? `/api/solidex/tooth/stages/${stageId}/complete`
        : dept === 'PRINT'
            ? `/api/print/stages/${stageId}/complete`
            : `/api/cocr/stages/${stageId}/complete`;

    return cy.apiRequest('POST', url, {
        ...data,
        _expected_version: version,
    });
});

// Reset database before each test (if needed)
Cypress.Commands.add('resetDatabase', () => {
    cy.request('POST', '/api/test/reset');
});
