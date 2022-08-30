import template from './checkout-com-inherit-media-upload.html.twig';
import './checkout-com-inherit-media-upload.scss';

const {
    Component,
    Context,
    Mixin,
} = Shopware;

Component.register('checkout-com-inherit-media-upload', {
    template,

    inject: [
        'repositoryFactory',
        'checkoutMediaService',
    ],

    mixins: [Mixin.getByName('notification')],

    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        canRemove: {
            type: Boolean,
            required: false,
            default: false,
        },
        label: {
            type: String,
            required: false,
        },
        hasParent: {
            type: Boolean,
            required: false,
            default: false,
        },
        value: {
            required: true,
        },
        inheritedValue: {
            required: false,
        },
        fileAccept: {
            type: String,
            required: false,
        },
        uploadTag: {
            type: String,
            required: true,
        },
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
        valueId() {
            return this.value ? this.value.id : null;
        },
        inheritedValueId() {
            return this.inheritedValue ? this.inheritedValue.id : null;
        },
    },

    methods: {
        onChangeValue(value) {
            this.$emit('input', value);
        },

        checkInheritance(value) {
            return value === null;
        },

        async onRestoreInheritance(restoreInheritanceCb) {
            await this.onRemoveMedia(restoreInheritanceCb);
        },

        restoreInheritance() {
            return null;
        },

        onRemoveInheritance(inheritanceUpdateCb) {
            inheritanceUpdateCb('');
        },

        /**
         * Update file finished event
         *
         * @param {string} targetId
         * @param {Function} inheritanceUpdateCb
         */
        async uploadFinish({ targetId }, inheritanceUpdateCb) {
            this.emitLoading(true);

            try {
                // We have to get the upload media from the repository
                const media = await this.mediaRepository.get(targetId, Context.api);

                // We update the media id in the checkout config with private media storage
                await this.updatePrivateMedia(media);

                inheritanceUpdateCb(media);
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            }

            this.emitLoading(false);
        },

        /**
         * Remove media finished event
         *
         * @param {Function} inheritanceUpdateCb
         */
        async onRemoveMedia(inheritanceUpdateCb) {
            if (!this.canRemove) {
                this.createNotificationError({
                    message: this.$tc('checkout-payments.general.permissionDeny'),
                });
                return;
            }

            if (!this.valueId) {
                inheritanceUpdateCb('');
                return;
            }

            // If the mediaId is the same as the parent mediaId, and it has parented
            // only remove the files preview
            if (this.valueId === this.inheritedValueId && this.hasParent) {
                inheritanceUpdateCb('');
                return;
            }

            this.emitLoading(true);
            try {
                await this.removeSystemMedia(this.valueId);

                // After removing the media, we need to update the checkout configs
                inheritanceUpdateCb('');
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

        /**
         * Media dropped event
         *
         * @param {string} id
         * @param {Function} inheritanceUpdateCb
         */
        onMediaDropped({ id }, inheritanceUpdateCb) {
            this.uploadFinish({ targetId: id }, inheritanceUpdateCb);
        },
    },
});
