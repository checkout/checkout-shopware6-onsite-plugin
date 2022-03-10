import CheckoutConfigService from "./api/checkout-config.service";

Shopware.Service().register("checkoutConfigService", (container) => {
    return new CheckoutConfigService(
        Shopware.Application.getContainer("init").httpClient,
        container.loginService
    );
});
