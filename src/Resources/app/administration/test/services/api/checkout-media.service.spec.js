import CheckoutMediaService from '../../../src/services/api/checkout-media.service';

describe('src/services/api/checkout-media.service', () => {
    const mockAxios = null;
    const mockLoginService = null;
    const service = new CheckoutMediaService(mockAxios, mockLoginService);

    it('should exist', async () => {
        expect(service).toBeTruthy();
    });
});
