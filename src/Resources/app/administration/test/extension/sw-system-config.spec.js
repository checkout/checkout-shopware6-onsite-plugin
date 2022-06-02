import { shallowMount, createLocalVue } from '@vue/test-utils';
import '@core/src/module/sw-settings/component/sw-system-config';
import '../../src/extension/sw-system-config';

function createWrapper() {
    const localVue = createLocalVue();
    localVue.directive('tooltip', {});
    localVue.directive('popover', {});
    localVue.filter('mediaName', Shopware.Filter.getByName('mediaName'));
    localVue.filter('unicodeUri', Shopware.Filter.getByName('unicodeUri'));

    return shallowMount(Shopware.Component.build('sw-system-config'), {
        localVue,
        propsData: {
            salesChannelSwitchable: true,
            domain: 'ConfigRenderer.config'
        },
        stubs: {
            'sw-form-field-renderer': true,
            'sw-card': true,
            'sw-sales-channel-switch': true,
            'sw-entity-single-select': true,
            'sw-button': true,
            'sw-label': true,
            'sw-inherit-wrapper': true,
            'sw-inheritance-switch': true,
            'sw-field': true,
            'sw-text-field': true,
            'sw-password-field': true,
            'sw-textarea-field': true,
            'sw-contextual-field': true,
            'sw-switch-field': true,
            'sw-number-field': true,
            'sw-checkbox-field': true,
            'sw-block-field': true,
            'sw-base-field': true,
            'sw-field-error': true,
            'sw-icon': true,
            'sw-single-select': true,
            'sw-multi-select': true,
            'sw-entity-multi-select': true,
            'sw-entity-multi-id-select': true,
            'sw-select-base': true,
            'sw-select-result-list': true,
            'sw-select-result': true,
            'sw-select-selection-list': true,
            'sw-popover': true,
            'sw-highlight-text': true,
            'sw-media-field': true,
            'sw-url-field': true,
            'sw-media-media-item': true,
            'sw-media-base-item': true,
            'sw-media-preview-v2': true,
            'sw-colorpicker': true,
            'sw-upload-listener': true,
            'sw-simple-search-field': true,
            'sw-loader': true,
            'sw-datepicker': true,
            'sw-text-editor': true,
        },
        provide: {
            systemConfigApiService: {},
            repositoryFactory: {},
            validationService: {},
            mediaService: {},
            feature: {}
        },
    });
}

describe('src/extension/sw-system-config', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });
});
