<?php

declare(strict_types=1);

namespace FuckBug\HttpClient;

interface HttpClientInterface
{
    public function send(string $dsn, string $type, array $data): int;
}
