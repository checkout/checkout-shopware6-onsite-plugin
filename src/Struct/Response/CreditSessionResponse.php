<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Response;

use Cko\Shopware6\Struct\PaymentMethod\Klarna\CreditSessionStruct;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_klarna_credit_session_response"
 * )
 */
class CreditSessionResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="boolean",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="creditSession",
     *      description="Credit session response data",
     *      ref="#/components/schemas/checkout_com_klarna_credit_session"
     * )
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(bool $success, ?CreditSessionStruct $creditSession = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => $success,
            'creditSession' => empty($creditSession) ? null : $creditSession->toApiJson(),
        ], 'credit_session'));
    }

    public function getCreditSession(): ?CreditSessionStruct
    {
        return $this->object->get('creditSession');
    }
}
