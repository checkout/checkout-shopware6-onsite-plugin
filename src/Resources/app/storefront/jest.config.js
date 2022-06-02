const { resolve } = require('path');

const STOREFRONT_PATH = resolve('../../../../vendor/shopware/storefront/Resources/app/storefront');
process.env.STOREFRONT_PATH = process.env.STOREFRONT_PATH || STOREFRONT_PATH;

module.exports = {
    verbose: true,

    moduleFileExtensions: [
        'js',
    ],

    transform: {
        '.*.js': '<rootDir>/node_modules/babel-jest',
        '.*.html$': '<rootDir>/node_modules/html-loader-jest',
    },

    moduleNameMapper: {
        '^src(.*)$': `${process.env.STOREFRONT_PATH}/src$1`,
    },

    transformIgnorePatterns: [
        'node_modules/(?!variables/.*)',
    ],

    testMatch: [
        '<rootDir>/test/**/*.spec.js',
    ],
};
