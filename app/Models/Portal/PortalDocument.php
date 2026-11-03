<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class PortalDocument extends Model
{
    use HasPublicId;

    protected $table = 'portal_documents';

    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
}
