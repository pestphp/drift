<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\NodeTypeResolver\TypeAnalyzer\ArrayTypeAnalyzer;
use Rector\NodeTypeResolver\TypeAnalyzer\CountableTypeAnalyzer;
use Rector\NodeTypeResolver\TypeAnalyzer\StringTypeAnalyzer;
use Rector\PostRector\Rector\AbstractPostRector;

class RemovePHPUnitClassRector extends AbstractPostRector
{
    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * @required
     */
    public function autowireTypeAnalyzerDependencies(
        NodeNameResolver $nodeNameResolver,
        NodeTypeResolver $nodeTypeResolver
    ): void {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeTypeResolver = $nodeTypeResolver;
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function getPriority(): int
    {
        return 799; // Lower priority than node remover
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Remove PHPUnit clas'
        );
    }

    /**
     * @return int|Node
     */
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Class_) {
            return $node;
        }

        if ($node->extends === null) {
            return $node;
        }

        if (!$this->nodeTypeResolver->isObjectType($node, TestCase::class)) {
            return $node;
        }

        // Check that test case class has no methods left
        if ($node->getMethods() !== []) {
            return $node;
        }

        return NodeTraverser::REMOVE_NODE;
    }
}
