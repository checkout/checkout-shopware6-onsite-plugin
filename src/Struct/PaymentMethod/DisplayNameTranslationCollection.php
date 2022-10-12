<?php declare(strict_types=1);

namespace Cko\Shopware6\Struct\PaymentMethod;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method DisplayNameTranslationStruct[]    getIterator()
 * @method DisplayNameTranslationStruct[]    getElements()
 * @method DisplayNameTranslationStruct|null get(string $key)
 * @method DisplayNameTranslationStruct|null first()
 * @method DisplayNameTranslationStruct|null last()
 */
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
        foreach ($this->getElements() as $translation) {
            $translations[$translation->getLang()] = $translation->getName();
        }

        return $translations;
    }

    public function getName(string $lang): string
    {
        if (empty($this->elements)) {
            return '';
        }

        /** @var ?DisplayNameTranslationStruct $translation */
        $translation = $this->get($lang);

        if (!$translation instanceof DisplayNameTranslationStruct) {
            return '';
        }

        return $translation->getName();
    }

    protected function getExpectedClass(): string
    {
        return DisplayNameTranslationStruct::class;
    }
}
