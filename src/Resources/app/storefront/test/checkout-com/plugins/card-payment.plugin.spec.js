/**
 * @jest-environment jsdom
 */

import CardPaymentPlugin from '../../../src/checkout-com/plugins/card-payment.plugin';
import template from './card-payment.plugin.template.html';

describe('CardPaymentPlugin tests', () => {
    let cardPaymentPlugin = undefined;
    let mockElement = undefined;
    const spyInit = jest.fn();

    beforeEach(() => {
        document.body.innerHTML = template;

        window.csrf = {
            enabled: false,
        };

        window.router = [];

        window.Frames = {
            init: jest.fn(),
            submitCard: jest.fn(),
            enableSubmitForm: jest.fn(),
            isCardValid: jest.fn(),
        };

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
        cardPaymentPlugin = new CardPaymentPlugin(mockElement);

        // create spy elements
        cardPaymentPlugin.init = spyInit;
    });

    afterEach(() => {
        cardPaymentPlugin = undefined;
        spyInit.mockClear();
    });

    test('card payments plugin exists', () => {
        expect(typeof cardPaymentPlugin).toBe('object');
    });
});
