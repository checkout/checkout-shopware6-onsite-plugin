import { shallowMount, createLocalVue } from '@vue/test-utils';
import '../../../../src/module/checkout-payments/components/checkout-plugin-config-webhook-section';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {},
        stubs: {
            'sw-field': true,
        },
        provide: {
            repositoryFactory: {}
        },
    };

    return shallowMount(Shopware.Component.build('checkout-plugin-config-webhook-section'), {
        ...options,
        ...customOptions
    });
}

describe('src/module/checkout-payments/components/checkout-plugin-config-webhook-section', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
