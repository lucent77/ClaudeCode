const { defineConfig } = require('cypress');

module.exports = defineConfig({
    e2e: {
        baseUrl: 'http://localhost:8000',
        supportFile: 'tests/E2E/support/e2e.js',
        specPattern: 'tests/E2E/specs/**/*.cy.js',
        fixturesFolder: 'tests/E2E/fixtures',
        viewportWidth: 1280,
        viewportHeight: 720,
        video: false,
        screenshotOnRunFailure: true,
        retries: {
            runMode: 2,
            openMode: 0,
        },
    },
});
