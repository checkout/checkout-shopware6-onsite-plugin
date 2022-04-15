<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_direct_process_response"
 * )
 */
class DirectProcessResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="boolean",
     *      description="Success flag"
     *  ),
     * @OA\Property(
     *      property="redirectUrl",
     *      type="string",
     *      description="Redirect Url"
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(bool $success, ?string $redirectUrl = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => $success,
            'redirectUrl' => $redirectUrl,
        ], 'direct_process'));
    }

    public function getRedirectUrl(): string
    {
        return $this->object->get('redirectUrl');
    }
}
