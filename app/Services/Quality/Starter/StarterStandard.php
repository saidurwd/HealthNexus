<?php

namespace App\Services\Quality\Starter;

/**
 * A plain starter checklist and privilege list, written for this product. They are examples to be replaced by the hospital's own licensing or accreditation requirements:
 * no accrediting body's text, numbering or name is bundled.
 */
final class StarterStandard
{
    public const CODE = 'HOSP-BASIC';

    public const NAME = 'Hospital basic safety checklist (example)';

    public const EDITION = 'Example — replace with your licensing or accreditation requirements';

    /** @return list<array{code:string,chapter:string,requirement:string}> */
    public static function items(): array
    {
        $rows = [
            ['Patients', ['Two identifiers are checked before any procedure, medicine, blood product or sample.', 'Informed consent is recorded before procedures and operations.', 'Patients are told how to make a complaint and the process is displayed.']],
            ['Medicines', ['High-alert medicines are stored apart and checked by a second person.', 'Look-alike and sound-alike medicines are separated or labelled.', 'Medicine errors and near misses are reported and reviewed.']],
            ['Infection control', ['Hand-hygiene supplies are at every point of care.', 'Isolation precautions are marked and followed.', 'Biomedical waste is separated at the source and recorded.']],
            ['Surgery and procedures', ['A safety checklist is used for every operation.', 'Instruments are sterilised in recorded batches that can be traced to a patient.']],
            ['Emergency and facilities', ['Fire drills are held at least twice a year and recorded.', 'Equipment has a maintenance schedule and calibration records.']],
            ['Staff and records', ['Staff licences are checked and kept current.', 'Clinical records are complete soon after discharge.', 'Policies are reviewed on schedule and staff have read them.', 'Incidents are reviewed regularly by a committee and actions are followed up.']],
        ];
        $out = [];
        $n = 0;
        foreach ($rows as [$chapter, $items]) {
            foreach ($items as $text) {
                $out[] = ['code' => sprintf('B-%02d', ++$n), 'chapter' => $chapter, 'requirement' => $text];
            }
        }

        return $out;
    }

    /** @return list<array{0:string,1:string,2:?string,3:int}> code, name, required licence, validity in months */
    public static function privileges(): array
    {
        return [['SURG-GEN', 'General surgery', 'bmdc', 24], ['ANAES', 'Anaesthesia', 'bmdc', 24], ['C-SECTION', 'Caesarean section', 'bmdc', 24], ['ENDOSCOPY', 'Endoscopy', 'bmdc', 24], ['IV-CHEMO', 'Giving chemotherapy', 'bnmc', 12], ['ICU-VENT', 'Managing a ventilator', null, 12]];
    }
}
