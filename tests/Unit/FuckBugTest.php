<?php

declare(strict_types=1);

namespace Unit;

use Exception;
use FuckBug\Core\Provider;
use FuckBug\Core\ProviderSetup;
use FuckBug\FuckBug;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerTrait;
use Throwable;

/**
 * @internal
 */
final class FuckBugTest extends TestCase
{
    public function testSingletonInstance(): void
    {
        $instance = FuckBug::init();
        self::assertInstanceOf(FuckBug::class, $instance);
        self::assertSame($instance, FuckBug::getInstance());
    }

    public function testWtfCalledIfEnabled(): void
    {
        $logger = new class() implements Provider {
            use LoggerTrait;

            public $called = false;

            public function wtf(Throwable $exception, array $context = []): void
            {
                $this->called = true;
            }

            public function log($level, $message, array $context = []): void
            {
            }
        };

        $setup = new ProviderSetup($logger, true);
        $fuckBug = new FuckBug([$setup]);
        $fuckBug->wtf(new Exception('Test'));

        self::assertTrue($logger->called);
    }

    public function testWtfNotCalledIfDisabled(): void
    {
        $logger = new class() implements Provider {
            use LoggerTrait;
            public $called = false;

            public function wtf(Throwable $exception, array $context = []): void
            {
                $this->called = true;
            }

            public function log($level, $message, array $context = []): void
            {
            }
        };

        $setup = new ProviderSetup($logger, false);
        $fuckBug = new FuckBug([$setup]);
        $fuckBug->wtf(new Exception('Test'));

        self::assertFalse($logger->called);
    }

    public function testMultipleProvidersAreCalled(): void
    {
        $called = [];

        $makeLogger = function (string $id) use (&$called) {
            return new class($id, $called) implements Provider {
                use LoggerTrait;

                private $id;
                private $calledRef;

                public function __construct($id, &$calledRef)
                {
                    $this->id = $id;
                    $this->calledRef = &$calledRef;
                }

                public function wtf(Throwable $exception, array $context = []): void
                {
                    $this->calledRef[] = $this->id;
                }

                public function log($level, $message, array $context = []): void
                {
                }
            };
        };

        $logger1 = $makeLogger('A');
        $logger2 = $makeLogger('B');

        $fuckBug = new FuckBug([
            new ProviderSetup($logger1, true),
            new ProviderSetup($logger2, true),
        ]);

        $fuckBug->wtf(new Exception('Test'));

        self::assertEquals(['A', 'B'], $called);
    }

    public function testLogRespectsLevelFlags(): void
    {
        $levels = [
            'debug' => 'enabledDebug',
            'info' => 'enabledInfo',
            'notice' => 'enabledNotice',
            'warning' => 'enabledWarning',
            'error' => 'enabledError',
            'critical' => 'enabledCritical',
            'alert' => 'enabledAlert',
            'emergency' => 'enabledEmergency',
        ];

        foreach ($levels as $level => $flag) {
            $logger = new class() implements Provider {
                use LoggerTrait;

                public $lastLevel;
                public $lastMessage;
                public $lastContext;

                public function log($level, $message, array $context = []): void
                {
                    $this->lastLevel = $level;
                    $this->lastMessage = $message;
                    $this->lastContext = $context;
                }

                public function wtf(Throwable $exception, array $context = []): void
                {
                }
            };

            $setup = new ProviderSetup(
                $logger,
                false,
                $flag === 'enabledDebug',
                $flag === 'enabledInfo',
                $flag === 'enabledNotice',
                $flag === 'enabledWarning',
                $flag === 'enabledError',
                $flag === 'enabledCritical',
                $flag === 'enabledAlert',
                $flag === 'enabledEmergency'
            );

            $fuckBug = new FuckBug([$setup]);
            $fuckBug->log(strtoupper($level), 'msg', ['a' => 1]);

            self::assertSame(strtoupper($level), $logger->lastLevel);
            self::assertSame('msg', $logger->lastMessage);
            self::assertSame(['a' => 1], $logger->lastContext);
        }
    }

    public function testLogSkipsWhenLevelDisabled(): void
    {
        $logger = new class() implements Provider {
            use LoggerTrait;

            public $called = false;

            public function log($level, $message, array $context = []): void
            {
                $this->called = true;
            }

            public function wtf(Throwable $exception, array $context = []): void
            {
            }
        };

        $setup = new ProviderSetup($logger, false, false, false, false, false, false, false, false, false);
        $fuckBug = new FuckBug([$setup]);

        $fuckBug->log('debug', 'should not log');
        self::assertFalse($logger->called);
    }

    public function testUnknownLogLevelIsIgnored(): void
    {
        $logger = new class() implements Provider {
            use LoggerTrait;
            public $called = false;

            public function log($level, $message, array $context = []): void
            {
                $this->called = true;
            }

            public function wtf(Throwable $exception, array $context = []): void
            {
            }
        };

        $setup = new ProviderSetup($logger);
        $fuckBug = new FuckBug([$setup]);
        $fuckBug->log('unknownLevel', 'msg');

        self::assertFalse($logger->called);
    }
}
