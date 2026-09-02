<?php

namespace Modules\Audit\Domain\ValueObjects;

final readonly class AuditContext
{
    public function __construct(
        public ?string $requestId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public array $metadata = [],
    ) {}
}
