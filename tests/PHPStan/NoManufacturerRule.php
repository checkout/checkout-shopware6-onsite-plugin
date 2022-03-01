<?php declare(strict_types=1);

namespace CheckoutcomShopware\PHPStan\Rules;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\InClassNode;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Rules\RuleErrorBuilder;


final class NoManufacturerRule implements \PHPStan\Rules\Rule
{

    /**
     * @var array
     */
    private $manufacturers;


    /**
     */
    public function __construct()
    {
        $this->manufacturers = [
            'shapeandshift'
        ];
    }


    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return array|string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            !$node instanceof \PHPStan\Node\InClassNode &&
            !$node instanceof \PHPStan\Node\InClassMethodNode &&
            !$node instanceof \PHPStan\Node\InClosureNode &&
            !$node instanceof \PHPStan\Node\InFunctionNode
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
     * @param string $manufacturer
     * @param Node $node
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
     * @param string $search
     * @param string $text
     * @return bool
     */
    private function stringContains(string $search, string $text)
    {
        $pos = strpos($text, $search);

        return ($pos !== false);
    }

}