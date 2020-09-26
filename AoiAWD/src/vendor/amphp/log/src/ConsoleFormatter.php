<?php

namespace Amp\Log;

use Amp\ByteStream\OutputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\Handler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Psr\Log\LogLevel;
use function Amp\Log\hasColorSupport;

final class ConsoleFormatter extends LineFormatter {
    const DEFAULT_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\r\n";

    /** @var bool */
    private $colors;

    public function __construct(string $format = null, string $dateFormat = null, bool $allowInlineLineBreaks = false, bool $ignoreEmptyContextAndExtra = false) {
        parent::__construct($format ?? self::DEFAULT_FORMAT, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
        $this->setAnsiColorOption();
    }

    public function format(array $record): string {
        if ($this->colors) {
            $record['level_name'] = $this->ansifyLevel($record['level_name']);
            $record['channel'] = "\033[1m{$record['channel']}\033[0m";
        }

        return parent::format($record);
    }

    private function setAnsiColorOption() {
        $value = \getenv("AMP_LOG_COLOR");
        if ($value === false || $value === '') {
            $value = "auto";
        }

        $value = \strtolower($value);
        switch ($value) {
            case "1":
            case "true":
            case "on":
                $this->colors = true;
                break;
            case "0":
            case "false":
            case "off":
                $this->colors = false;
                break;
            default:
                $this->colors = hasColorSupport();
                break;
        }
    }

    private function ansifyLevel(string $level): string {
        $level = \strtolower($level);

        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                return "\033[1;31m{$level}\033[0m"; // bold + red
            case LogLevel::WARNING:
                return "\033[1;33m{$level}\033[0m"; // bold + yellow
            case LogLevel::NOTICE:
                return "\033[1;32m{$level}\033[0m"; // bold + green
            case LogLevel::INFO:
                return "\033[1;35m{$level}\033[0m"; // bold + magenta
            case LogLevel::DEBUG:
                return "\033[1;36m{$level}\033[0m"; // bold + cyan
            default:
                return "\033[1m{$level}\033[0m"; // bold
        }
    }
}
