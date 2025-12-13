/**
 * SOLIDEX Roll-up Tests
 *
 * SOLIDEX: if any tooth not at QC, case cannot be DONE.
 * Case-level complete triggers only when all tooth rows reached QC/Done.
 */

describe('SOLIDEX Per-Tooth Roll-up', () => {
    beforeEach(() => {
        cy.login('admin@cadcam.local', 'password123');
    });

    it('should not mark case as DONE if any tooth is not complete', () => {
        // Create a case with SOLIDEX route and multiple teeth
        cy.createCase({
            case_number: `SOLIDEX-ROLLUP-${Date.now()}`,
            routes: ['SOLIDEX'],
            solidex: {
                teeth: ['8', '9', '10'],
                note: 'Test case with 3 teeth',
            },
        }).then((response) => {
            expect(response.status).to.eq(201);
            const caseId = response.body.data.case.id;

            // Get teeth
            cy.apiRequest('GET', `/api/solidex/${caseId}/teeth`).then((teethResponse) => {
                expect(teethResponse.body.data.teeth).to.have.length(3);

                const teeth = teethResponse.body.data.teeth;

                // Complete all stages for tooth 8 only
                const tooth8 = teeth.find(t => t.tooth_number === '8');
                cy.completAllToothStages(tooth8.id);

                // Check if case is NOT done (other teeth incomplete)
                cy.apiRequest('GET', `/api/solidex/${caseId}/isComplete`).then((completeResponse) => {
                    expect(completeResponse.body.data.is_complete).to.be.false;
                    expect(completeResponse.body.data.completion_percentage).to.be.lessThan(100);
                });

                // Verify case status is not DONE
                cy.apiRequest('GET', `/api/cases/${caseId}`).then((caseResponse) => {
                    expect(caseResponse.body.data.case.status).to.not.eq('DONE');
                });
            });
        });
    });

    it('should mark case as DONE when all teeth complete all stages', () => {
        cy.createCase({
            case_number: `SOLIDEX-COMPLETE-${Date.now()}`,
            routes: ['SOLIDEX'],
            solidex: {
                teeth: ['14', '15'],
                note: 'Test case with 2 teeth',
            },
        }).then((response) => {
            const caseId = response.body.data.case.id;

            // Get teeth
            cy.apiRequest('GET', `/api/solidex/${caseId}/teeth`).then((teethResponse) => {
                const teeth = teethResponse.body.data.teeth;

                // Complete all stages for all teeth
                const completePromises = teeth.map(tooth => {
                    return cy.completAllToothStages(tooth.id);
                });

                // After all teeth are complete, check case status
                cy.wrap(Promise.all(completePromises)).then(() => {
                    // Wait a moment for status update
                    cy.wait(500);

                    cy.apiRequest('GET', `/api/solidex/${caseId}/isComplete`).then((completeResponse) => {
                        expect(completeResponse.body.data.is_complete).to.be.true;
                        expect(completeResponse.body.data.completion_percentage).to.eq(100);
                    });
                });
            });
        });
    });

    it('should correctly track per-tooth progress', () => {
        cy.createCase({
            case_number: `SOLIDEX-PROGRESS-${Date.now()}`,
            routes: ['SOLIDEX'],
            solidex: {
                teeth: ['3', '4', '5', '6'],
            },
        }).then((response) => {
            const caseId = response.body.data.case.id;

            cy.apiRequest('GET', `/api/solidex/${caseId}/teeth`).then((teethResponse) => {
                const teeth = teethResponse.body.data.teeth;
                expect(teeth).to.have.length(4);

                // Each tooth should have 6 stages (TRANSCAN, PRECAD, CAD, PRECAM, CNC, QC)
                teeth.forEach(tooth => {
                    expect(tooth.stages).to.have.length(6);
                    expect(tooth.status).to.eq('OPEN');
                    expect(tooth.progress).to.eq(0);
                });

                // Complete first stage of first tooth
                const firstTooth = teeth[0];
                const firstStage = firstTooth.stages.find(s => s.stage === 'TRANSCAN');

                cy.completeStage('SOLIDEX', firstStage.id, firstStage.version, {}).then(() => {
                    // Check tooth progress updated
                    cy.apiRequest('GET', `/api/solidex/${caseId}/teeth`).then((updatedResponse) => {
                        const updatedTooth = updatedResponse.body.data.teeth.find(
                            t => t.tooth_number === firstTooth.tooth_number
                        );
                        // 1 of 6 stages complete = ~16.7%
                        expect(updatedTooth.progress).to.be.greaterThan(0);
                        expect(updatedTooth.status).to.eq('IN_PROGRESS');
                    });
                });
            });
        });
    });
});

// Helper command to complete all stages for a tooth
Cypress.Commands.add('completAllToothStages', (toothId) => {
    const stages = ['TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'CNC', 'QC'];

    const completeStagesSequentially = (index = 0) => {
        if (index >= stages.length) return;

        return cy.apiRequest('GET', `/api/solidex/tooth/${toothId}/stages`).then((response) => {
            const currentStage = response.body.data.stages.find(s => s.stage === stages[index]);
            if (currentStage && currentStage.status !== 'DONE') {
                const data = currentStage.stage === 'CNC' ? { machine: 'SOLIDEX-CNC-1' } : {};
                return cy.completeStage('SOLIDEX', currentStage.id, currentStage.version, data).then(() => {
                    return completeStagesSequentially(index + 1);
                });
            }
            return completeStagesSequentially(index + 1);
        });
    };

    return completeStagesSequentially();
});
