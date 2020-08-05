<?php

declare(strict_types=1);

namespace Pest\Drift\PHPUnit\ClassMethod;

use Pest\Drift\PestCollector;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class BeforeClassToBeforeAllRector extends AbstractClassMethodRector
{
    public ?string $type = PestCollector::BEFORE_ALL;

    public function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?FuncCall
    {
        if (! $this->isBeforeClassMethod($classMethodNode)) {
            return null;
        }

        return $this->createPestBeforeAll($classMethodNode);
    }

    private function isBeforeClassMethod(ClassMethod $method): bool
    {
        /** @var PhpDocInfo $phpDoc */
        $phpDoc = $method->getAttribute(AttributeKey::PHP_DOC_INFO);

        return $phpDoc && $phpDoc->hasByName('beforeClass');
    }

    private function createPestBeforeAll(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall('beforeAll', [
            new Closure(['stmts' => $method->stmts]),
        ]);
    }
}
