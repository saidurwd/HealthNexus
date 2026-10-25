<?php

namespace App\Services\Documents;

use App\Contracts\Documents\DocumentProvider;
use App\Contracts\Documents\PdfRenderer;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Ops\TrainingMode;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Template-driven printable documents (guideline 14.3, H-08): one template per document type, both languages through the
 * label packs, hospital letter-head, QR/barcode, reprints marked and audited.
 */
class DocumentService
{
    private const BN_DIGITS = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    public function __construct(
        private readonly SafeTemplate $templates,
        private readonly Letterhead $letterhead,
        private readonly Code128Svg $barcodes,
        private readonly TenantContextResolver $tenant,
        private readonly AuditLogger $audit,
        private readonly SettingsService $settings,
    ) {}

    /** @return string complete HTML page, ready to print */
    public function render(string $type, string $reference, string $language, User $user, ?string $reprintReason = null): string
    {
        $provider = $this->provider($type);
        $organizationId = $this->tenant->getOrganizationId() ?? throw ValidationException::withMessages(['organization' => 'No active organization.']);

        if (! $user->can($provider->permission())) {
            throw new AuthorizationException('You are not allowed to print this document.');
        }

        if (! in_array($language, config('documents.languages'), true)) {
            throw ValidationException::withMessages(['language' => "Language [$language] is not available."]);
        }

        $source = $provider->resolve($reference, $organizationId) ?? abort(404);

        $previous = DB::table('document_prints')->where(['organization_id' => $organizationId, 'doc_type' => $type, 'document_ref' => $source->reference])->count();

        if ($provider->isFinancial() && $previous > 0 && trim((string) $reprintReason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required to reprint a financial document.']);
        }

        [$template, $templateSource, $templateVersion] = $this->template($type, $organizationId, $source->branchId);

        $copy = $previous + 1;
        $printedAt = now()->timezone(config('documents.timezone'));
        $variables = array_merge($source->variables, [
            'l' => $this->labels($language),
            'letterhead' => $this->letterhead->for($source->branchId ? Branch::query()->find($source->branchId) : null),
            'is_copy' => $provider->isFinancial() && $previous > 0,
            'copy_number' => $copy,
            'is_void' => $source->isVoid,
            'printed_by' => $user->name,
            'printed_at' => $printedAt->format('d M Y H:i'),
            'qr_svg' => $source->qrPayload ? $this->stripXmlProlog((string) QrCode::format('svg')->size(90)->margin(0)->generate($source->qrPayload)) : '',
            'barcode_svg' => $source->barcodeText ? $this->barcodes->render($source->barcodeText, 2, 40) : '',
        ]);

        $body = $this->templates->render($template, $variables);

        if (str_contains($language, 'bn') && $this->settings->get('documents.bangla_numerals')) {
            $body = $this->banglaNumerals($body);
        }

        DB::table('document_prints')->insert([
            'organization_id' => $organizationId, 'branch_id' => $source->branchId, 'printed_by' => $user->id, 'doc_type' => $type,
            'document_ref' => $source->reference, 'patient_id' => $source->patientId, 'language' => $language,
            'template_source' => $templateSource, 'template_version' => $templateVersion, 'copy_number' => $copy,
            'reprint_reason' => $reprintReason, 'printed_at' => now(),
        ]);
        $this->audit->log('PRINT', null, null, null, ['doc_type' => $type, 'ref' => $source->reference, 'copy' => $copy, 'reason' => $reprintReason]);

        if (app(TrainingMode::class)->active()) {
            $body = '<div class="stamp">TRAINING — NOT A REAL DOCUMENT</div>'.$body;
        }

        return $this->layout($provider, $type, $body);
    }

    /** The same document as a PDF (print audit is written once, by render()). */
    public function renderPdf(string $type, string $reference, string $language, User $user, ?string $reprintReason = null): string
    {
        $html = $this->render($type, $reference, $language, $user, $reprintReason);

        return app(PdfRenderer::class)->render($html, $this->provider($type)->paper());
    }

    /** Stores an administrator-edited template as a new version. Invalid or unsafe templates are refused. */
    public function saveTemplate(User $user, string $type, string $body): int
    {
        $this->provider($type);
        $organizationId = $this->tenant->getOrganizationId() ?? throw ValidationException::withMessages(['organization' => 'No active organization.']);

        try {
            $this->templates->validate($body);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        return DB::transaction(function () use ($user, $type, $body, $organizationId) {
            $version = (int) DB::table('document_templates')->where(['organization_id' => $organizationId, 'doc_type' => $type])->whereNull('hospital_id')->max('version') + 1;
            DB::table('document_templates')->where(['organization_id' => $organizationId, 'doc_type' => $type])->whereNull('hospital_id')->update(['is_active' => false]);
            DB::table('document_templates')->insert([
                'organization_id' => $organizationId, 'hospital_id' => null, 'doc_type' => $type, 'version' => $version,
                'body' => $body, 'is_active' => true, 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit->log('TEMPLATE_SAVED', null, null, null, ['doc_type' => $type, 'version' => $version]);

            return $version;
        });
    }

    private function provider(string $type): DocumentProvider
    {
        foreach (config('documents.providers') as $class) {
            $provider = app($class);
            if ($provider->type() === $type) {
                return $provider;
            }
        }

        abort(404, "Unknown document type [$type].");
    }

    /** @return array{0: string, 1: string, 2: ?int} body, source (db|file), version */
    private function template(string $type, int $organizationId, ?int $branchId): array
    {
        $hospitalId = $branchId ? Branch::query()->whereKey($branchId)->value('hospital_id') : null;

        $row = DB::table('document_templates')
            ->where(['organization_id' => $organizationId, 'doc_type' => $type, 'is_active' => true])
            ->where(fn ($q) => $q->whereNull('hospital_id')->when($hospitalId, fn ($q2) => $q2->orWhere('hospital_id', $hospitalId)))
            ->orderByRaw('hospital_id is null')->orderByDesc('version')->first();

        if ($row) {
            return [$row->body, 'db', $row->version];
        }

        return [file_get_contents(resource_path("documents/$type.html")), 'file', null];
    }

    /** @return array<string, string> */
    private function labels(string $language): array
    {
        $en = Lang::get('doc_labels', [], 'en');
        $bn = Lang::get('doc_labels', [], 'bn');

        return match ($language) {
            'bn' => $bn,
            'en+bn' => collect($en)->map(fn ($label, $key) => ($bn[$key] ?? $label).' / '.$label)->all(),
            default => $en,
        };
    }

    private function stripXmlProlog(string $svg): string
    {
        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }

    private function banglaNumerals(string $html): string
    {
        // Leave barcode/QR drawings untouched: their numbers are geometry and scanner data.
        return implode('', array_map(
            fn ($part) => str_starts_with($part, '<svg') ? $part : strtr($part, self::BN_DIGITS),
            preg_split('/(<svg.*?<\/svg>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE),
        ));
    }

    private function layout(DocumentProvider $provider, string $type, string $body): string
    {
        $paper = config('documents.papers.'.$provider->paper());

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.e($type).'</title><style>'
            ."@page{size:{$paper['page']};margin:6mm}"
            ."body{margin:0 auto;width:{$paper['width']};font:{$paper['font']}/1.4 ".config('documents.font_stack').';color:#000}'
            .'.lh,.center{text-align:center}h2{text-align:center;margin:6px 0}table{width:100%;border-collapse:collapse}'
            .'.kv td{padding:2px 0;vertical-align:top}.grid th,.grid td{border:1px solid #000;padding:3px;text-align:left}'
            .'.stamp{border:2px solid #000;text-align:center;font-weight:bold;margin:6px 0;padding:2px}.foot{font-size:.85em;text-align:center}'
            .'.band{padding:2px}svg{max-width:100%}@media screen{body{margin:12px auto}}'
            .'</style></head><body>'.$body.'</body></html>';
    }
}
