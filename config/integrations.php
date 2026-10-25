<?php

/*
| Integration Stub Registry (guideline H-01). Each gateway is bound to its Null adapter until the
| owning phase registers a real implementation (e.g. Phase 13 rebinds BloodRequestGateway).
*/
return [
    'gateways' => [
        \App\Contracts\Integration\BloodRequestGateway::class => \App\Integrations\Null\NullBloodRequestGateway::class,
        \App\Contracts\Integration\FinanceGateway::class => \App\Integrations\Null\NullFinanceGateway::class,
        \App\Contracts\Integration\PayerGateway::class => \App\Integrations\Null\NullPayerGateway::class,
        \App\Contracts\Integration\NationalExchangeGateway::class => \App\Integrations\Null\NullNationalExchangeGateway::class,
        \App\Contracts\Integration\AiProviderGateway::class => \App\Integrations\Null\NullAiProviderGateway::class,
    ],
];
