/**
 * @jest-environment jsdom
 */

import ApplePayConfirmPlugin from '../../../../src/checkout-com/plugins/apple/apple-pay-confirm.plugin';
import template from './apple-pay-confirm.plugin.template.html';

describe('ApplePayConfirmPlugin tests', () => {
    let applePayConfirmPlugin = undefined;
    let mockElement = undefined;
    const spyInit = jest.fn();

    beforeEach(() => {
        document.body.innerHTML = template;

        window.csrf = {
            enabled: false,
        };

        window.router = [];

        window.PluginManager = {
            getPluginInstancesFromElement: () => {
                return new Map();
            },
            getPlugin: () => {
                return {
                    get: () => [],
                };
            },
            initializePlugins: undefined,
        };

        // mock buy box plugins
        mockElement = document.querySelector('#confirmOrderForm');
        applePayConfirmPlugin = new ApplePayConfirmPlugin(mockElement);

        // create spy elements
        applePayConfirmPlugin.init = spyInit;
    });

    afterEach(() => {
        applePayConfirmPlugin = undefined;
        spyInit.mockClear();
    });

    test('apple pay confirm plugin exists', () => {
        expect(typeof applePayConfirmPlugin).toBe('object');
    });
});
