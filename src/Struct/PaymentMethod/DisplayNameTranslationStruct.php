<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Struct;

class DisplayNameTranslationStruct extends Struct
{
    protected string $lang;

    protected string $name;

    public function __construct(string $lang, string $name)
    {
        $this->lang = $lang;
        $this->name = $name;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
