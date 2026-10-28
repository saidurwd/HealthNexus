<?php

use App\Contracts\Integration\AiProviderGateway;
use App\Contracts\Integration\BiometricGateway;
use App\Contracts\Integration\BloodRequestGateway;
use App\Contracts\Integration\FinanceGateway;
use App\Contracts\Integration\NationalExchangeGateway;
use App\Contracts\Integration\PayerGateway;
use App\Integrations\Null\NullAiProviderGateway;
use App\Integrations\Null\NullBiometricGateway;
use App\Integrations\Null\NullBloodRequestGateway;
use App\Integrations\Null\NullFinanceGateway;
use App\Integrations\Null\NullNationalExchangeGateway;
use App\Integrations\Null\NullPayerGateway;

/*
| Integration Stub Registry (guideline H-01). Each gateway is bound to its Null adapter until the
| owning phase registers a real implementation (e.g. Phase 13 rebinds BloodRequestGateway).
*/
return [
    'gateways' => [
        BloodRequestGateway::class => NullBloodRequestGateway::class,
        FinanceGateway::class => NullFinanceGateway::class,
        PayerGateway::class => NullPayerGateway::class,
        NationalExchangeGateway::class => NullNationalExchangeGateway::class,
        AiProviderGateway::class => NullAiProviderGateway::class,
        BiometricGateway::class => NullBiometricGateway::class,
    ],
];
