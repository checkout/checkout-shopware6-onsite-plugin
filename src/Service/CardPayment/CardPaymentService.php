<?php declare(strict_types=1);

namespace Cko\Shopware6\Service\CardPayment;

use Checkout\CheckoutApiException;
use Checkout\Tokens\CardTokenRequest;
use Cko\Shopware6\Factory\SettingsFactory;
use Cko\Shopware6\Service\CheckoutApi\CheckoutTokenService;
use Cko\Shopware6\Struct\CheckoutApi\Resources\Token;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class CardPaymentService extends AbstractCardPaymentService
{
    protected LoggerInterface $logger;

    protected SettingsFactory $settingsFactory;

    protected CheckoutTokenService $checkoutTokenService;

    public function __construct(
        LoggerInterface $logger,
        SettingsFactory $settingsFactory,
        CheckoutTokenService $checkoutTokenService
    ) {
        $this->logger = $logger;
        $this->settingsFactory = $settingsFactory;
        $this->checkoutTokenService = $checkoutTokenService;
    }

    public function getDecorated(): AbstractCardPaymentService
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @throws CheckoutApiException
     */
    public function createToken(RequestDataBag $data, SalesChannelContext $context): Token
    {
        $request = new CardTokenRequest();
        $request->name = $data->get('name');
        $request->number = $data->get('number');
        $request->expiry_month = $data->get('expiryMonth');
        $request->expiry_year = $data->get('expiryYear');
        $request->cvv = $data->get('cvv');

        try {
            return $this->checkoutTokenService->requestCardToken($request, $context->getSalesChannelId());
        } catch (CheckoutApiException $e) {
            if (empty($e->error_details)) {
                throw $e;
            }

            $errorCodes = $e->error_details['error_codes'] ?? [];
            if (!\is_array($errorCodes) || empty($errorCodes)) {
                throw $e;
            }

            $violations = new ConstraintViolationList();
            foreach ($errorCodes as $errorCode) {
                $violations->add(
                    new ConstraintViolation(
                        $errorCode,
                        '',
                        [],
                        null,
                        '',
                        null
                    )
                );
            }

            throw new ConstraintViolationException($violations, $data->all());
        }
    }
}
