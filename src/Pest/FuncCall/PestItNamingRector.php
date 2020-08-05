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

final class PestItNamingRector extends AbstractRector
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

        $args = (array) $node->args;
        if (count($args) === 0) {
            return null;
        }

        $firstArgumentValue = $this->getValue($args[0]->value);
        if (! is_string($firstArgumentValue)) {
            return null;
        }

        if (! Strings::startsWith($firstArgumentValue, 'it')) {
            return null;
        }

        $node->name = new Name('it');
        $node->args[0]->value = new String_(trim(substr($firstArgumentValue, 2)));

        return $node;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Renames tests starting with `it` to use the `it()` function', [
            new CodeSample(
                <<<'PHP'
test('it starts with it')->skip();
PHP,
                <<<'PHP'
it('starts with it')->skip();
PHP
            ),
        ]);
    }
}
