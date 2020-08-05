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

final class PestTestNamingRector extends AbstractRector
{
    /**
     * @var string
     */
    private const TEST = 'test';

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node, self::TEST)) {
            return null;
        }

        $args = (array) $node->args;
        if (count($args) === 0) {
            return null;
        }

        $firstArgumentValue = $this->getValue($args[0]->value);
        if (! is_string($firstArgumentValue)) {
            return null;
        }

        if (! Strings::startsWith($firstArgumentValue, self::TEST)) {
            return null;
        }

        $node->name = new Name(self::TEST);
        $node->args[0]->value = new String_(trim(substr($firstArgumentValue, 4)));

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
