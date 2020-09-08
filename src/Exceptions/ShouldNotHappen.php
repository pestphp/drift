<?php

declare(strict_types=1);

namespace Pest\Drift\Exceptions;

use RuntimeException;

final class ShouldNotHappen extends RuntimeException
{
    public function __construct(Exception $exception)
    {
        $message = $exception->getMessage();

        parent::__construct(sprintf(<<<EOF
This should not happen - please create an new issue here: https://github.com/pestphp/drift.
- Issue: %s
- PHP version: %s
- Operating system: %s
EOF
            , $message, PHP_VERSION, PHP_OS), 1, $exception);
    }

    public static function fromMessage(string $message): self
    {
        return new self(new Exception($message));
    }
}
