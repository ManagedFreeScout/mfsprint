<?php

namespace Modules\MFSPrint\Models;

use Illuminate\Database\Eloquent\Model;

class MFSPrintLicense extends Model
{
    // Staleness backstop (same as MSTeamsFS, cards #232 F5 / #258): the licence is re-checked
    // every 6 hours; if no successful check has happened for this long (e.g. the hub is
    // unreachable, or the cron isn't running), the cached "valid" is no longer trusted.
    // LicenseService touches updated_at on every successful check, even when nothing changed.
    const MAX_STALE_DAYS = 14;

    protected $table = 'modules_licenses';

    protected $fillable = [
        "module_alias",
        "license_key",
        "is_valid",
        "status",
        "license_type",
        "expires_at",
        "domain",
        "response_data"
    ];

    protected $casts = [
        "is_valid" => "boolean",
        "expires_at" => "datetime",
        "response_data" => "array",
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('mfsprint', function ($builder) {
            $builder->where('module_alias', 'mfsprint');
        });

        static::creating(function ($model) {
            $model->module_alias = 'mfsprint';
        });
    }

    public function isValid()
    {
        if (!$this->is_valid || $this->status !== "active") {
            return false;
        }

        // Expired subscription = features off (Rutger, 8 Oct 2026: "pay to use it").
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->updated_at && $this->updated_at->lt(now()->subDays(self::MAX_STALE_DAYS))) {
            return false;
        }

        return true;
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
