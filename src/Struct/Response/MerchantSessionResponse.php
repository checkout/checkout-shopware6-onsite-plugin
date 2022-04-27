<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\Response;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class MerchantSessionResponse extends StoreApiResponse
{
    /**
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(?array $merchantSession = null)
    {
        parent::__construct(new ArrayStruct([
            'success' => !empty($merchantSession),
            'merchant' => $merchantSession,
        ]));
    }

    public function getMerchantSession(): ArrayStruct
    {
        return $this->object;
    }
}
