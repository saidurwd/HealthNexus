<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use Database\Factories\Quality\QaIncidentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QaIncident extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaIncidentFactory
    {
        return QaIncidentFactory::new();
    }

    protected $table = 'qa_incidents';

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime', 'triaged_at' => 'datetime', 'closed_at' => 'datetime', 'due_on' => 'date:Y-m-d', 'is_anonymous' => 'boolean'];

    /** The reported facts: set once, never changed (the database refuses it too). A correction is an amendment. */
    public const FROZEN = ['organization_id', 'branch_id', 'number', 'kind', 'category', 'harm', 'occurred_at', 'department_id', 'location', 'patient_id', 'description', 'immediate_action', 'is_anonymous', 'source_type', 'source_id'];

    protected static function booted(): void
    {
        static::updating(function (self $incident) {
            foreach (self::FROZEN as $column) {
                if ($incident->isDirty($column)) {
                    throw new \LogicException(__('A reported incident cannot be changed: add an amendment.'));
                }
            }
        });
        static::deleting(fn () => throw new \LogicException(__('An incident cannot be deleted.')));
    }

    /** @return HasMany<QaIncidentAmendment, $this> */
    public function amendments(): HasMany
    {
        return $this->hasMany(QaIncidentAmendment::class, 'incident_id');
    }

    /** @return HasMany<QaIncidentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(QaIncidentEvent::class, 'incident_id');
    }

    /** @return HasOne<QaInvestigation, $this> */
    public function investigation(): HasOne
    {
        return $this->hasOne(QaInvestigation::class, 'incident_id');
    }

    /** @return Builder<QaAction> */
    public function actions()
    {
        return QaAction::query()->where(['source_type' => 'incident', 'source_id' => $this->id])->orderBy('due_on');
    }

    /** @return HasOne<QaIncidentReporter, $this> */
    public function reporterRow(): HasOne
    {
        return $this->hasOne(QaIncidentReporter::class, 'incident_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** What the incident says now: the latest amendment of a field wins, the reported value is kept underneath. */
    public function effective(string $field): mixed
    {
        $amendment = $this->amendments->where('field', $field)->sortByDesc('id')->first();

        return $amendment ? $amendment->new_value : $this->getAttribute($field);
    }

    /** The harm level that counts for follow-up: set at review, otherwise as amended or reported. */
    public function harmNow(): string
    {
        return (string) ($this->review_harm ?: $this->effective('harm'));
    }
}
