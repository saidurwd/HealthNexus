<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/** What the posting engine did with one source record and event: posted once, skipped, or failed with a reason to retry. */
class Posting extends Model
{
    protected $table = 'fin_postings';

    protected $guarded = ['id'];

    protected $casts = ['last_attempt_at' => 'datetime'];
}
