import template from './checkout-com-refund-modal.html.twig';
import './checkout-com-refund-modal.scss';
import { LINE_ITEM_PAYLOAD } from '../../constant/settings';

const {
    Component,
    Mixin,
    Utils,
} = Shopware;
const { isNumber } = Utils.types;

Component.register('checkout-com-refund-modal', {
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
        orderLineItems: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            refundableLineItems: null,
            isLoading: false,
            isConfirmRefund: false,
        };
    },

    computed: {
        columns() {
            return [
                {
                    label: this.$tc('checkout-payments.order.refund.modal.nameColumn'),
                    property: 'label',
                    align: 'left',
                },
                {
                    label: this.$tc('checkout-payments.order.refund.modal.priceColumn'),
                    property: 'unitPrice',
                    width: '170px',
                    align: 'right',
                },
                {
                    label: this.$tc('checkout-payments.order.refund.modal.quantityColumn'),
                    property: 'quantity',
                    width: '170px',
                    align: 'right',
                },
                {
                    label: this.$tc('checkout-payments.order.refund.modal.refundedColumn'),
                    property: 'refundedQuantity',
                    width: '170px',
                    align: 'right',
                },
                {
                    label: this.$tc('checkout-payments.order.refund.modal.returnQuantityColumn'),
                    property: 'returnQuantity',
                    width: '250px',
                    align: 'center',
                },
            ];
        },

        selections() {
            if (!this.refundableLineItems) {
                return [];
            }

            return this.refundableLineItems.filter((item) => item.refundSelected);
        },

        refundableSelections() {
            return this.selections.filter((item) => !this.isFullyRefunded(item));
        },

        canRefund() {
            if (this.isLoading) {
                return false;
            }

            if (!this.isConfirmRefund) {
                return false;
            }

            if (this.refundableSelections.length === 0) {
                return false;
            }

            return !this.refundableSelections.some((item) => item.returnQuantityError);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.setRefundableLineItems();
        },

        setRefundableLineItems() {
            const refundLineItems = [];
            const refundableLineItems = [];

            // Foreach line items of order to separate 2 types:
            // 1. refundedLineItems = The order line items has property `LINE_ITEM_PAYLOAD`(refunded line items)
            // 2. refundableLineItems = The refundable order line items that can be shown in the refund manager modal
            this.orderLineItems.forEach((orderLineItem) => {
                if (!orderLineItem || !orderLineItem.payload || !orderLineItem.payload.hasOwnProperty(LINE_ITEM_PAYLOAD)) {
                    refundableLineItems.push({ ...orderLineItem });
                } else {
                    refundLineItems.push({ ...orderLineItem });
                }
            });

            refundableLineItems.forEach((item) => {
                // Get all refunded line items of the current order
                const mappingRefundLineItems = refundLineItems.filter(
                    (refundItem) => refundItem.payload[LINE_ITEM_PAYLOAD].refundLineItemId === item.id,
                );

                // Calculate the refunded quantity of the line item
                item.refundedQuantity = mappingRefundLineItems.reduce((
                    totalRefunded,
                    itemRefund,
                ) => totalRefunded + itemRefund.quantity, 0);

                item.remainingQuantity = item.quantity - item.refundedQuantity;
            });

            this.refundableLineItems = refundableLineItems;
        },

        closeModal() {
            this.$emit('close-modal');
        },

        onSelectItemChange(selections, item, selected) {
            this.$set(item, 'refundSelected', selected);
            this.validateReturnQuantityError(item);
        },

        returnQuantityOptions(item) {
            const options = [];
            for (let i = 1; i <= item.remainingQuantity; i += 1) {
                options.push({
                    value: i,
                    label: i.toString(),
                });
            }

            return options;
        },

        isFullyRefunded(item) {
            return item.remainingQuantity === 0;
        },

        returnQuantityPlaceholder(item) {
            if (this.isFullyRefunded(item)) {
                return this.$tc('checkout-payments.order.refund.modal.returnQuantityFullyRefundedPlaceholder');
            }

            return this.$tc('checkout-payments.order.refund.modal.returnQuantityPlaceholder');
        },

        validateReturnQuantityError(item) {
            const error = item.refundSelected && !isNumber(item.returnQuantity) && !this.isFullyRefunded(item);
            this.$set(item, 'returnQuantityError', error);
        },

        async onRefund() {
            try {
                this.isLoading = true;
                const refundItems = this.refundableSelections.map((item) => ({
                    id: item.id,
                    returnQuantity: item.returnQuantity,
                }));

                await this.checkoutOrderService.refundPayment(this.order.id, refundItems);

                this.createNotificationSuccess({
                    message: this.$tc('checkout-payments.order.refund.message.refundExecuted'),
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

        onSelectItem(item) {
            this.$refs.refundGrid.selectItem(true, item);
        },
    },
});
