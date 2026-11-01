<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;

class QaAuditFinding extends Model
{
    protected $table = 'qa_audit_findings';

    protected $guarded = ['id'];

    public $timestamps = false;
}
