export const DASHBOARD_LINK = {
    SANDBOX: 'https://sandbox.checkout.com/settings/channels',
    LIVE: 'https://hub.checkout.com/settings/channels',
};

export const CHECKOUT_DOMAIN = 'CheckoutCom.config';

export const SETUP_LINK = {
    APPLE_PAY: 'https://www.checkout.com/docs/payments/payment-methods/wallets/apple-pay/set-up-apple-pay',
};

/**
 * @see Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentHandlerIdentifierSubscriber
 * @description It will be handler_checkoutcom_{%s} with %s is lower string of classname
 */
export const PAYMENT_METHOD_IDENTIFIER = {
    APPLE_PAY: 'handler_checkoutcom_applepayhandler',
};
