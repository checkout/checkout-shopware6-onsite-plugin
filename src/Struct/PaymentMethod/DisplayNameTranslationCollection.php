<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Collection;

class DisplayNameTranslationCollection extends Collection
{
    public function addLangData(string $lang, string $name): void
    {
        $displayNameStruct = new DisplayNameTranslationStruct($lang, $name);

        $this->set($displayNameStruct->getLang(), $displayNameStruct);
    }

    public function toTranslationArray(): array
    {
        if (empty($this->elements)) {
            return [];
        }

        $translations = [];
        /** @var DisplayNameTranslationStruct $translation */
        foreach ($this->getElements() as $translation) {
            $translations[$translation->getLang()] = $translation->getName();
        }

        return $translations;
    }

    protected function getExpectedClass(): string
    {
        return DisplayNameTranslationStruct::class;
    }
}
