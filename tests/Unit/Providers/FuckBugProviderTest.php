<?php

declare(strict_types=1);

namespace Unit\Providers;

use Exception;
use FuckBug\HttpClient\HttpClientInterface;
use FuckBug\Providers\FuckBugProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
final class FuckBugProviderTest extends TestCase
{
    private const TEST_DSN = 'https://example.com';

    /**
     * @throws ReflectionException
     */
    public function testLogSendsCorrectPayload()
    {
        /** @var HttpClientInterface|MockObject $client */
        $client = $this->createMock(HttpClientInterface::class);

        /** @noinspection PhpParamsInspection */
        $client->expects(self::once())
            ->method('send')
            ->with(
                self::TEST_DSN,
                'logs',
                self::callback(function (array $data) {
                    self::assertSame('Something went wrong', $data['message']);
                    self::assertSame('WARN', $data['level']);
                    self::assertIsInt($data['time']);
                    self::assertSame(['foo' => 'bar'], $data['context']);
                    return true;
                })
            );

        $provider = $this->createProviderWithClient($client);

        $provider->log('warning', 'Something went wrong', ['foo' => 'bar']);
    }

    /**
     * @throws ReflectionException
     */
    public function testWtfSendsException()
    {
        /** @var HttpClientInterface|MockObject $client */
        $client = $this->createMock(HttpClientInterface::class);

        /** @noinspection PhpParamsInspection */
        $client->expects(self::once())
            ->method('send')
            ->with(
                self::TEST_DSN,
                'errors',
                self::callback(function (array $data) {
                    return isset($data['message'], $data['stacktrace'], $data['file'], $data['line']);
                })
            );

        $provider = $this->createProviderWithClient($client);

        $provider->wtf(new class() extends Exception {
            public function __construct()
            {
                parent::__construct('test');
            }
        });
    }

    /**
     * @throws ReflectionException
     */
    private function createProviderWithClient(HttpClientInterface $client): FuckBugProvider
    {
        $provider = FuckBugProvider::create(self::TEST_DSN, ['password'], false, 1, 1);
        $this->injectClient($provider, $client);
        return $provider;
    }

    /**
     * @param mixed $provider
     * @param mixed $client
     * @throws ReflectionException
     */
    private function injectClient($provider, $client)
    {
        $reflection = new ReflectionClass($provider);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($provider, $client);
    }
}
