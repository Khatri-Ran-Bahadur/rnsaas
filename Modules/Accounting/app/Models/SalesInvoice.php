<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Accounting\Database\Factories\SalesInvoiceFactory;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'customer_id',
    'invoice_number',
    'invoice_date',
    'due_date',
    'currency',
    'subtotal',
    'discount_total',
    'tax_total',
    'grand_total',
    'status',
    'reference',
    'notes',
    'journal_entry_id',
    'created_by',
])]
#[Hidden([
    'id',
])]
class SalesInvoice extends Model
{
    use HasFactory;

    protected $table = 'accounting_sales_invoices';

    protected function casts(): array
    {
        return [
            'invoice_date' => 'immutable_date',
            'due_date' => 'immutable_date',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
        ];
    }

    protected static function newFactory(): SalesInvoiceFactory
    {
        return SalesInvoiceFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            $invoice->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(AccountingAttachment::class, 'attachable');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class, 'sales_invoice_id');
    }
}
