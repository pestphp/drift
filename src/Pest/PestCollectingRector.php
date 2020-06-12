<?php

namespace Pest\Drift\Pest;

use Pest\Drift\PestCollector;
use PhpParser\Node;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\PostRector\Rector\AbstractPostRector;

class PestCollectingRector extends AbstractPostRector
{
    /** @var PestCollector */
    private $pestCollector;

    public function __construct(PestCollector $pestCollector)
    {
        $this->pestCollector = $pestCollector;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition("Takes care of the pest collector.");
    }

    public function getPriority(): int
    {
        return 1500;
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
