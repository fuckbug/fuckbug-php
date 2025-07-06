<?php

declare(strict_types=1);

namespace FuckBug\Providers;

use FuckBug\Core\Provider;
use FuckBug\HttpClient\HttpClient;
use FuckBug\HttpClient\HttpClientInterface;
use FuckBug\Util\RequestContext;
use Psr\Log\LoggerTrait;
use Throwable;

final class FuckBugProvider implements Provider
{
    use LoggerTrait;

    /** @var RequestContext */
    private $requestContext;
    /** @var string */
    private $dsn;
    /** @var bool */
    private $enableEnvLogging;
    /** @var int */
    private $timeout;
    /** @var int */
    private $connectionTimeout;
    /** @var HttpClientInterface */
    private $client;

    /** @param string[] $sensitiveFields */
    private function __construct(
        HttpClientInterface $client,
        string $dsn,
        array $sensitiveFields,
        bool $enableEnvLogging,
        int $timeout,
        int $connectionTimeout
    ) {
        $this->dsn = $dsn;
        $this->enableEnvLogging = $enableEnvLogging;
        $this->timeout = $timeout;
        $this->connectionTimeout = $connectionTimeout;
        $this->requestContext = new RequestContext($sensitiveFields);
        $this->client = $client;
    }

    /** @param string[] $sensitiveFields */
    public static function create(
        string $dsn,
        array $sensitiveFields = ['password', 'token', 'api_key'],
        bool $enableEnvLogging = false,
        int $timeout = 5,
        int $connectionTimeout = 3
    ): self {
        $client = new HttpClient($timeout, $connectionTimeout);
        return new self($client, $dsn, $sensitiveFields, $enableEnvLogging, $timeout, $connectionTimeout);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @psalm-suppress MixedOperand
     */
    public function log($level, $message, array $context = []): void
    {
        $data = [
            'time'          => $this->getMicroTime(),
            'level'         => $this->getLevel((string)$level),
            'message'       => $message,
            'context'       => $context,
            'ip'            => $this->requestContext->getUserIp(),
            'url'           => $this->requestContext->getUrl(),
            'method'        => $this->requestContext->getMethod(),
            'headers'       => $this->requestContext->getHeaders(),
            'queryParams'   => $this->requestContext->getQueryParams(),
            'bodyParams'    => $this->requestContext->getBodyParams(),
            'cookies'       => $this->requestContext->getCookies(),
            'session'       => $this->requestContext->getSession(),
            'files'         => $this->requestContext->getFiles(),
        ];

        if ($this->enableEnvLogging) {
            $data['env'] = $this->requestContext->getEnv();
        }
        $this->client->send($this->dsn, 'logs', $data);
    }

    public function wtf(Throwable $exception, array $context = []): void
    {
        $data = [
            'time'          => $this->getMicroTime(),
            'file'          => $exception->getFile(),
            'line'          => $exception->getLine(),
            'message'       => $exception->getMessage(),
            'stacktrace'    => $exception->getTrace(),
            'context'       => $context,
            'ip'            => $this->requestContext->getUserIp(),
            'url'           => $this->requestContext->getUrl(),
            'method'        => $this->requestContext->getMethod(),
            'headers'       => $this->requestContext->getHeaders(),
            'queryParams'   => $this->requestContext->getQueryParams(),
            'bodyParams'    => $this->requestContext->getBodyParams(),
            'cookies'       => $this->requestContext->getCookies(),
            'session'       => $this->requestContext->getSession(),
            'files'         => $this->requestContext->getFiles(),
        ];

        if ($this->enableEnvLogging) {
            $data['env'] = $this->requestContext->getEnv();
        }
        $this->client->send($this->dsn, 'errors', $data);
    }

    private function getMicroTime(): int
    {
        return (int)round(microtime(true) * 1000);
    }

    private function getLevel(string $level): string
    {
        $level = strtoupper(trim($level));

        $levelMapping = [
            'DEBUG'         => 'DEBUG',
            'INFO'          => 'INFO',
            'NOTICE'        => 'INFO',
            'WARNING'       => 'WARN',
            'ERROR'         => 'ERROR',
            'CRITICAL'      => 'ERROR',
            'ALERT'         => 'ERROR',
            'EMERGENCY'     => 'ERROR',
        ];

        return $levelMapping[$level] ?? 'INFO';
    }
}
