<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\Response;

use Cko\Shopware6\Struct\Extension\PublicConfigStruct;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @OA\Schema(
 *     schema="checkout_com_config_response"
 * )
 */
class ConfigResponse extends StoreApiResponse
{
    /**
     * @OA\Property(
     *      property="success",
     *      type="string",
     *      description="Success flag"
     *  ),
     *
     * @OA\Property(
     *      property="configs",
     *      ref="#/components/schemas/checkout_com_public_config"
     *  ),
     *
     * @var ArrayStruct
     */
    protected $object;

    public function __construct(PublicConfigStruct $publicConfigStruct)
    {
        parent::__construct(new ArrayStruct([
            'success' => true,
            'configs' => $publicConfigStruct->jsonSerialize(),
        ], 'config_response'));
    }

    public function getConfig(): PublicConfigStruct
    {
        return $this->object->get('configs');
    }
}
