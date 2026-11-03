<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;

class QaHandHygieneAudit extends Model
{
    protected $table = 'qa_hand_hygiene_audits';

    protected $guarded = ['id'];

    protected $casts = ['audited_on' => 'date:Y-m-d'];
}
