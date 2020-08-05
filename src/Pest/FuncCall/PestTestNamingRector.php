<?php

declare(strict_types=1);

namespace Pest\Drift\Pest\FuncCall;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

class PestTestNamingRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node, 'test')) {
            return null;
        }

        if (count($node->args) < 1 || ! is_string($this->getValue($node->args[0]->value))) {
            return null;
        }

        if (! Strings::startsWith($this->getValue($node->args[0]->value), 'test')) {
            return null;
        }

        $node->name = new Name('test');
        $node->args[0]->value = new String_(trim(substr($this->getValue($node->args[0]->value), 4)));

        return $node;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Renames tests starting with `test` to remove the `test` prefix', [
            new CodeSample(
                <<<'PHP'
test('testStartsWithTest')->skip();
PHP,
                <<<'PHP'
test('StartsWithTest')->skip();
PHP
            ),
        ]);
    }
}
