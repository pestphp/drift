<?php

declare(strict_types=1);

namespace Pest\Drift\ValueObject;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;

final class MethodCallWithPosition
{
    private int $positoin;

    private MethodCall $methodCall;

    public function __construct(int $positoin, MethodCall $methodCall)
    {
        $this->positoin = $positoin;
        $this->methodCall = $methodCall;
    }

    public function getPosition(): int
    {
        return $this->positoin;
    }

    /**
     * @return Arg[]
     */
    public function getArgs(): array
    {
        return $this->methodCall->args;
    }
}
