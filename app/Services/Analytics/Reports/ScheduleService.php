<?php

namespace App\Services\Analytics\Reports;

use App\Contracts\Documents\PdfRenderer;
use App\Mail\AnalyticsReportMail;
use App\Models\Analytics\AnalyticsDelivery;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Analytics\AnalyticsSchedule;
use App\Models\User;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Reports sent by e-mail on a timetable (CSV or PDF). Each run uses the scope the owner has now, goes only to people of the hospital (unless the hospital allows others), and is written to the audit trail. */
class ScheduleService
{
    public function __construct(private readonly ReportService $reports, private readonly AuditLogger $audit) {}

    /** @param list<string> $recipients */
    public function create(AnalyticsReport $report, User $by, string $frequency, ?int $day, string $atTime, string $format, array $recipients): AnalyticsSchedule
    {
        $this->validate($report->organization_id, $frequency, $day, $atTime, $format, $recipients);
        if ($report->organization_id !== $this->orgOf($by) || ($report->created_by !== $by->id && ! $report->shared)) {
            throw ValidationException::withMessages(['report' => 'You cannot schedule this report.']);
        }
        $s = AnalyticsSchedule::create(['organization_id' => $report->organization_id, 'report_id' => $report->id, 'frequency' => $frequency, 'day' => $day, 'at_time' => $atTime, 'format' => $format, 'recipients' => array_values(array_unique(array_map('strtolower', $recipients))),
            'active' => true, 'created_by' => $by->id]);
        $s->update(['next_run_at' => $this->next($s)]);
        $this->audit->log('ANALYTICS_SCHEDULE_CREATED', AnalyticsSchedule::class, $s->id, null, ['report' => $report->name, 'recipients' => count($recipients), 'format' => $format]);

        return $s;
    }

    private function orgOf(User $u): int
    {
        return (int) (app(TenantContextResolver::class)->getOrganizationId() ?? $u->organizations()->value('organizations.id'));
    }

    /** @param list<string> $recipients */
    private function validate(int $organizationId, string $frequency, ?int $day, string $atTime, string $format, array $recipients): void
    {
        if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true) || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $atTime)) {
            throw ValidationException::withMessages(['frequency' => 'Choose daily, weekly or monthly and a time such as 07:30.']);
        }
        if (($frequency === 'weekly' && ($day === null || $day < 1 || $day > 7)) || ($frequency === 'monthly' && ($day === null || $day < 1 || $day > 28))) {
            throw ValidationException::withMessages(['day' => $frequency === 'weekly' ? 'Choose the weekday (1 Monday to 7 Sunday).' : 'Choose the day of the month (1 to 28).']);
        }
        if (! in_array($format, (array) config('analytics.schedules.allowed_formats', ['csv', 'pdf']), true)) {
            throw ValidationException::withMessages(['format' => 'Choose CSV or PDF.']);
        }
        $recipients = array_values(array_unique(array_map('strtolower', array_filter(array_map('trim', $recipients)))));
        if ($recipients === [] || count($recipients) > (int) config('analytics.schedules.max_recipients', 10)) {
            throw ValidationException::withMessages(['recipients' => 'Give between one and '.(int) config('analytics.schedules.max_recipients', 10).' e-mail addresses.']);
        }
        $staff = User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->whereIn(DB::raw('lower(email)'), $recipients)->pluck('email')->map('strtolower')->all();
        foreach ($recipients as $r) {
            if (! filter_var($r, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['recipients' => "$r is not an e-mail address."]);
            }
            if (! in_array($r, $staff, true) && ! (bool) config('analytics.schedules.external_recipients', false)) {
                throw ValidationException::withMessages(['recipients' => "$r is not a user of this hospital. Reports go only to the hospital's own staff."]);
            }
        }
    }

    /** The next time it is due, in stored (UTC) time, from the hospital's local timetable. */
    public function next(AnalyticsSchedule $s, ?CarbonImmutable $after = null): CarbonImmutable
    {
        $tz = (string) config('analytics.timezone', 'Asia/Dhaka');
        $now = ($after ?? CarbonImmutable::now())->setTimezone($tz);
        [$h, $m] = array_map('intval', explode(':', $s->at_time));
        $c = $now->setTime($h, $m)->startOfMinute();
        if ($s->frequency === 'daily') {
            $c = $c->lte($now) ? $c->addDay() : $c;
        } elseif ($s->frequency === 'weekly') {
            $c = $c->addDays(($s->day - $c->isoWeekday() + 7) % 7);
            $c = $c->lte($now) ? $c->addWeek() : $c;
        } else {
            $c = $c->setDay(min(28, (int) $s->day));
            $c = $c->lte($now) ? $c->addMonthNoOverflow()->setDay(min(28, (int) $s->day)) : $c;
        }

        return $c->setTimezone('UTC');
    }

    /** The period a run covers: yesterday, the last seven days, or the month just ended. @return array{0:string,1:string} */
    public function period(string $frequency, ?CarbonImmutable $now = null): array
    {
        $today = ($now ?? CarbonImmutable::now())->setTimezone((string) config('analytics.timezone', 'Asia/Dhaka'))->startOfDay();

        return match ($frequency) {
            'daily' => [$today->subDay()->toDateString(), $today->subDay()->toDateString()],
            'weekly' => [$today->subDays(7)->toDateString(), $today->subDay()->toDateString()],
            default => [$today->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->subMonthNoOverflow()->endOfMonth()->toDateString()],
        };
    }

    /** Send every schedule that is due. @return array{sent:int,failed:int} */
    public function runDue(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $sent = $failed = 0;
        foreach (AnalyticsSchedule::query()->where('active', true)->where('next_run_at', '<=', $now->toDateTimeString())->orderBy('id')->get() as $s) {
            $this->deliver($s, $now) ? $sent++ : $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function deliver(AnalyticsSchedule $s, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();
        $error = null;
        $rows = 0;
        $checksum = null;
        try {
            $report = AnalyticsReport::query()->findOrFail($s->report_id);
            $owner = User::query()->find($s->created_by);
            if ($owner === null || ! $owner->can('analytics.report.view') || ! $owner->can('analytics.query.run') || ! $owner->organizations()->where('organizations.id', $s->organization_id)->exists()) {
                throw new \RuntimeException('The person who set this up no longer has access to the reports.');
            }
            [$from, $to] = $this->period($s->frequency, $now);
            $result = $this->reports->run($report, ScopeContext::for($owner, $s->organization_id), $from, $to);
            $rows = count($result['rows']);
            $body = $s->format === 'pdf' ? app(PdfRenderer::class)->render($this->reports->toHtml($report->name, $result), 'a4') : $this->reports->toCsv($result);
            $checksum = hash('sha256', $body);
            $name = Str::slug($report->name).'-'.$to.'.'.$s->format;
            foreach ($s->recipients as $address) {
                Mail::to($address)->send(new AnalyticsReportMail($report->name, $from, $to, $body, $name, $s->format === 'pdf' ? 'application/pdf' : 'text/csv'));
            }
            $this->reports->auditRun($report, $owner, 'scheduled_send', $rows);
        } catch (Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 400);
        }
        AnalyticsDelivery::create(['schedule_id' => $s->id, 'run_at' => now(), 'status' => $error ? 'failed' : 'sent', 'recipients' => count($s->recipients), 'rows' => $rows, 'checksum' => $checksum, 'error' => $error]);
        $s->update(['last_run_at' => now(), 'last_status' => $error ? 'failed' : 'sent', 'next_run_at' => $this->next($s, $now)]);

        return $error === null;
    }
}
