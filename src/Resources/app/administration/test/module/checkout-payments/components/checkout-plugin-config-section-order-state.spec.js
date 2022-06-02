import { shallowMount, createLocalVue } from '@vue/test-utils';
import '../../../../src/module/checkout-payments/components/checkout-plugin-config-section-order-state';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {},
        stubs: {
            'sw-loader': true,
            'sw-single-select': true,
        },
        provide: {
            repositoryFactory: {}
        },
    };

    return shallowMount(Shopware.Component.build('checkout-plugin-config-section-order-state'), {
        ...options,
        ...customOptions
    });
}

describe('src/module/checkout-payments/components/checkout-plugin-config-section-order-state', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
