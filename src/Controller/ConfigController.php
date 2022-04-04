<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Controller;

use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutDefaultSdk;
use Checkout\Environment;
use Checkout\Sources\SepaSourceRequest;
use Checkout\StaticKeysCheckoutSdkBuilder;
use Checkout\Tokens\CardTokenRequest;
use Exception;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class ConfigController extends AbstractController
{
    // Valid error response for authenticate request
    private const KEYS_VALID_RESPONSE = [
        Response::HTTP_UNPROCESSABLE_ENTITY,
        Response::HTTP_BAD_REQUEST,
    ];

    /**
     * Api for test api keys
     *
     * @Route("/api/_action/checkout/config/test-api-key", name="api.action.checkout.config.test-api-key", methods={"POST"})
     */
    public function testApiKeys(Request $request): JsonResponse
    {
        $secretKey = $request->get('secretKey');
        $publicKey = $request->get('publicKey');
        $isSandbox = $request->get('isSandbox', true);

        $builder = CheckoutDefaultSdk::staticKeys();
        $builder->setEnvironment($isSandbox ? Environment::sandbox() : Environment::production());

        $secretKeyValid = $this->checkSecretKeyValidPattern($builder, $secretKey);
        $publicKeyValid = $this->checkPublicKeyValidPattern($builder, $publicKey);

        $defaultApi = $builder->build();

        // We call the api to test the keys if they have a valid pattern
        if ($secretKeyValid) {
            $secretKeyValid = $this->checkSecretKeyValidByCallApi($defaultApi);
        }

        // We call the api to test the keys if they have a valid pattern
        if ($publicKeyValid) {
            $publicKeyValid = $this->checkPublicKeyValidByCallApi($defaultApi);
        }

        return new JsonResponse([
            [
                'isSecretKey' => true,
                'key' => $secretKey,
                'valid' => $secretKeyValid,
            ],
            [
                'isSecretKey' => false,
                'key' => $publicKey,
                'valid' => $publicKeyValid,
            ],
        ]);
    }

    /**
     * Check if the secret key has a valid pattern
     */
    private function checkSecretKeyValidPattern(StaticKeysCheckoutSdkBuilder $builder, string $secretKey): bool
    {
        // We have to check it because the PHP SDK maybe doesn't throw an exception
        if (empty($secretKey)) {
            return false;
        }

        try {
            $builder->setSecretKey($secretKey);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Check if the public key has a valid pattern
     */
    private function checkPublicKeyValidPattern(StaticKeysCheckoutSdkBuilder $builder, string $publicKey): bool
    {
        // We have to check it because the PHP SDK maybe doesn't throw an exception
        if (empty($publicKey)) {
            return false;
        }

        try {
            $builder->setPublicKey($publicKey);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * We use this method to check if the secret key is valid by calling the API without data
     */
    private function checkSecretKeyValidByCallApi(CheckoutApi $apiClient): bool
    {
        try {
            $apiClient->getSourcesClient()->createSepaSource(new SepaSourceRequest());
        } catch (CheckoutApiException $exception) {
            // We check if the http_status_code response status is valid
            if (\in_array($exception->http_status_code, self::KEYS_VALID_RESPONSE, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * We use this method to check if the public key is valid by calling the API without data
     */
    private function checkPublicKeyValidByCallApi(CheckoutApi $apiClient): bool
    {
        try {
            $apiClient->getTokensClient()->requestCardToken(new CardTokenRequest());
        } catch (CheckoutApiException $exception) {
            // We check if the http_status_code response status is valid
            if (\in_array($exception->http_status_code, self::KEYS_VALID_RESPONSE, true)) {
                return true;
            }
        }

        return false;
    }
}
