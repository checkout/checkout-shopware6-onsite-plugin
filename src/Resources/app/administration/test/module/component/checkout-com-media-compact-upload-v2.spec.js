import { shallowMount, createLocalVue } from '@vue/test-utils';
import '@core/src/app/component/media/sw-media-upload-v2';
import '@core/src/app/component/media/sw-media-compact-upload-v2';
import '../../../src/module/component/checkout-com-media-compact-upload-v2';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();

    const options = {
        localVue,
        propsData: {
            uploadTag: 'my-upload',
        },
        stubs: {
            'sw-icon': true,
            'sw-button': true,
            'sw-media-url-form': true,
            'sw-context-button': true,
            'sw-context-menu-item': true,
        },
        provide: {
            repositoryFactory: {},
            configService: {},
            mediaService: {},
        },
    };

    return shallowMount(Shopware.Component.build('checkout-com-media-compact-upload-v2'), {
        ...options,
        ...customOptions,
    });
}

describe('src/module/component/checkout-com-media-compact-upload-v2', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
