<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\JournalEntryFactory;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_datetime',
        'source_type',
        'source_id',
        'description',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'journal_datetime' => 'datetime',
    ];

    public function postings()
    {
        return $this->hasMany(JournalPosting::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
