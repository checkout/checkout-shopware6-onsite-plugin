export const DASHBOARD_LINK = {
    SANDBOX: 'https://sandbox.checkout.com/settings/channels',
    LIVE: 'https://hub.checkout.com/settings/channels',
};

export const ORDER_CHECKOUT_COM_CUSTOM_FIELDS = 'checkoutComPayments';
export const LINE_ITEM_PAYLOAD = 'checkoutComPayments';
export const CHECKOUT_DOMAIN = 'CheckoutCom.config';
export const CHECKOUT_DOMAIN_PAYMENT_METHOD = `${CHECKOUT_DOMAIN}.paymentMethod`;

export const FLOW_ACTION = {
    CAPTURE_PAYMENT: 'action.checkout_com.capture_payment',
    FULL_REFUND: 'action.checkout_com.full_refund',
};

export const SETUP_LINK = {
    APPLE_PAY: 'https://www.checkout.com/docs/payments/payment-methods/wallets/apple-pay/set-up-apple-pay',
};

export const ACCOUNT_TYPE = {
    ABC: 'abc',
    NAS: 'nas',
};

/**
 * @see CheckoutCom\Shopware6\Handler\PaymentHandler::getPaymentMethodType()
 */
export const PAYMENT_METHOD_TYPE = {
    CARD_PAYMENT: 'card',
    APPLE_PAY: 'applepay',
    GOOGLE_PAY: 'googlepay',
};

export const CHECKOUT_STATUS = {
    AUTHORIZED: 'Authorized',
    CAPTURED: 'Captured',
    PARTIAL_REFUNDED: 'Partially Refunded',
    REFUNDED: 'Refunded',
};
