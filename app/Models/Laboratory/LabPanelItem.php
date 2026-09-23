<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabPanelItem extends Model
{
    protected $fillable = ['panel_id', 'test_id', 'sequence'];

    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    public function panel(): BelongsTo { return $this->belongsTo(LabPanel::class, 'panel_id'); }
    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
}
