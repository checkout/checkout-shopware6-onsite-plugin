<?php declare(strict_types=1);

namespace Cko\Shopware6\Tests\Services;

use Cko\Shopware6\Service\LoggerService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\Session;

class LoggerServiceTest extends TestCase
{
    protected LoggerService $loggerService;

    protected $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->loggerService = new LoggerService(
            $this->createConfiguredMock(Session::class, [
                'getId' => 'foo',
            ]),
            $this->logger
        );
    }

    public function testLog(): void
    {
        $this->logger->expects(static::once())
            ->method('log');

        $this->loggerService->log(LogLevel::DEBUG, 'test message', ['foo' => 'bar']);
    }

    /**
     * @dataProvider logRecordProvider
     */
    public function testLogRecord(string $logMethod, ?string $logLevel = null): void
    {
        $this->logger->expects(static::once())
            ->method($logMethod);

        if ($logLevel !== null) {
            $this->loggerService->{$logMethod}($logLevel, 'test message', ['foo' => 'bar']);
        } else {
            $this->loggerService->{$logMethod}('test message', ['foo' => 'bar']);
        }
    }

    public function logRecordProvider(): array
    {
        return [
            'Test log method with level' => [
                'log', LogLevel::DEBUG,
            ],
            'Test debug method' => [
                'debug',
            ],
            'Test info method' => [
                'info',
            ],
            'Test notice method' => [
                'notice',
            ],
            'Test warning method' => [
                'warning',
            ],
            'Test error method' => [
                'error',
            ],
            'Test critical method' => [
                'critical',
            ],
            'Test alert method' => [
                'alert',
            ],
            'Test emergency method' => [
                'emergency',
            ],
        ];
    }
}
