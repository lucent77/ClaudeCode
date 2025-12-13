/**
 * Concurrency Conflict Tests
 *
 * When two workers click Complete on the same stage, exactly one succeeds;
 * the other sees a refreshed state (409 Conflict).
 */

describe('Concurrency Conflict Handling', () => {
    beforeEach(() => {
        cy.login('admin@cadcam.local', 'password123');
    });

    it('should handle concurrent stage completion with optimistic locking', () => {
        // Create a test case with COCR route
        cy.createCase({
            case_number: `CONC-${Date.now()}`,
            routes: ['COCR'],
        }).then((response) => {
            expect(response.status).to.eq(201);
            const caseId = response.body.data.case.id;

            // Get the COCR stages
            cy.apiRequest('GET', `/api/cocr/${caseId}/stages`).then((stagesResponse) => {
                const transStage = stagesResponse.body.data.stages.find(s => s.stage === 'TRANS');
                const stageId = transStage.id;
                const version = transStage.version;

                // First completion should succeed
                cy.completeStage('COCR', stageId, version, {}).then((firstResponse) => {
                    expect(firstResponse.status).to.eq(200);
                    expect(firstResponse.body.success).to.be.true;
                });

                // Second completion with same version should fail with 409 Conflict
                cy.completeStage('COCR', stageId, version, {}).then((secondResponse) => {
                    expect(secondResponse.status).to.eq(409);
                    expect(secondResponse.body.error).to.eq('conflict');
                    expect(secondResponse.body.current_version).to.be.greaterThan(version);
                });
            });
        });
    });

    it('should allow completion after refreshing with new version', () => {
        cy.createCase({
            case_number: `CONC-REFRESH-${Date.now()}`,
            routes: ['COCR'],
        }).then((response) => {
            const caseId = response.body.data.case.id;

            // Get initial stages
            cy.apiRequest('GET', `/api/cocr/${caseId}/stages`).then((stagesResponse) => {
                const designStage = stagesResponse.body.data.stages.find(s => s.stage === 'DESIGN');
                const stageId = designStage.id;
                const oldVersion = designStage.version;

                // Complete TRANS first (to move to DESIGN)
                const transStage = stagesResponse.body.data.stages.find(s => s.stage === 'TRANS');
                cy.completeStage('COCR', transStage.id, transStage.version, {}).then(() => {
                    // Try to complete DESIGN with old version (should fail)
                    cy.completeStage('COCR', stageId, oldVersion, {}).then((failResponse) => {
                        // May or may not fail depending on if version changed
                        // Now refresh and get new version
                        cy.apiRequest('GET', `/api/cocr/${caseId}/stages`).then((refreshedResponse) => {
                            const refreshedStage = refreshedResponse.body.data.stages.find(s => s.id === stageId);
                            const newVersion = refreshedStage.version;

                            // Complete with new version should work
                            cy.completeStage('COCR', stageId, newVersion, {}).then((successResponse) => {
                                expect(successResponse.status).to.eq(200);
                            });
                        });
                    });
                });
            });
        });
    });

    it('should handle concurrent updates from multiple users', () => {
        cy.createCase({
            case_number: `MULTI-USER-${Date.now()}`,
            routes: ['COCR'],
        }).then((response) => {
            const caseId = response.body.data.case.id;

            // Get stage info
            cy.apiRequest('GET', `/api/cocr/${caseId}/stages`).then((stagesResponse) => {
                const transStage = stagesResponse.body.data.stages.find(s => s.stage === 'TRANS');

                // Simulate two workers getting the same version
                const workerAVersion = transStage.version;
                const workerBVersion = transStage.version;

                // Worker A completes first
                cy.completeStage('COCR', transStage.id, workerAVersion, {
                    note: 'Worker A completed',
                }).then((workerAResponse) => {
                    expect(workerAResponse.status).to.eq(200);
                });

                // Worker B tries to complete with stale version
                cy.completeStage('COCR', transStage.id, workerBVersion, {
                    note: 'Worker B completed',
                }).then((workerBResponse) => {
                    expect(workerBResponse.status).to.eq(409);
                    expect(workerBResponse.body.message).to.include('modified');
                });
            });
        });
    });
});
