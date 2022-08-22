import template from './checkout-com-flow-capture-payment-modal.html.twig';

const {
    Component,
} = Shopware;

Component.register('checkout-com-flow-capture-payment-modal', {
    template,

    props: {
        sequence: {
            type: Object,
            required: true,
        },
    },

    methods: {
        onClose() {
            this.$emit('modal-close');
        },

        onAddAction() {
            this.$emit('process-finish', this.sequence);
        },
    },
});
