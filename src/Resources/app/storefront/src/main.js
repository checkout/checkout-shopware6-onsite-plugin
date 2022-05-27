import 'regenerator-runtime';

import CheckoutComApplePayConfirm from './checkout-com/plugins/apple/apple-pay-confirm.plugin';
import CheckoutComApplePayDirect from './checkout-com/plugins/apple/apple-pay-direct.plugin';
import CheckoutComApplePayDisplay from './checkout-com/plugins/apple/apple-pay-display.plugin';

import CheckoutComGooglePayConfirm from './checkout-com/plugins/google/google-pay-confirm.plugin';
import CheckoutComGooglePayDirect from './checkout-com/plugins/google/google-pay-direct.plugin';
import CheckoutComGooglePayDisplay from './checkout-com/plugins/google/google-pay-display.plugin';

import CheckoutComCardPayment from './checkout-com/plugins/card-payment.plugin';
import CheckoutComSepa from './checkout-com/plugins/sepa.plugin';
import CheckoutComIdeal from './checkout-com/plugins/ideal.plugin';

const PluginManager = window.PluginManager;

// Action plugins
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComApplePayConfirm', CheckoutComApplePayConfirm, '[data-checkout-com-apple-pay-confirm]');
PluginManager.register('CheckoutComGooglePayConfirm', CheckoutComGooglePayConfirm, '[data-checkout-com-google-pay-confirm]');

PluginManager.register('CheckoutComApplePayDirect', CheckoutComApplePayDirect, '[data-checkout-com-apple-pay-direct]');
PluginManager.register('CheckoutComGooglePayDirect', CheckoutComGooglePayDirect, '[data-checkout-com-google-pay-direct]');

// display the payment methods
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComApplePayDisplay', CheckoutComApplePayDisplay, '[data-checkout-com-apple-pay-display]');
PluginManager.register('CheckoutComGooglePayDisplay', CheckoutComGooglePayDisplay, '[data-checkout-com-google-pay-display]');

// Show card payments components in the checkout
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComCardPayment', CheckoutComCardPayment, '[data-checkout-com-card-payment]');
PluginManager.register('CheckoutComSepa', CheckoutComSepa, '[data-checkout-com-sepa]');
PluginManager.register('CheckoutComIdeal', CheckoutComIdeal, '[data-checkout-com-ideal]');
