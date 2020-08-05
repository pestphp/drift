<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\ClassMethod;

use Pest\Drift\PestCollector;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class TearDownToAfterEachRector extends AbstractClassMethodRector
{
    public ?string $type = PestCollector::AFTER_EACH;

    public function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?FuncCall
    {
        if (! $this->isTearDownMethod($classMethodNode)) {
            return null;
        }

        return $this->createPestAfterEach($classMethodNode);
    }

    private function isTearDownMethod(ClassMethod $method): bool
    {
        return $this->isName($method, 'tearDown');
    }

    private function createPestAfterEach(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall('afterEach', [
            new Closure(['stmts' => $method->stmts]),
        ]);
    }
}
