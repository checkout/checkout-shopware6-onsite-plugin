import CheckoutConfigService from './api/checkout-config.service';
import CheckoutMediaService from './api/checkout-media.service';

Shopware.Service().register('checkoutConfigService', (container) => {
    return new CheckoutConfigService(
        Shopware.Application.getContainer('init').httpClient,
        container.loginService,
    );
});

Shopware.Service().register('checkoutMediaService', (container) => {
    return new CheckoutMediaService(
        Shopware.Application.getContainer('init').httpClient,
        container.loginService,
    );
});
