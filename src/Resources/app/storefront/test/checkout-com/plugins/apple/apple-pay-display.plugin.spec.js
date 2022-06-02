/**
 * @jest-environment jsdom
 */

import ApplePayDisplayPlugin from '../../../../src/checkout-com/plugins/apple/apple-pay-display.plugin';

describe('ApplePayDisplayPlugin tests', () => {
    let applePayDisplayPlugin = undefined;
    let spyInit = jest.fn();

    beforeEach(() => {
        const mockElement = document.createElement('div');

        window.csrf = {
            enabled: false
        };

        window.router = [];

        window.PluginManager = {
            getPluginInstancesFromElement: () => {
                return new Map();
            },
            getPlugin: () => {
                return {
                    get: () => []
                };
            },
            initializePlugins: undefined
        };

        // mock buy box plugins
        ApplePayDisplayPlugin.options.active = true;
        applePayDisplayPlugin = new ApplePayDisplayPlugin(mockElement);

        // create spy elements
        applePayDisplayPlugin.init = spyInit;
    });

    afterEach(() => {
        applePayDisplayPlugin = undefined;
        spyInit.mockClear();
    });

    test('apple pay direct plugin exists', () => {
        expect(typeof applePayDisplayPlugin).toBe('object');
    });
});
