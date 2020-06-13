<?php

namespace Pest\Drift\PHPUnit\ClassMethod;

use Pest\Drift\PHPUnit\AbstractPHPUnitToPestRector;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;

abstract class AbstractClassMethodRector extends AbstractPHPUnitToPestRector
{
    public ?string $type = null;

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $classNode
     */
    public function refactor(Node $classNode): ?Node
    {
        if (!$this->isInPhpUnitBehavior($classNode)) {
            return null;
        }

        $this->traverseNodesWithCallable($classNode->getMethods(), function (Node $node) use ($classNode) {
            if (! $node instanceof ClassMethod) {
                return null;
            }

            $newNode = $this->classMethodRefactor($classNode, $node);

            if ($newNode === null) {
                return null;
            }

            // Add the new node and remove the old one.
            $this->pestCollector->addExprToArray($this->type, $classNode, $newNode);
            $this->removeNode($node);

            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        });

        return $classNode;
    }

    public abstract function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?Node;
}
