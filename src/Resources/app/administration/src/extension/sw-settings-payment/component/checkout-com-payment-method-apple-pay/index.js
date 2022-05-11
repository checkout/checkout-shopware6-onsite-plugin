import template from './checkout-com-payment-method-apple-pay.html.twig';
import { SETUP_LINK } from '../../../../constant/settings';
import { getCheckoutConfig } from '../../../../services/utils/system-config.utils';
import './checkout-com-payment-method-apple-pay.scss';

const { Component, Context, Mixin } = Shopware;

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

    mixins: [Mixin.getByName('notification')],

    props: {
        checkoutConfigs: {
            type: Object,
            required: false,
        },
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
    },

    data() {
        return {
            isLoading: false,
            setupLink: SETUP_LINK.APPLE_PAY,
            applePayFiles: {
                applePayDomainMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-domain',
                    accept: '.txt',
                    preview: null,
                },
                applePayKeyMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-key',
                    accept: '.key',
                    preview: null,
                },
                applePayPemMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-pem',
                    accept: '.pem',
                    preview: null,
                },
            },
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            this.loadMediaPreviews().finally(() => {
                this.isLoading = false;
            });
        },

        /**
         * Load the media ID from the Shopware checkout configuration
         * Then, Load the media entity from the media ID
         * And map the media entity to the preview property of the applePayFiles object.
         */
        loadMediaPreviews() {
            if (!this.checkoutConfigs) {
                return;
            }

            const applePayConfigs = [
                'applePayDomainMediaId',
                'applePayKeyMediaId',
                'applePayPemMediaId',
            ];

            return Promise.all(
                Object.keys(this.checkoutConfigs).map((key) => {
                    const applePaySuffixField = applePayConfigs.find((config) =>
                        key.includes(config),
                    );

                    if (!applePaySuffixField) {
                        return Promise.resolve();
                    }

                    const mediaId = this.checkoutConfigs[key];

                    return this.checkoutMediaService
                        .getSystemMedia(mediaId)
                        .then((media) => {
                            // We have to get private media from media service
                            this.setApplePayFilesPreview(
                                applePaySuffixField,
                                media,
                            );
                        });
                }),
            );
        },

        onInputChange(field, value) {
            this.setCheckoutConfigs(field, value);
        },

        getCheckoutConfigValue(field) {
            return this.checkoutConfigs[getCheckoutConfig(field)];
        },

        /**
         * Set preview property for applePayFiles
         */
        setApplePayFilesPreview(applePaySuffixField, media) {
            if (!this.applePayFiles.hasOwnProperty(applePaySuffixField)) {
                return;
            }

            this.applePayFiles[applePaySuffixField]['preview'] = media;
        },

        setCheckoutConfigs(applePaySuffixField, data) {
            this.$emit('set-checkout-config', applePaySuffixField, data);
        },

        async uploadFinish(applePaySuffixField, { targetId }) {
            this.isLoading = true;

            try {
                // We have to get the upload media from the repository
                let media = await this.mediaRepository.get(
                    targetId,
                    Context.api,
                );

                // We update the media id in the checkout config with private media storage
                await this.updatePrivateMedia(media);

                this.setApplePayFilesPreview(applePaySuffixField, media);
                this.setCheckoutConfigs(applePaySuffixField, media.id);

                this.$emit('save-system-config');
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.isLoading = false;
        },

        async onRemoveMedia(applePaySuffixField) {
            if (!this.acl.can('payment.deleter')) {
                this.createNotificationError({
                    message: this.$tc('checkout-payments.general.permissionDeny'),
                });
                return;
            }

            this.isLoading = true;

            const mediaId = this.getCheckoutConfigValue(applePaySuffixField);

            try {
                await this.removeSystemMedia(mediaId);

                // After removing the media, we need to update the checkout configs
                this.setCheckoutConfigs(applePaySuffixField, null);
                this.setApplePayFilesPreview(applePaySuffixField, null);
                this.$emit('save-system-config');
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.isLoading = false;
        },

        updatePrivateMedia(mediaItem) {
            mediaItem.private = true;
            this.mediaRepository.save(mediaItem, Context.api);
        },

        removeSystemMedia(mediaId) {
            this.checkoutMediaService.removeSystemMedia(mediaId);
        },

        onMediaDropped(checkoutSuffixField, dropItem) {
            this.uploadFinish(checkoutSuffixField, { targetId: dropItem.id });
        },
    },
});
