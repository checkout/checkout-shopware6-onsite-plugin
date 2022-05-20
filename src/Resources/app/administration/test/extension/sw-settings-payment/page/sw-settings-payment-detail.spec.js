import { shallowMount, createLocalVue } from '@vue/test-utils';
import '@core/src/module/sw-settings-payment/page/sw-settings-payment-detail';
import '../../../../src/extension/sw-settings-payment/page/sw-settings-payment-detail';

function createWrapper(privileges = [], customOptions = {}) {
    const localVue = createLocalVue();
    localVue.directive('tooltip', {});

    const options = {
        localVue,
        propsData: {},
        stubs: {
            'sw-page': true,
            'sw-button': true,
            'sw-button-process': true,
            'sw-language-switch': true,
            'sw-card-view': true,
            'sw-card': true,
            'sw-container': true,
            'sw-field': true,
            'sw-language-info': true,
            'sw-upload-listener': true,
            'sw-media-upload-v2': true,
            'sw-plugin-box': true,
            'sw-textarea-field': true,
            'sw-select-rule-create': true,
            'sw-sidebar': true,
            'sw-sidebar-media-item': true,
            'sw-skeleton': true,
            'sw-entity-single-select': true,
            'checkout-com-payment-method-apple-pay': true,
        },
        provide: {
            repositoryFactory: {
                create: () => ({
                    create: () => {
                        return {
                            id: '1a2b3c',
                            name: 'Test settings-payment',
                            entity: 'settings-payment',
                            pluginId: '12321-a'
                        };
                    },
                    get: () => Promise.resolve({
                        id: '1a2b3c',
                        name: 'Test settings-payment',
                        entity: 'settings-payment',
                        pluginId: '12321-a',
                        getEntityName: () => { return 'payment_method'; }
                    }),
                    search: () => Promise.resolve({})
                })
            },
            acl: {
                can: (identifier) => {
                    if (!identifier) { return true; }

                    return privileges.includes(identifier);
                }
            },
            customFieldDataProviderService: {
                getCustomFieldSets: () => Promise.resolve([])
            },
            systemConfigApiService: {},
        },
        mocks: {
            $route: {
                query: {
                    page: 1,
                    limit: 25
                },
                params: {
                    id: '12312'
                }
            }
        },
    };

    return shallowMount(Shopware.Component.build('sw-settings-payment-detail'), {
        ...options,
        ...customOptions,
    });
}

describe('src/extension/sw-settings-payment/page/sw-settings-payment-detail', () => {
    const mockPaymentMethod = {
        name: 'Cash',
        id: '1000000000',
        pluginId: '01'
    };
    mockPaymentMethod.getEntityName = () => { return 'payment_method'; };

    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();
        await wrapper.setData({
            paymentMethod: mockPaymentMethod,
            isLoading: false
        });

        expect(wrapper.vm).toBeTruthy();
    });
});
