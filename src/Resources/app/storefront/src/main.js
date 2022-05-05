import 'regenerator-runtime';

import CheckoutComApplePayConfirm from './checkout-com/plugins/apple/apple-pay-confirm.plugin';
import CheckoutComApplePayDisplay from './checkout-com/plugins/apple/apple-pay-display.plugin';

import CheckoutComGooglePayConfirm from './checkout-com/plugins/google/google-pay-confirm.plugin';
import CheckoutComGooglePayDisplay from './checkout-com/plugins/google/google-pay-display.plugin';

import CheckoutComCreditCard from './checkout-com/plugins/credit-card.plugin';

const PluginManager = window.PluginManager;

// Action plugins
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComApplePayConfirm', CheckoutComApplePayConfirm, '[data-checkout-com-apple-pay-confirm]');
PluginManager.register('CheckoutComGooglePayConfirm', CheckoutComGooglePayConfirm, '[data-checkout-com-google-pay-confirm]');

// display the payment methods
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComApplePayDisplay', CheckoutComApplePayDisplay, '[data-checkout-com-apple-pay-display]');
PluginManager.register('CheckoutComGooglePayDisplay', CheckoutComGooglePayDisplay, '[data-checkout-com-google-pay-display]');

// Show credit card components in the checkout
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComCreditCard', CheckoutComCreditCard, '[data-checkout-com-credit-card]');
