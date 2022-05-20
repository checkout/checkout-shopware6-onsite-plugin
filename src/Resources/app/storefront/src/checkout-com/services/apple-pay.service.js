import StoreApiClient from 'src/service/store-api-client.service';

/**
 * This class is responsible for handling Apple Pay payments.
 */
export default class ApplePayService {
    constructor() {
        this.storeApiClient = new StoreApiClient();
    }

    abortApiClient() {
        return this.storeApiClient.abort();
    }

    /**
     * This will be called when the Apple Pay session is starting to validate the merchant.
     */
    validateMerchant(validateMerchantEndpoint, validationURL, callback) {
        const data = JSON.stringify({
            validationURL,
        });

        this.storeApiClient.post(validateMerchantEndpoint, data, (result) => {
            if (!result) {
                callback(null);

                return;
            }

            const { merchant } = JSON.parse(result);

            // Need to commit the merchant to the Apple Pay session to let it know that we are ready to process the payment
            callback(merchant);
        });
    }
}
