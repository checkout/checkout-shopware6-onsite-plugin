import template from './checkout-com-payment-method-apple-pay.html.twig';
import { SETUP_LINK } from '../../../../constant/settings';
import './checkout-com-payment-method-apple-pay.scss';

const {
    Component,
    Context,
    Mixin,
} = Shopware;

const applePayConfigs = [
    'domainMediaId',
    'keyMediaId',
    'pemMediaId',
];

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
        salesChannelId: {
            type: String,
            required: false,
        },
        paymentMethodConfigs: {
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
                domainMediaId: {
                    tag: 'checkout-com-payment-method-apple-pay-domain',
                    accept: '.txt',
                    preview: null,
                },
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
        };
    },

    watch: {
        salesChannelId() {
            // Reset the files when the sales channel is changed
            applePayConfigs.forEach((applePaySuffixField) => {
                this.setApplePayFilesPreview(applePaySuffixField, null);
            });

            this.loadMediaPreviews();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadMediaPreviews();
        },

        /**
         * Load the media ID from the Shopware checkout configuration
         * Then, Load the media entity from the media ID
         * And map the media entity to the preview property of the applePayFiles object.
         */
        async loadMediaPreviews() {
            if (!this.paymentMethodConfigs) {
                return Promise.resolve();
            }

            try {
                this.emitLoading(true);

                return await Promise.all(
                    Object.keys(this.paymentMethodConfigs).map((propertyKey) => {
                        if (!applePayConfigs.includes(propertyKey)) {
                            return Promise.resolve();
                        }

                        const mediaId = this.paymentMethodConfigs[propertyKey];

                        if (!mediaId) {
                            return Promise.resolve();
                        }

                        return this.checkoutMediaService
                            .getSystemMedia(mediaId)
                            .then((media) => {
                                // We have to get private media from media service
                                this.setApplePayFilesPreview(
                                    propertyKey,
                                    media,
                                );
                            });
                    }),
                );
            } finally {
                this.emitLoading(false);
            }
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

            this.applePayFiles[propertyKey].preview = media;
        },

        setCheckoutConfigs(propertyKey, data) {
            this.$emit('set-checkout-payment-configs', propertyKey, data);
        },

        async uploadFinish(propertyKey, { targetId }) {
            this.emitLoading(true);

            try {
                // We have to get the upload media from the repository
                const media = await this.mediaRepository.get(targetId, Context.api);

                // We update the media id in the checkout config with private media storage
                await this.updatePrivateMedia(media);

                this.setApplePayFilesPreview(propertyKey, media);
                this.setCheckoutConfigs(propertyKey, media.id);

                this.$emit('save-system-config');
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.emitLoading(false);
        },

        async onRemoveMedia(propertyKey) {
            if (!this.acl.can('payment.deleter')) {
                this.createNotificationError({
                    message: this.$tc('checkout-payments.general.permissionDeny'),
                });
                return;
            }

            const mediaId = this.paymentMethodConfigs[propertyKey];
            if (!mediaId) {
                // The mediaId in the checkout config is not set, so we also remove the media preview
                this.setApplePayFilesPreview(propertyKey, null);
                return;
            }

            this.emitLoading(true);
            try {
                await this.removeSystemMedia(mediaId);

                // After removing the media, we need to update the checkout configs
                this.setCheckoutConfigs(propertyKey, null);
                this.setApplePayFilesPreview(propertyKey, null);
                this.$emit('save-system-config');
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.emitLoading(false);
        },

        emitLoading(isLoading) {
            this.$emit('set-loading', isLoading);
        },

        updatePrivateMedia(mediaItem) {
            mediaItem.private = true;
            this.mediaRepository.save(mediaItem, Context.api);
        },

        removeSystemMedia(mediaId) {
            this.checkoutMediaService.removeSystemMedia(mediaId);
        },

        onMediaDropped(propertyKey, dropItem) {
            this.uploadFinish(propertyKey, { targetId: dropItem.id });
        },
    },
});
