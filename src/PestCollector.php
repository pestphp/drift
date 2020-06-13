<?php

namespace Pest\Drift;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;

class PestCollector
{
    public $nodes = [];
    public const DATA_PROVIDER = 'data_provider';
    public const TEST_METHODS = 'test_methods';
    public const FILE_SCOPE_GROUP = 'file_scope_group';
    public const AFTER_ALL = 'after_all';
    public const BEFORE_ALL = 'before_all';
    public const BEFORE_EACH = 'before_each';
    public const AFTER_EACH = 'after_each';
    public const USES = 'uses';
    public const HELPER = 'helper';

    public function addExprToArray(string $type, Node $node, Node $newNode)
    {
        $this->nodes[spl_object_hash($node)][$type][] = $newNode instanceof Stmt ? $newNode : new Expression($newNode);;
    }

    public function addTestMethod(Node $node, Expr $method): void
    {
        $this->addExprToArray(self::TEST_METHODS, $node, $method);
    }

    public function addDataProviderMethod(Node $node, Expr\FuncCall $func): void
    {
        $this->addExprToArray(self::DATA_PROVIDER, $node, $func);
    }

    public function addFileScopeGroup(Node $node, Expr\MethodCall $methodCall): void
    {
        $this->addExprToArray(self::FILE_SCOPE_GROUP, $node, $methodCall);
    }

    public function addAfterAll(Node $node, Expr\FuncCall $newNode)
    {
        $this->addExprToArray(self::AFTER_ALL, $node, $newNode);
    }

    public function addBeforeAll(Node $node, Expr\FuncCall $newNode)
    {
        $this->addExprToArray(self::BEFORE_ALL, $node, $newNode);
    }

    public function addBeforeEach(Node $node, Expr\FuncCall $newNode)
    {
        $this->addExprToArray(self::BEFORE_EACH, $node, $newNode);
    }

    public function addAfterEach(Node $node, Expr\FuncCall $newNode)
    {
        $this->addExprToArray(self::AFTER_EACH, $node, $newNode);
    }

    public function addUses(Node $node, Expr\FuncCall $newNode)
    {
        $this->addExprToArray(self::USES, $node, $newNode);
    }

    public function addHelperMethod(Node $node, Function_ $newNode)
    {
        $this->addExprToArray(self::HELPER, $node, $newNode);
    }

    public function getMethodsOrdered(Node $node): array
    {
        $nodes = $this->nodes[spl_object_hash($node)] ?? [];

        if ($nodes === []) {
            return [];
        }

        $sortedNodes = [];
        array_push(
            $sortedNodes,
            ...($nodes[self::USES] ?? []),
            ...($nodes[self::FILE_SCOPE_GROUP] ?? []),
            ...($nodes[self::BEFORE_ALL] ?? []),
            ...($nodes[self::AFTER_ALL] ?? []),
            ...($nodes[self::BEFORE_EACH] ?? []),
            ...($nodes[self::AFTER_EACH] ?? []),
            ...($nodes[self::HELPER] ?? []),
            ...($nodes[self::DATA_PROVIDER] ?? []),
            ...($nodes[self::TEST_METHODS] ?? []),
        );

        return $sortedNodes;
    }
}
