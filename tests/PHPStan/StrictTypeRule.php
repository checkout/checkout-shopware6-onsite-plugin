<?php declare(strict_types=1);

namespace CheckoutcomShopware\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

final class StrictTypeRule implements Rule
{
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @return array|string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FileNode) {
            throw new ShouldNotHappenException(\sprintf(
                'Expected node to be instance of "%s", but got instance of "%s" instead.',
                FileNode::class,
                \get_class($node)
            ));
        }

        $nodes = $node->getNodes();

        if (\count($nodes) === 0) {
            return [];
        }

        $firstNode = \array_shift($nodes);

        if (
            $firstNode instanceof Node\Stmt\InlineHTML
            && $firstNode->getEndLine() === 2
            && \mb_strpos($firstNode->value, '#!') === 0
        ) {
            $firstNode = \array_shift($nodes);
        }

        if ($firstNode instanceof Node\Stmt\Declare_) {
            foreach ($firstNode->declares as $declare) {
                if (
                    $declare->key->toLowerString() === 'strict_types'
                    && $declare->value instanceof Node\Scalar\LNumber
                    && $declare->value->value === 1
                ) {
                    return [];
                }
            }
        }

        return [
            'File has no "declare(strict_types=1)" declaration. This is required for this project!',
        ];
    }
}
