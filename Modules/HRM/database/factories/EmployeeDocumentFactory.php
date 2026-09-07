<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;
use Modules\HRM\Models\EmployeeDocument;

class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'tenant_staff_id' => null,

            'type' => fake()->randomElement(
                EmployeeDocumentType::cases(),
            ),

            'title' => fake()->sentence(3),

            'document_number' => fake()->optional()->bothify(
                'DOC-####-????',
            ),

            'issue_date' => fake()->optional()->dateTimeBetween(
                '-2 years',
                'now',
            ),

            'expiry_date' => fake()->optional()->dateTimeBetween(
                'now',
                '+3 years',
            ),

            'file_disk' => 'private',
            'file_path' => 'employee-documents/test/document.pdf',
            'original_file_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,

            'status' => EmployeeDocumentStatus::PENDING,

            'notes' => fake()->optional()->sentence(),

            'created_by' => null,
            'updated_by' => null,
            'verified_by' => null,
            'verified_at' => null,

            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => EmployeeDocumentStatus::PENDING,
        ]);
    }

    public function verified(): static
    {
        return $this->state([
            'status' => EmployeeDocumentStatus::VERIFIED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => EmployeeDocumentStatus::REJECTED,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => EmployeeDocumentStatus::EXPIRED,
            'expiry_date' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
