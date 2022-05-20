const { resolve, join } = require('path');

const ADMIN_PATH = resolve('../../../../vendor/shopware/administration/Resources/app/administration');
process.env.ADMIN_PATH = process.env.ADMIN_PATH || ADMIN_PATH;

module.exports = {
    preset: '@shopware-ag/jest-preset-sw6-admin',

    globals: {
        adminPath: process.env.ADMIN_PATH,
    },

    verbose: true,

    collectCoverageFrom: [
        'src/**/*.js',
    ],

    moduleNameMapper: {
        '\@vue/test-utils': '<rootDir>/node_modules/@vue/test-utils',
        '\@core/src(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        '../../_mocks_/entity-schema.json': '<rootDir>/test/_mocks_/entity-schema.json',
        vue$: 'vue/dist/vue.common.dev.js',
    },

    setupFilesAfterEnv: [
        resolve(join(process.env.ADMIN_PATH, '/test/_setup/prepare_environment.js')),
    ],
};
