/**
 * @jest-environment jsdom
 */

import CreditCardPlugin from '../../../src/checkout-com/plugins/credit-card.plugin';
import template from './credit-card.plugin.template.html';

describe('CreditCardPlugin tests', () => {
    let creditCardPlugin = undefined;
    let mockElement = undefined;
    let spyInit = jest.fn();

    beforeEach(() => {
        document.body.innerHTML = template;

        window.csrf = {
            enabled: false
        };

        window.router = [];

        window.Frames = {
            init: jest.fn(),
            submitCard: jest.fn(),
            enableSubmitForm: jest.fn()
        };

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
        mockElement = document.querySelector('#confirmOrderForm');
        creditCardPlugin = new CreditCardPlugin(mockElement);

        // create spy elements
        creditCardPlugin.init = spyInit;
    });

    afterEach(() => {
        creditCardPlugin = undefined;
        spyInit.mockClear();
    });

    test('credit card plugin exists', () => {
        expect(typeof creditCardPlugin).toBe('object');
    });
});
