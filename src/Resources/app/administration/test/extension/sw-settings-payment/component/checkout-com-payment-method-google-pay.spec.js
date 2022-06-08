import { shallowMount, createLocalVue } from '@vue/test-utils';
import '../../../../src/extension/sw-settings-payment/component/checkout-com-payment-method-google-pay';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {
            salesChannelId: '',
            paymentMethodConfigs: {},
            parentPaymentMethodConfigs: {}
        },
        stubs: {
            'sw-field': true,
            'sw-inherit-wrapper': true,
        },
        provide: {},
    };

    return shallowMount(Shopware.Component.build('checkout-com-payment-method-google-pay'), {
        ...options,
        ...customOptions
    });
}

describe('src/extension/sw-settings-payment/component/checkout-com-payment-method-google-pay', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
