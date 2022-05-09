import template from './checkout-com-payment-method-apple-pay.html.twig';
import { SETUP_LINK } from '../../../../constant/settings';
import './checkout-com-payment-method-apple-pay.scss';

const {
    Component,
    Data,
    Context,
} = Shopware;
const { Criteria } = Data;

/**
 * This component is used to handle the configuration of the Apple Pay payment method.
 * It will save the configuration and use it to validate the merchant within Apple Pay.
 */
Component.register('checkout-com-payment-method-apple-pay', {
    template,

    inject: [
        'acl',
        'repositoryFactory',
        'checkoutMediaService',
    ],

    props: {
        salesChannelId: {
            type: String,
            required: false,
        },
        paymentMethodConfigs: {
            type: Object,
            required: false,
        },
        parentPaymentMethodConfigs: {
            type: Object,
            required: false,
        },
        parentCheckoutMediaPreviews: {
            type: Object,
            required: false,
        },
    },

    computed: {
        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },
        salesChannelDomainCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannelId));
            return criteria;
        },
        isNotDefaultSalesChannel() {
            return this.salesChannelId !== null;
        },
        domainColumns() {
            return [
                {
                    property: 'url',
                    label: this.$tc('checkout-payments.paymentMethod.applePay.configuration.urlColumn'),
                },
                {
                    property: 'media',
                    label: this.$tc('checkout-payments.paymentMethod.applePay.configuration.mediaColumn'),
                },
            ];
        },
    },

    data() {
        return {
            isLoading: false,
            salesChannelDomains: null,
            setupLink: SETUP_LINK.APPLE_PAY,
            applePayFiles: {
                keyMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-key',
                    accept: '.key',
                    preview: null,
                },
                pemMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-pem',
                    accept: '.pem',
                    preview: null,
                },
            },
            domainMedias: {
                tag: 'checkout-com-payment-method-apple-pay-domain',
                accept: '.txt',
                data: [],
            },
        };
    },

    watch: {
        salesChannelId() {
            // // Reset the files when the sales channel is changed
            Object.keys(this.applePayFiles).forEach((property) => {
                this.setApplePayFilesPreview(property, null);
            });

            this.loadData();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadData();
        },

        async loadData() {
            this.emitLoading(true);

            try {
                await Promise.all([
                    this.loadSalesChannelDomain(),
                    this.loadMediaPreviews(),
                ]);
            } finally {
                this.emitLoading(false);
            }
        },

        async loadSalesChannelDomain() {
            if (!this.isNotDefaultSalesChannel) {
                return;
            }

            const salesChannelDomains = await this.salesChannelDomainRepository.search(
                this.salesChannelDomainCriteria,
                Context.api,
            );

            const domainMediasData = await Promise.all(salesChannelDomains.map(async (salesChannelDomain) => {
                const domainMediaData = {
                    id: salesChannelDomain.id,
                    url: salesChannelDomain.url,
                    media: null,
                };

                const domainMediasConfigs = this.paymentMethodConfigs.domainMedias;
                if (!domainMediasConfigs) {
                    return domainMediaData;
                }

                // Find the domain medias config for the current domain
                const domainMediasConfig = domainMediasConfigs.find(
                    (domainMediaConfigs) => domainMediaConfigs.domainId === salesChannelDomain.id,
                );

                if (!domainMediasConfig || !domainMediasConfig.mediaId) {
                    return domainMediaData;
                }

                return {
                    ...domainMediaData,
                    media: await this.checkoutMediaService.getSystemMedia(domainMediasConfig.mediaId),
                };
            }));

            this.$set(this.domainMedias, 'data', domainMediasData);
        },

        /**
         * Load the media ID from the Shopware checkout configuration
         * Then, Load the media entity from the media ID
         * And map the media entity to the preview property of the applePayFiles object.
         */
        loadMediaPreviews() {
            if (!this.paymentMethodConfigs) {
                return Promise.resolve();
            }

            return Promise.all(Object.keys(this.applePayFiles).map((propertyKey) => {
                if (!this.paymentMethodConfigs.hasOwnProperty(propertyKey)) {
                    return Promise.resolve();
                }

                const mediaId = this.paymentMethodConfigs[propertyKey];
                if (!mediaId) {
                    this.setApplePayFilesPreview(propertyKey, '');

                    return Promise.resolve();
                }

                return this.checkoutMediaService
                    .getSystemMedia(mediaId)
                    .then((media) => {
                        // We have to get private media from media service
                        this.setApplePayFilesPreview(propertyKey, media);
                    });
            }));
        },

        onInputChange(field, value) {
            this.setCheckoutConfigs(field, value);
        },

        /**
         * Set preview property for applePayFiles
         */
        setApplePayFilesPreview(propertyKey, media) {
            if (!this.applePayFiles.hasOwnProperty(propertyKey)) {
                return;
            }

            if (!this.isNotDefaultSalesChannel) {
                this.setParentApplePayPreviews(propertyKey, media);
            }

            this.applePayFiles[propertyKey].preview = media;
        },

        /**
         * Add/Remove/Update the data' property of the domainMedias object
         */
        setDomainMediasData(domainId, media) {
            const domainMediasData = this.domainMedias.data;

            domainMediasData.map((domainMedia) => {
                if (domainId === domainMedia.id) {
                    domainMedia.media = media;
                }

                return domainMedia;
            });

            this.$set(this.domainMedias, 'data', domainMediasData);
        },

        setParentApplePayPreviews(propertyKey, media) {
            this.$emit('set-parent-checkout-media-preview', propertyKey, media);
        },

        setCheckoutConfigs(propertyKey, data) {
            this.$emit('set-checkout-payment-configs', propertyKey, data);
        },

        /**
         * @param {string} propertyKey
         * @param {Object|null|''} media
         * @returns {Promise<void>}
         */
        async onMediaUpdate(propertyKey, media) {
            this.emitLoading(true);

            this.setApplePayFilesPreview(propertyKey, media);
            this.setCheckoutConfigs(propertyKey, media ? media.id : media);
            this.$emit('save-system-config');

            this.emitLoading(false);
        },

        /**
         * @param {string} domainId
         * @param {Object|null|''} media
         * @returns {Promise<void>}
         */
        async onDomainMediaUpdate(domainId, media) {
            this.emitLoading(true);

            this.setDomainMediasData(domainId, media);

            const domainMediasConfigs = this.domainMedias.data.map((domainMedia) => ({
                domainId: domainMedia.id,
                mediaId: domainMedia.media ? domainMedia.media.id : null,
            }));
            this.setCheckoutConfigs('domainMedias', domainMediasConfigs);
            this.$emit('save-system-config');

            this.emitLoading(false);
        },

        emitLoading(isLoading) {
            this.$emit('set-loading', isLoading);
        },
    },
});
