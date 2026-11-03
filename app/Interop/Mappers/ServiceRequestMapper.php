<?php

namespace App\Interop\Mappers;

use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use Illuminate\Database\Eloquent\Model;

/** Lab tests and imaging exams that were ordered. */
class ServiceRequestMapper extends Mapper
{
    public function type(): string
    {
        return 'ServiceRequest';
    }

    public function sources(): array
    {
        return [
            ['model' => LabOrderItem::class, 'entity' => 'LabOrderItem', 'patient' => fn ($q, $pid) => $q->whereIn('lab_order_id', LabOrder::query()->where('patient_id', $pid)->select('id')), 'search' => ['category' => fn ($q, $v) => $v === 'laboratory' ? $q : $q->whereRaw('1 = 0')]],
            ['model' => RadiologyOrderItem::class, 'entity' => 'RadiologyOrderItem', 'patient' => fn ($q, $pid) => $q->whereIn('radiology_order_id', RadiologyOrder::query()->where('patient_id', $pid)->select('id')), 'search' => ['category' => fn ($q, $v) => $v === 'imaging' ? $q : $q->whereRaw('1 = 0')]],
        ];
    }

    private function status(?string $s): string
    {
        return match ($s) {
            'cancelled' => 'revoked', 'completed', 'reported', 'final' => 'completed', 'pending', 'ordered', 'registered', null => 'active', default => 'active'
        };
    }

    /** @param LabOrderItem|RadiologyOrderItem $m */
    public function toFhir(Model $m): array
    {
        if ($m instanceof LabOrderItem) {
            $order = LabOrder::query()->find($m->lab_order_id);
            $test = LabTest::query()->whereKey($m->test_id)->first(['code', 'name']);

            return array_filter($this->base($m, 'LabOrderItem') + [
                'identifier' => $order ? [['system' => 'urn:healthnexus:lab-order', 'value' => $order->order_number]] : null,
                'status' => $this->status($m->status), 'intent' => 'order',
                'category' => [['coding' => [$this->coding('http://snomed.info/sct', '108252007', 'Laboratory procedure')]]],
                'priority' => match ($m->priority) {
                    'urgent' => 'urgent', 'stat' => 'stat', default => 'routine'
                },
                'code' => ['coding' => array_filter([$test?->code ? $this->coding('urn:healthnexus:lab-test', $test->code, $test->name) : null]), 'text' => $test->name ?? $m->requested_test_name],
                'subject' => $order ? $this->ref('Patient', Patient::class, $order->patient_id) : null,
                'authoredOn' => $this->time($m->requested_at),
            ], fn ($v) => $v !== null && $v !== []);
        }
        $order = RadiologyOrder::query()->find($m->radiology_order_id);

        return array_filter($this->base($m, 'RadiologyOrderItem') + [
            'identifier' => $order ? [['system' => 'urn:healthnexus:rad-order', 'value' => $order->order_number], ['system' => 'urn:healthnexus:accession', 'value' => $order->accession_number]] : null,
            'status' => $this->status($m->status), 'intent' => 'order',
            'category' => [['coding' => [$this->coding('http://snomed.info/sct', '363679005', 'Imaging')]]],
            'priority' => match ($m->priority) {
                'urgent' => 'urgent', 'stat' => 'stat', default => 'routine'
            },
            'code' => ['text' => $m->requested_procedure_name],
            'subject' => $order ? $this->ref('Patient', Patient::class, $order->patient_id) : null,
            'authoredOn' => $this->time($m->requested_at),
            'reasonCode' => $order && $order->clinical_indication ? [['text' => $order->clinical_indication]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** @param LabOrderItem|RadiologyOrderItem $m */
    public function patientIdOf(Model $m): ?int
    {
        return $m instanceof LabOrderItem ? LabOrder::query()->whereKey($m->lab_order_id)->value('patient_id') : RadiologyOrder::query()->whereKey($m->radiology_order_id)->value('patient_id');
    }
}
