<?php

namespace Pest\Drift\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;

class DataProviderRector extends AbstractPHPUnitToPestRector
{
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, TestCase::class)) {
            return null;
        }

        /** @var ClassMethod[] $methods */
        $methods = $this->betterNodeFinder->findInstanceOf(
            $node,
            ClassMethod::class
        );

        foreach ($methods as $method) {
            if (!$this->isDataProviderMethod($method, $methods)) {
                continue;
            }

            $newNode = $this->createPestDataProvider($method);
            $this->removeNode($method);
            $this->pestCollector->addDataProviderMethod($node, $newNode);
        }

        return $node;
    }

    private function isDataProviderMethod(ClassMethod $method, array $methods)
    {
        foreach ($methods as $lookUpMethod) {
            $dataProviderName = $this->getDataProviderName($lookUpMethod);

            if ($dataProviderName === null) {
                continue;
            }

            if ($this->isName($method, $dataProviderName)) {
                return true;
            }
        }

        return false;
    }

    private function createPestDataProvider(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'dataset',
            [
                $this->getName($method),
                new Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
