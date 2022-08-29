<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Controller;

use Checkout\CheckoutApi;
use Checkout\CheckoutApiException;
use Checkout\CheckoutSdkBuilder;
use Checkout\CheckoutStaticKeysSdkBuilder;
use Checkout\Environment;
use Checkout\HttpMetadata;
use Checkout\Previous\CheckoutApi as CheckoutPreviousApi;
use Checkout\Previous\CheckoutStaticKeysPreviousSdkBuilder;
use Checkout\Sources\Previous\SepaSourceRequest;
use Checkout\Tokens\CardTokenRequest;
use CheckoutCom\Shopware6\Struct\SystemConfig\SettingStruct;
use Exception;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * This controller handles all tasks related to plugin configuration.
 *
 * @RouteScope(scopes={"api"})
 */
class ConfigController extends AbstractController
{
    // Valid error response for authenticate request
    private const KEYS_VALID_RESPONSE = [
        Response::HTTP_UNPROCESSABLE_ENTITY,
        Response::HTTP_BAD_REQUEST,
    ];

    private DataValidator $dataValidator;

    public function __construct(DataValidator $dataValidator)
    {
        $this->dataValidator = $dataValidator;
    }

    /**
     * Api for test api keys
     *
     * @Route("/api/_action/checkout-com/config/test-api-key", name="api.action.checkout-com.config.test-api-key", methods={"POST"})
     */
    public function testApiKeys(RequestDataBag $request): JsonResponse
    {
        $dataValidation = $this->getTestApiKeysValidation();
        $data = $request->all();
        $this->dataValidator->validate($data, $dataValidation);

        $secretKey = $request->get('secretKey') ?: ' ';
        $publicKey = $request->get('publicKey') ?: ' ';
        $isSandbox = $request->getBoolean('isSandbox', true);
        $accountType = $request->get('accountType', SettingStruct::ACCOUNT_TYPE_ABC);

        $builder = $this->getCheckoutBuilder($accountType);
        $builder->environment($isSandbox ? Environment::sandbox() : Environment::production());
        $builder->publicKey($publicKey);
        $builder->secretKey($secretKey);

        try {
            $defaultApi = $builder->build();
        } catch (Exception $e) {
            return $this->getResponseInvalidKey($e);
        }

        // We call the api to test the keys if they have a valid pattern
        $secretKeyValid = $this->checkSecretKeyValidByCallApi($accountType, $defaultApi);

        // We call the api to test the keys if they have a valid pattern
        $publicKeyValid = $this->checkPublicKeyValidByCallApi($defaultApi);

        return new JsonResponse([
            [
                'isSecretKey' => true,
                'valid' => $secretKeyValid,
            ],
            [
                'isSecretKey' => false,
                'valid' => $publicKeyValid,
            ],
        ]);
    }

    private function getTestApiKeysValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('checkout_com.test_api_keys');

        $definition->add('secretKey', new Optional(new Type('string')));
        $definition->add('publicKey', new Optional(new Type('string')));
        $definition->add('isSandbox', new Optional(new Type('boolean')));
        $definition->add('accountType', new Optional(new Type('string')), new Choice([
            SettingStruct::ACCOUNT_TYPE_ABC,
            SettingStruct::ACCOUNT_TYPE_NAS,
        ]));

        return $definition;
    }

    /**
     * @return CheckoutStaticKeysSdkBuilder|CheckoutStaticKeysPreviousSdkBuilder
     */
    private function getCheckoutBuilder(string $accountType)
    {
        if ($accountType === SettingStruct::ACCOUNT_TYPE_ABC) {
            return (new CheckoutSdkBuilder())->previous()->staticKeys();
        }

        return (new CheckoutSdkBuilder())->staticKeys();
    }

    private function getResponseInvalidKey(Exception $e): JsonResponse
    {
        $publicKeyValid = true;
        $secretKeyValid = true;

        if (str_contains($e->getMessage(), 'public')) {
            $publicKeyValid = false;
        } elseif (str_contains($e->getMessage(), 'secret')) {
            $secretKeyValid = false;
        } else {
            $publicKeyValid = false;
            $secretKeyValid = false;
        }

        return new JsonResponse([
            [
                'isSecretKey' => true,
                'valid' => $secretKeyValid,
            ],
            [
                'isSecretKey' => false,
                'valid' => $publicKeyValid,
            ],
        ]);
    }

    /**
     * We use this method to check if the secret key is valid by calling the API without data
     *
     * @param CheckoutApi|CheckoutPreviousApi $apiClient
     */
    private function checkSecretKeyValidByCallApi(string $accountType, $apiClient): bool
    {
        try {
            if ($accountType === SettingStruct::ACCOUNT_TYPE_ABC) {
                $apiClient->getSourcesClient()->createSepaSource(new SepaSourceRequest());
            } else {
                $apiClient->getWorkflowsClient()->getWorkflows();
            }

            return true;
        } catch (CheckoutApiException $exception) {
            return $this->checkHttpMetaData($exception);
        }
    }

    /**
     * We use this method to check if the public key is valid by calling the API without data
     *
     * @param CheckoutApi|CheckoutPreviousApi $apiClient
     */
    private function checkPublicKeyValidByCallApi($apiClient): bool
    {
        try {
            $apiClient->getTokensClient()->requestCardToken(new CardTokenRequest());

            return true;
        } catch (CheckoutApiException $exception) {
            return $this->checkHttpMetaData($exception);
        }
    }

    private function checkHttpMetaData(CheckoutApiException $exception): bool
    {
        $httpMetaData = $exception->http_metadata;
        if (!$httpMetaData instanceof HttpMetadata) {
            return false;
        }

        // We check if the http_status_code response status is valid
        return \in_array($httpMetaData->getStatusCode(), self::KEYS_VALID_RESPONSE, true);
    }
}
