import 'regenerator-runtime';

import CheckoutComApplePayConfirm from './checkout-com/plugins/apple-pay-confirm.plugin';
import CheckoutComApplePayDisplay from './checkout-com/plugins/apple-pay-display.plugin';
import CheckoutComCreditCard from './checkout-com/plugins/credit-card.plugin';

const PluginManager = window.PluginManager;

// Action plugins
// -----------------------------------------------------------------------------
PluginManager.register(
    'CheckoutComApplePayConfirm',
    CheckoutComApplePayConfirm,
    '[data-checkout-com-apple-pay-confirm]',
);

// display the payment methods
// -----------------------------------------------------------------------------
PluginManager.register(
    'CheckoutComApplePayDisplay',
    CheckoutComApplePayDisplay,
    '[data-checkout-com-apple-pay-display]',
);

// Show credit card components in the checkout
// -----------------------------------------------------------------------------
PluginManager.register(
    'CheckoutComCreditCard',
    CheckoutComCreditCard,
    '[data-checkout-com-credit-card]',
);
