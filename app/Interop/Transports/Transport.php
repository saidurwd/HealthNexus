<?php

namespace App\Interop\Transports;

use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;

interface Transport
{
    public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult;
}
