import 'regenerator-runtime';

import CheckoutComCreditCardComponents from './checkout-com/plugins/credit-card-components.plugin';

const PluginManager = window.PluginManager;

// Show credit card components in the checkout
// -----------------------------------------------------------------------------
PluginManager.register('CheckoutComCreditCardComponents', CheckoutComCreditCardComponents, '[data-checkout-com-credit-card-components]');
