<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabAnalyzerTest extends Model
{
    protected $fillable = ['analyzer_id', 'test_id', 'analyzer_test_code'];

    public function analyzer(): BelongsTo { return $this->belongsTo(LabAnalyzer::class, 'analyzer_id'); }
    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
}
