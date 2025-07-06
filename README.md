# Official FuckBug SDK for PHP

FuckBug is a developer-focused wrapper for all your PHP loggers. It provides a unified, flexible interface to handle logging and error monitoring across different providers, with support for multiple log levels and context enrichment.

## 🚀 Quick Start

### Install

Install the SDK using [Composer](https://getcomposer.org/).

```bash
composer require fuckbug/fuckbug
```

### Configuration

Initialize the SDK as early as possible in your application

```php
\FuckBug\FuckBug::init([
    new \FuckBug\Core\ProviderSetup(
        \FuckBug\Providers\FuckBugProvider::create('__PUBLIC_DSN__'),
        true, // enable catching Throwable
        false // disable Debug level logs
    )
]);
```

### Usage

#### Log exceptions using `wtf()`

```php
try {
    thisFunctionThrows(); // throw new \Exception('foo bar');
} catch (\Exception $exception) {
    \FuckBug\FuckBug::getInstance()->wtf($exception);
}
```

#### Log messages with severity levels

```php
\FuckBug\FuckBug::getInstance()->warning('User not found', ['userId' => 8]);
```

## ⚙️ Custom and Multiple Providers

#### Create your own Provider

```php
use FuckBug\Core\Provider;
use Psr\Log\LoggerTrait;
use Throwable;

class MyCustomProvider implements Provider
{
    use LoggerTrait;

    public function wtf(Throwable $exception, array $context = []): void
    {
        // Your custom logic for handling exceptions
    }

    public function log($level, $message, array $context = []): void
    {
        // Required by the LoggerTrait
    }
}
```

#### Initialize FuckBug with Multiple Providers

```php
\FuckBug\FuckBug::init([
    new \FuckBug\Core\ProviderSetup(FuckBug\Providers\FuckBugProvider::create('__PUBLIC_DSN__')),
    new \FuckBug\Core\ProviderSetup(new MyCustomProvider())
]);
```

## 🕵️ Request Context

FuckBug also supports gathering request-specific context information such as IP address, URL, query/body parameters, headers, and more via `RequestContext`.
It is recommended to use this class in your custom implementations of the `Provider` interface to enrich logs with useful request metadata.

#### Example with custom Provider

```php
use FuckBug\Core\Provider;
use FuckBug\Util\RequestContext;
use Psr\Log\LoggerTrait;
use Throwable;

class MyCustomProvider implements Provider
{
    use LoggerTrait;

    /** @var RequestContext */
    private $context;

    public function __construct()
    {
        $this->context = new RequestContext(['password', 'token']);
    }

    public function wtf(Throwable $exception, array $context = []): void
    {
        $context['ip'] = $this->context->getUserIp();
        $context['url'] = $this->context->getUrl();
        $context['method'] = $this->context->getMethod();

        // Send enriched context to your storage/logs/etc.
        error_log('[MyCustomProvider] ' . $exception->getMessage() . ' ' . json_encode($context));
    }

    public function log($level, $message, array $context = []): void
    {
        // Optional: implement log-level handling
    }
}
```

## 📄 License

Licensed under the MIT license, see [LICENSE](LICENSE).
