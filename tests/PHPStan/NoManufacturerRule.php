<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\PHPStan;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

final class NoManufacturerRule implements Rule
{
    /**
     * @var array
     */
    private $manufacturers;

    public function __construct()
    {
        $this->manufacturers = [
            'shapeandshift',
        ];
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return array|string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            !$node instanceof \PHPStan\Node\InClassNode
            && !$node instanceof \PHPStan\Node\InClassMethodNode
            && !$node instanceof \PHPStan\Node\InClosureNode
            && !$node instanceof \PHPStan\Node\InFunctionNode
        ) {
            return [];
        }

        foreach ($this->manufacturers as $manufacturer) {
            if ($this->hasNodeManufacturer($manufacturer, $node)) {
                return [
                    'Found Plugin Manufacturer: "' . $manufacturer . '"! Please remove this and keep a Checkout.com branding!',
                ];
            }
        }

        return [];
    }

    /**
     * @return bool
     */
    private function hasNodeManufacturer(string $manufacturer, Node $node)
    {
        if ($node->getDocComment() !== null) {
            $comment = $node->getDocComment()->getText();

            if ($this->stringContains(strtolower($manufacturer), strtolower($comment))) {
                return true;
            }
        }

        /** @var Doc $comment */
        foreach ($node->getComments() as $comment) {
            if ($this->stringContains(strtolower($manufacturer), strtolower($comment->getText()))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function stringContains(string $search, string $text)
    {
        $pos = strpos($text, $search);

        return $pos !== false;
    }
}
