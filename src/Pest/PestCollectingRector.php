<?php

declare(strict_types=1);

namespace Pest\Drift\Pest;

use Pest\Drift\PestCollector;
use PhpParser\Node;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\PostRector\Rector\AbstractPostRector;

final class PestCollectingRector extends AbstractPostRector
{
    private PestCollector $pestCollector;

    public function __construct(PestCollector $pestCollector)
    {
        $this->pestCollector = $pestCollector;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Takes care of the pest collector.');
    }

    public function getPriority(): int
    {
        return 1_500;
    }

    /**
     * @return Node[]|Node|null
     */
    public function leaveNode(Node $node)
    {
        $testMethods = $this->pestCollector->getMethodsOrdered($node);

        if ($testMethods !== []) {
            return array_merge([$node], $testMethods);
        }

        return $node;
    }
}
