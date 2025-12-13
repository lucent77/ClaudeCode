/**
 * Notes Tagging & Search Tests
 *
 * Notes saved with ["3D PRINT","CR"] render as chips and searchable by tag.
 */

describe('Notes with Tag Chips', () => {
    let testCaseId;

    beforeEach(() => {
        cy.login('admin@cadcam.local', 'password123');

        // Create a test case for notes
        cy.createCase({
            case_number: `NOTES-${Date.now()}`,
            routes: ['COCR', 'PRINT'],
        }).then((response) => {
            testCaseId = response.body.data.case.id;
        });
    });

    it('should create notes with multiple tags', () => {
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'This is a test note with multiple tags',
            tags: ['3D PRINT', 'CR'],
        }).then((response) => {
            expect(response.status).to.eq(201);
            expect(response.body.data.note.tags).to.include('3D PRINT');
            expect(response.body.data.note.tags).to.include('CR');
            expect(response.body.data.note.body).to.eq('This is a test note with multiple tags');
        });
    });

    it('should search notes by single tag', () => {
        // Create notes with different tags
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Note with 3D PRINT tag',
            tags: ['3D PRINT'],
        });

        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Note with CR tag',
            tags: ['CR'],
        });

        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Note with both tags',
            tags: ['3D PRINT', 'CR'],
        });

        // Search for notes with 3D PRINT tag
        cy.apiRequest('GET', `/api/notes?tag=3D PRINT`).then((response) => {
            expect(response.status).to.eq(200);
            const notes = response.body.data;
            expect(notes.length).to.be.at.least(2);
            notes.forEach(note => {
                expect(note.tags).to.include('3D PRINT');
            });
        });
    });

    it('should search notes by multiple tags (OR logic)', () => {
        // Create several notes
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Urgent note',
            tags: ['URGENT'],
        });

        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'QC Issue note',
            tags: ['QC ISSUE'],
        });

        // Search with multiple tags
        cy.apiRequest('GET', `/api/notes?tags[]=URGENT&tags[]=QC ISSUE`).then((response) => {
            expect(response.status).to.eq(200);
            const notes = response.body.data;
            notes.forEach(note => {
                const hasMatchingTag = note.tags.some(
                    tag => ['URGENT', 'QC ISSUE'].includes(tag)
                );
                expect(hasMatchingTag).to.be.true;
            });
        });
    });

    it('should search notes by body text', () => {
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Customer requested specific shade matching',
            tags: ['CUSTOMER REQUEST'],
        });

        cy.apiRequest('GET', `/api/notes?q=shade matching`).then((response) => {
            expect(response.status).to.eq(200);
            const notes = response.body.data;
            const matchingNote = notes.find(n => n.body.includes('shade matching'));
            expect(matchingNote).to.exist;
        });
    });

    it('should scope notes correctly', () => {
        // Create note scoped to case
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Case-level note',
            scope: 'CASE',
            tags: ['COCR'],
        });

        // Query notes for specific case
        cy.apiRequest('GET', `/api/notes?scope=CASE&scope_id=${testCaseId}`).then((response) => {
            expect(response.status).to.eq(200);
            const notes = response.body.data;
            notes.forEach(note => {
                expect(note.scope).to.eq('CASE');
                expect(note.scope_id).to.eq(testCaseId);
            });
        });
    });

    it('should display notes with creator information', () => {
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'Note with creator info',
            tags: ['3D PRINT'],
        }).then((response) => {
            const note = response.body.data.note;
            expect(note.creator).to.exist;
            expect(note.creator.name).to.exist;
        });
    });

    it('should filter notes by scope and tag combination', () => {
        // Create notes in different scopes
        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'COCR department note',
            scope: 'COCR',
            scope_id: testCaseId,
            tags: ['DESIGN CHANGE'],
        });

        cy.apiRequest('POST', `/api/cases/${testCaseId}/notes`, {
            body: 'PRINT department note',
            scope: 'PRINT',
            scope_id: testCaseId,
            tags: ['DESIGN CHANGE'],
        });

        // Filter by scope
        cy.apiRequest('GET', `/api/notes?scope=COCR&tag=DESIGN CHANGE`).then((response) => {
            const notes = response.body.data;
            notes.forEach(note => {
                expect(note.scope).to.eq('COCR');
                expect(note.tags).to.include('DESIGN CHANGE');
            });
        });
    });
});

describe('Notes Tag Rendering', () => {
    beforeEach(() => {
        cy.login();
    });

    it('should render tag chips correctly on case detail page', () => {
        cy.createCase({
            case_number: `UI-NOTES-${Date.now()}`,
            routes: ['COCR'],
        }).then((response) => {
            const caseId = response.body.data.case.id;

            // Add note with tags
            cy.apiRequest('POST', `/api/cases/${caseId}/notes`, {
                body: 'UI test note',
                tags: ['3D PRINT', 'CR', 'URGENT'],
            });

            // Visit case detail page
            cy.visit(`/cases/${caseId}`);

            // Wait for page load
            cy.wait(1000);

            // Click on Notes tab
            cy.contains('Notes').click();

            // Verify tags are rendered as chips
            cy.get('[class*="rounded-md"]').contains('3D PRINT').should('exist');
            cy.get('[class*="rounded-md"]').contains('CR').should('exist');
            cy.get('[class*="rounded-md"]').contains('URGENT').should('exist');
        });
    });
});
