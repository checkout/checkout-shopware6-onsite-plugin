/**
 * @jest-environment jsdom
 */

import GooglePayDirectPlugin from '../../../../src/checkout-com/plugins/google/google-pay-direct.plugin';

describe('GooglePayDirectPlugin tests', () => {
    let googlePayDirectPlugin = undefined;
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
        GooglePayDirectPlugin.options.paymentMethodType = 'googlepay';
        googlePayDirectPlugin = new GooglePayDirectPlugin(mockElement);

        // create spy elements
        googlePayDirectPlugin.init = spyInit;
    });

    afterEach(() => {
        googlePayDirectPlugin = undefined;
        spyInit.mockClear();
    });

    test('google pay direct plugin exists', () => {
        expect(typeof googlePayDirectPlugin).toBe('object');
    });
});
