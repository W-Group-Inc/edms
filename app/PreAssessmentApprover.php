<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PreAssessmentApprover extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
