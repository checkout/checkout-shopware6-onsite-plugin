import { shallowMount, createLocalVue } from '@vue/test-utils';
import '../../../../src/module/checkout-payments/components/checkout-plugin-config-section-api';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {},
        stubs: {
            'sw-button': true,
            'sw-container': true,
            'sw-switch-field': true,
            'sw-password-field': true,
            'sw-external-link': true,
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
