<?php

namespace App\Contracts\Integration;

use Carbon\CarbonInterface;

/**
 * Attendance devices (fingerprint, face, card readers). A real adapter pulls what a device recorded since a time and hands it to
 * `PunchIngestService`; devices that cannot be reached are simply skipped and caught up on the next run (guideline P-22). Module key: hr.
 */
interface BiometricGateway
{
    /** @return IntegrationResult handled: data['punches'] = list of ['code' => device user number, 'at' => ISO time] */
    public function pullPunches(string $deviceCode, CarbonInterface $since): IntegrationResult;
}
