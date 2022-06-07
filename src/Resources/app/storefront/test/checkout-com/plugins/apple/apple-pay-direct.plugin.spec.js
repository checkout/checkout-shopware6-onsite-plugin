/**
 * @jest-environment jsdom
 */

import ApplePayDirectPlugin from '../../../../src/checkout-com/plugins/apple/apple-pay-direct.plugin';

describe('ApplePayDirectPlugin tests', () => {
    let applePayDirectPlugin = undefined;
    const spyInit = jest.fn();

    beforeEach(() => {
        const mockElement = document.createElement('div');

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
        ApplePayDirectPlugin.options.paymentMethodType = 'applepay';
        applePayDirectPlugin = new ApplePayDirectPlugin(mockElement);

        // create spy elements
        applePayDirectPlugin.init = spyInit;
    });

    afterEach(() => {
        applePayDirectPlugin = undefined;
        spyInit.mockClear();
    });

    test('apple pay direct plugin exists', () => {
        expect(typeof applePayDirectPlugin).toBe('object');
    });
});
