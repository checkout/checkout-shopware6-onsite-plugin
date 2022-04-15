/**
 * @see CheckoutCom\Shopware6\Helper\RequestUtil::DATA_BAG_KEY
 */
export const DATA_BAG_KEY = 'checkoutComDetails';

export const APPLE_PAY = {
    APPLE_PAY_VERSION: 3,
    MERCHANT_CAPABILITIES: ['supports3DS'],
    SUPPORTED_NETWORKS: [
        'visa',
        'masterCard',
        'amex',
        'discover',
    ],
}

export const GOOGLE_PAY = {
    PAYMENT_GATEWAY: 'PAYMENT_GATEWAY',
    GATEWAY: 'checkoutltd',
    API_VERSION: 2,
    API_VERSION_MINOR: 0,
    TOTAL_PRICE_STATUS: 'FINAL',
    BASE_CARD_PAYMENT_METHOD: {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: [
                'PAN_ONLY',
                'CRYPTOGRAM_3DS',
            ],
            allowedCardNetworks: [
                'AMEX',
                'DISCOVER',
                'INTERAC',
                'JCB',
                'MASTERCARD',
                'MIR',
                'VISA',
            ],
        },
    },
};
