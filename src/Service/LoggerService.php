<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Service;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\Session;

class LoggerService implements LoggerInterface
{
    public const CHANNEL = 'CheckoutCom';

    private string $sessionId;

    private Logger $logger;

    public function setLogger(Session $session, string $filename, string $retentionDays): void
    {
        $this->sessionId = $session->getId();

        // Create handler for create file every day
        $fileHandler = new RotatingFileHandler($filename, (int) $retentionDays, LogLevel::INFO);
        $this->logger = new Logger(self::CHANNEL, [$fileHandler]);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log(
            $level,
            $this->modifyMessage($message),
            $this->extendInfoData($context)
        );
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug(
            $this->modifyMessage($message),
            $this->extendInfoData($context)
        );
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info(
            $this->modifyMessage($message),
            $this->extendInfoData($context)
        );
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice(
            $this->modifyMessage($message),
            $this->extendInfoData($context)
        );
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning(
            $this->modifyMessage($message),
            $this->extendInfoData($context)
        );
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error(
            $this->modifyMessage($message),
            $this->extendErrorData($context)
        );
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical(
            $this->modifyMessage($message),
            $this->extendErrorData($context)
        );
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert(
            $this->modifyMessage($message),
            $this->extendErrorData($context)
        );
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency(
            $this->modifyMessage($message),
            $this->extendErrorData($context)
        );
    }

    private function modifyMessage($message): string
    {
        return sprintf('%s (Session: %s)', $message, $this->sessionId);
    }

    private function extendInfoData(array $context): array
    {
        $additional = [
            'session' => $this->sessionId,
        ];

        return array_merge_recursive($context, $additional);
    }

    private function extendErrorData(array $context): array
    {
        $additional = [
            'session' => $this->sessionId,
        ];

        return array_merge_recursive($context, $additional);
    }
}
