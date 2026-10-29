<?php

namespace App\Models\Integration;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class DicomWorklistEntry extends Model
{
    use HasPublicId;

    protected $table = 'dicom_worklist_entries';

    protected $guarded = ['id'];

    protected $casts = ['birth_date' => 'date:Y-m-d', 'scheduled_at' => 'datetime'];
}
