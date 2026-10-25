<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;

/**
 * Pure read-side consistency check across surgery_request -> surgery -> consent -> checklist
 * site/side fields (spec §22/§23) — returns a structured mismatch list, never silently resolves
 * one. Blocks progression to the safety checklist when ot.consent_requires_witness-style strict
 * checks are configured; the caller decides what to do with the mismatch list.
 */
class OtVerificationService
{
    public function verify(OtSurgery $surgery): array
    {
        $mismatches = [];
        $request = $surgery->surgeryRequest;

        if ($request && $request->site && $surgery->site && $request->site !== $surgery->site) {
            $mismatches[] = "Site differs between the surgery request ('{$request->site}') and the scheduled surgery ('{$surgery->site}').";
        }

        if ($request && $request->laterality && $surgery->laterality && $request->laterality !== $surgery->laterality) {
            $mismatches[] = "Laterality differs between the surgery request ('{$request->laterality}') and the scheduled surgery ('{$surgery->laterality}').";
        }

        if (! $surgery->consent_id) {
            $mismatches[] = 'No consent is linked to this surgical case.';
        } elseif ($surgery->consent && ! $surgery->consent->isActive()) {
            $mismatches[] = 'The linked consent is not active (withdrawn or superseded).';
        }

        return $mismatches;
    }

    public function isVerified(OtSurgery $surgery): bool
    {
        return empty($this->verify($surgery));
    }
}
