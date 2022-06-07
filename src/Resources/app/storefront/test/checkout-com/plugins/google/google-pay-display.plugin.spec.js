/**
 * @jest-environment jsdom
 */

import GooglePayDisplayPlugin from '../../../../src/checkout-com/plugins/google/google-pay-display.plugin';

describe('GooglePayDisplayPlugin tests', () => {
    let googlePayDisplayPlugin = undefined;
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
        GooglePayDisplayPlugin.options.active = true;
        googlePayDisplayPlugin = new GooglePayDisplayPlugin(mockElement);

        // create spy elements
        googlePayDisplayPlugin.init = spyInit;
    });

    afterEach(() => {
        googlePayDisplayPlugin = undefined;
        spyInit.mockClear();
    });

    test('google pay direct plugin exists', () => {
        expect(typeof googlePayDisplayPlugin).toBe('object');
    });
});
