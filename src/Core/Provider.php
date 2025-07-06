<?php

declare(strict_types=1);

namespace FuckBug\Core;

use Psr\Log\LoggerInterface;
use Throwable;

interface Provider extends LoggerInterface
{
    public function wtf(Throwable $exception, array $context = []): void;
}
