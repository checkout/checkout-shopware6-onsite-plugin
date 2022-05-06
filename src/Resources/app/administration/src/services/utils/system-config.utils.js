import { CHECKOUT_DOMAIN } from '../../constant/settings';

/**
 * Get checkout.com config setting field
 *
 * @param field{string}
 * @returns {string}
 */
export const getCheckoutConfig = (field) => {
    return `${CHECKOUT_DOMAIN}.${field}`;
};

export default {
    getCheckoutConfig,
};
