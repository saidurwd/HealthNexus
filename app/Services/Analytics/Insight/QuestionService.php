<?php

namespace App\Services\Analytics\Insight;

use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Semantic\SemanticQuery;
use Carbon\CarbonImmutable;

/**
 * "Ask a question": turns an English question into a semantic query by matching words to the model — no AI service. It is advisory and read-only: it shows the query it built and the SQL that ran,
 * runs with the asker's own scope, and says plainly when it did not understand. (A real language model is Phase 20, behind the same boundary.)
 */
class QuestionService
{
    public function __construct(private readonly SemanticQuery $queries) {}

    /** word or phrase => [fact, measure] */
    private const TOPICS = [
        'net revenue' => ['revenue', 'net_revenue'], 'revenue' => ['revenue', 'net_revenue'], 'income' => ['revenue', 'net_revenue'], 'billed' => ['invoices', 'billed'], 'collection rate' => ['invoices', 'collection_rate'], 'collected' => ['invoices', 'collected'],
        'outstanding' => ['invoices', 'outstanding'], 'dues' => ['invoices', 'outstanding'], 'outpatient visits' => ['encounters', 'opd_visits'], 'opd visits' => ['encounters', 'opd_visits'], 'visits' => ['encounters', 'visits'],
        'no-show' => ['appointments', 'no_show_rate'], 'no show' => ['appointments', 'no_show_rate'], 'appointments' => ['appointments', 'appointments'], 'waiting' => ['appointments', 'avg_wait'], 'wait' => ['appointments', 'avg_wait'],
        'admissions' => ['admissions', 'admissions'], 'length of stay' => ['admissions', 'avg_los'], 'occupancy' => ['beds', 'occupancy_rate'], 'emergency' => ['emergency', 'ed_visits'], 'door to doctor' => ['emergency', 'door_to_doctor'],
        'operations' => ['surgery', 'surgeries_done'], 'surgeries' => ['surgery', 'surgeries_done'], 'cancellation' => ['surgery', 'cancellation_rate'], 'lab turn' => ['lab', 'lab_tat'], 'lab orders' => ['lab', 'lab_orders'], 'laboratory' => ['lab', 'lab_orders'],
        'imaging' => ['radiology', 'exams'], 'dispensing' => ['pharmacy', 'dispensings'], 'prescriptions' => ['pharmacy', 'dispensings'], 'stock-out' => ['stock', 'stockout_rate'], 'stock out' => ['stock', 'stockout_rate'],
        'claims' => ['claims', 'claims'], 'denial' => ['claims', 'denial_rate'], 'attendance' => ['daily', 'attendance_rate'], 'rating' => ['daily', 'avg_rating'], 'new patients' => ['daily', 'new_patients'], 'payments' => ['payments', 'payments_total'],
    ];

    /** word => dimension key (checked against the fact) */
    private const CUTS = ['department' => 'department', 'doctor' => 'doctor', 'branch' => 'branch', 'category' => 'category', 'service' => 'category', 'status' => 'status', 'hour' => 'hour', 'priority' => 'priority', 'payer' => 'payer_kind', 'insurer' => 'payer', 'method' => 'method', 'type' => 'visit_type'];

    /** @return array{understood:bool,message:string,query:?array<string,mixed>,result:?array<string,mixed>,suggestions:list<string>} */
    public function ask(string $question, ScopeContext $scope): array
    {
        $text = ' '.strtolower(trim(preg_replace('/[^a-z0-9\- ]+/i', ' ', $question))).' ';
        $suggestions = ['What was the net revenue by department last month?', 'How many outpatient visits per day this month?', 'What is the no-show rate by doctor last 30 days?', 'Show bed occupancy by day this month.'];
        $topic = null;
        $best = 0;
        foreach (self::TOPICS as $phrase => $target) {
            if (str_contains($text, ' '.$phrase) && strlen($phrase) > $best) {
                $topic = $target;
                $best = strlen($phrase);
            }
        }
        if ($topic === null) {
            return ['understood' => false, 'message' => 'I could not tell which figure you mean. I know about revenue, visits, appointments, admissions, beds, emergency, operations, lab, imaging, pharmacy, claims, attendance and ratings.', 'query' => null, 'result' => null, 'suggestions' => $suggestions];
        }
        [$fact, $measure] = $topic;
        $dims = SemanticCatalog::fact($fact)['dimensions'];
        $groupBy = [];
        $grain = null;
        if (preg_match('/ by (day|daily) | per day | each day | daily /', $text)) {
            $grain = 'day';
        } elseif (preg_match('/ by month | per month | monthly | each month /', $text)) {
            $grain = 'month';
        }
        foreach (self::CUTS as $word => $dim) {
            if (preg_match('/ (by|per|for each) '.preg_quote($word, '/').'/', $text) && isset($dims[$dim])) {
                $groupBy[] = $dim;
            }
        }
        [$from, $to, $periodText] = $this->period($text);
        $query = ['fact' => $fact, 'measures' => [$measure], 'group_by' => array_slice($groupBy, 0, 2), 'grain' => $grain, 'from' => $from, 'to' => $to, 'limit' => 50];
        $result = $this->queries->run($query, $scope);

        return ['understood' => true, 'message' => 'I read this as: '.$result['columns'][count($result['columns']) - 1]['label'].($groupBy ? ' by '.implode(' and ', $groupBy) : '').($grain ? ' by '.$grain : '').', '.$periodText.'. This is a reading of your words, not a decision: check the query below.',
            'query' => $query, 'result' => $result, 'suggestions' => []];
    }

    /** @return array{0:string,1:string,2:string} */
    private function period(string $text): array
    {
        $today = CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->startOfDay();
        $r = function (string $a, string $b, string $label) {
            return [$a, $b, $label];
        };
        $match = fn (string $p) => str_contains($text, ' '.$p.' ') || str_ends_with(rtrim($text), ' '.$p);

        return match (true) {
            $match('today') => $r($today->toDateString(), $today->toDateString(), 'today'),
            $match('yesterday') => $r($today->subDay()->toDateString(), $today->subDay()->toDateString(), 'yesterday'),
            $match('last week') => $r($today->subWeek()->startOfWeek()->toDateString(), $today->subWeek()->endOfWeek()->toDateString(), 'last week'),
            $match('this week') => $r($today->startOfWeek()->toDateString(), $today->toDateString(), 'this week'),
            $match('last month') => $r($today->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->subMonthNoOverflow()->endOfMonth()->toDateString(), 'last month'),
            $match('this month') => $r($today->startOfMonth()->toDateString(), $today->toDateString(), 'this month'),
            $match('this year') => $r($today->startOfYear()->toDateString(), $today->toDateString(), 'this year'),
            $match('last year') => $r($today->subYear()->startOfYear()->toDateString(), $today->subYear()->endOfYear()->toDateString(), 'last year'),
            (bool) preg_match('/last (\d{1,3}) days/', $text, $m) => $r($today->subDays(max(1, (int) $m[1]) - 1)->toDateString(), $today->toDateString(), 'the last '.(int) $m[1].' days'),
            default => $r($today->subDays(29)->toDateString(), $today->toDateString(), 'the last 30 days (you did not say a period)'),
        };
    }
}
