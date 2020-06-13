<?php

namespace Pest\Drift\PHPUnit\ClassMethod;

use Pest\Drift\PestCollector;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;

class AfterClassToAfterAllRector extends AbstractClassMethodRector
{
    public ?string $type = PestCollector::AFTER_ALL;

    public function classMethodRefactor(Class_ $classNode, ClassMethod $classMethodNode): ?FuncCall
    {
        if (!$this->isAfterClassMethod($classMethodNode)) {
            return null;
        }

        return $this->createPestAfterAll($classMethodNode);
    }

    private function isAfterClassMethod(ClassMethod $method): bool
    {
        /** @var PhpDocInfo $phpDoc */
        $phpDoc = $method->getAttribute(AttributeKey::PHP_DOC_INFO);

        return $phpDoc && $phpDoc->hasByName('afterClass');
    }

    private function createPestAfterAll(ClassMethod $method): FuncCall
    {
        return $this->builderFactory->funcCall(
            'afterAll',
            [
                new Closure(['stmts' => $method->stmts]),
            ]
        );
    }
}
