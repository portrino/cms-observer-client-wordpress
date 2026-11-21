<?php

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

final class wpdb
{
    public function __construct(
        public bool $is_mysql = true,
        private readonly string $serverInfo = '',
    ) {}

    public function db_server_info(): string
    {
        return $this->serverInfo;
    }
}

final class WP_Site
{
    public function __construct(
        public string $domain,
    ) {}
}

final class WP_Error
{
    public function __construct(
        private readonly string $message,
    ) {}

    public function get_error_message(): string
    {
        return $this->message;
    }
}

final class WP_CLI
{
    /** @var list<string> */
    public static array $lines = [];

    public static function line(string $message): void
    {
        self::$lines[] = $message;
    }

    public static function success(string $message): void
    {
        self::$lines[] = 'Success: ' . $message;
    }

    public static function error(string $message): never
    {
        throw new \RuntimeException('WP_CLI::error: ' . $message);
    }
}

final class WP_Theme
{
    /**
     * @param array<string, string|false> $headers
     */
    public function __construct(
        private readonly array $headers,
    ) {}

    public function get(string $header): string|false
    {
        return $this->headers[$header] ?? false;
    }
}
