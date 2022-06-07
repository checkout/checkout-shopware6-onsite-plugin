/**
 * @jest-environment jsdom
 */

import GooglePayConfirmPlugin from '../../../../src/checkout-com/plugins/google/google-pay-confirm.plugin';
import template from './google-pay-confirm.plugin.template.html';

describe('GooglePayConfirmPlugin tests', () => {
    let googlePayConfirmPlugin = undefined;
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
        googlePayConfirmPlugin = new GooglePayConfirmPlugin(mockElement);

        // create spy elements
        googlePayConfirmPlugin.init = spyInit;
    });

    afterEach(() => {
        googlePayConfirmPlugin = undefined;
        spyInit.mockClear();
    });

    test('google pay confirm plugin exists', () => {
        expect(typeof googlePayConfirmPlugin).toBe('object');
    });
});
