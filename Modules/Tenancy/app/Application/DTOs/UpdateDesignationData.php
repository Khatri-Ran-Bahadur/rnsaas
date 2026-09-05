<?php

namespace Modules\Tenancy\Application\DTOs;

final readonly class UpdateDesignationData
{
    public function __construct(
        public string $name,
        public string $code,
    ) {}
}
