import template from './checkout-com-fix-price-difference.html.twig';

const {
    Component,
    Mixin,
} = Shopware;

Component.register('checkout-com-fix-price-difference', {
    template,

    mixins: [Mixin.getByName('notification')],

    inject: [
        'checkoutOrderService',
    ],

    props: {
        order: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
        };
    },

    methods: {
        closeModal() {
            this.$emit('close-modal');
        },

        async onSubmit() {
            try {
                this.isLoading = true;

                await this.checkoutOrderService.fixPriceDifferencePayment(this.order.id);

                this.createNotificationSuccess({
                    message: this.$tc('checkout-payments.order.fixPriceDifference.message.fixPriceDifferenceExecuted'),
                });
                this.closeModal();

                this.$nextTick(() => {
                    this.$root.$emit('checkout-order-update');
                });
            } catch {
                this.createNotificationError({
                    message: this.$tc('global.notification.unspecifiedSaveErrorMessage'),
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
});
