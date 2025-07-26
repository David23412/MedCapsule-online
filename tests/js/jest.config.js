module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/../../tests/js/setup.js'],
  collectCoverageFrom: [
    '../../public/js/**/*.js',
    '!../../public/js/vendor/**',
    '!../../public/js/lib/**'
  ],
  coverageDirectory: '../../tests/coverage',
  coverageReporters: ['html', 'text', 'lcov'],
  testMatch: [
    '<rootDir>/**/*.test.js'
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/../../public/js/$1'
  },
  transform: {
    '^.+\\.js$': 'babel-jest'
  },
  testTimeout: 10000
};