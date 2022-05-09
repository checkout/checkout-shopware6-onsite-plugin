import { shallowMount, createLocalVue } from '@vue/test-utils';
import '../../../../src/extension/sw-settings-payment/component/checkout-com-payment-method-apple-pay';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {
            salesChannelId: '',
            paymentMethodConfigs: {},
            parentPaymentMethodConfigs: {},
            parentCheckoutMediaPreviews: {}
        },
        stubs: {
            'sw-field': true,
            'sw-external-link': true,
            'sw-upload-listener': true,
            'sw-inherit-wrapper': true,
            'checkout-com-inherit-media-upload': true,
            'checkout-com-media-compact-upload-v2': true,
        },
        provide: {
            repositoryFactory: {},
            checkoutMediaService: {}
        },
    };

    return shallowMount(Shopware.Component.build('checkout-com-payment-method-apple-pay'), {
        ...options,
        ...customOptions
    });
}

describe('src/extension/sw-settings-payment/component/checkout-com-payment-method-apple-pay', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
