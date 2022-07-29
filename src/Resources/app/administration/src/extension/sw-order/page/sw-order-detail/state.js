import { CHECKOUT_STATUS } from '../../../../constant/settings';

export default {
    namespaced: true,

    state() {
        return {
            checkoutComPayment: null,
            checkoutComOrder: null,
        };
    },

    getters: {
        isAuthorizedPayment(state) {
            if (!state.checkoutComPayment) {
                return false;
            }

            return state.checkoutComPayment.status === CHECKOUT_STATUS.AUTHORIZED;
        },

        isCapturedPayment(state) {
            if (!state.checkoutComPayment) {
                return false;
            }

            return state.checkoutComPayment.status === CHECKOUT_STATUS.CAPTURED;
        },

        isPartialRefundedPayment(state) {
            if (!state.checkoutComPayment) {
                return false;
            }

            return state.checkoutComPayment.status === CHECKOUT_STATUS.PARTIAL_REFUNDED;
        },

        isFullRefundedPayment(state) {
            if (!state.checkoutComPayment) {
                return false;
            }

            return state.checkoutComPayment.status === CHECKOUT_STATUS.REFUNDED;
        },

        orderPaymentMethod(state) {
            return state.checkoutComOrder?.transactions?.last()?.paymentMethod;
        },

        paymentMethodCheckoutConfig(state, getters) {
            return getters.orderPaymentMethod?.customFields?.checkoutConfig;
        },
    },

    mutations: {
        setCheckoutComPayment(state, checkoutComPayment) {
            state.checkoutComPayment = checkoutComPayment;
        },
        setOrder(state, order) {
            state.checkoutComOrder = order;
        },
    },
};
