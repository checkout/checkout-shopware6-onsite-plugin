import CheckoutConfigService from '../../../src/services/api/checkout-config.service';

describe('src/services/api/checkout-config.service', () => {
    const mockAxios = null;
    const mockLoginService = null;
    const service = new CheckoutConfigService(mockAxios, mockLoginService);

    it('should exist', async () => {
        expect(service).toBeTruthy();
    });
});
