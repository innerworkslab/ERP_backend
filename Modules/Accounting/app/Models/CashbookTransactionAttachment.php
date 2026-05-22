<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\app\Models\CashbookTransaction;
// use Modules\Accounting\Database\Factories\CashbookTransactionAttachmentFactory;

class CashbookTransactionAttachment extends Model
{
    protected $fillable = [
        'cashbook_transaction_id',
        'file_name',
        'file_path',
        'file_type',
        'extension',
        'attachment_type',
        'file_size',
    ];

    protected $appends = [
        'file_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function transaction()
    {
        return $this->belongsTo(
            CashbookTransaction::class,
            'cashbook_transaction_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isImage(): bool
    {
        return $this->attachment_type === 'image';
    }

    public function isPdf(): bool
    {
        return $this->attachment_type === 'pdf';
    }

    public function isSpreadsheet(): bool
    {
        return $this->attachment_type === 'spreadsheet';
    }

    public function isDocument(): bool
    {
        return $this->attachment_type === 'document';
    }
}
