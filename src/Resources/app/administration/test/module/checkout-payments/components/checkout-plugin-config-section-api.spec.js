import { shallowMount, createLocalVue } from '@vue/test-utils';
import '@core/src/app/component/utils/sw-inherit-wrapper';
import '../../../../src/module/checkout-payments/components/checkout-plugin-config-section-api';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {
            inheritedValue: {},
            actualConfigData: {},
            isNotDefaultSalesChannel: false,
        },
        stubs: {
            'sw-button': true,
            'sw-container': true,
            'sw-switch-field': true,
            'sw-password-field': true,
            'sw-external-link': true,
            'sw-inherit-wrapper': Shopware.Component.build('sw-inherit-wrapper'),
        },
        provide: {
            checkoutConfigService: {}
        },
    };

    return shallowMount(Shopware.Component.build('checkout-plugin-config-section-api'), {
        ...options,
        ...customOptions
    });
}

describe('src/module/checkout-payments/components/checkout-plugin-config-section-api', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
