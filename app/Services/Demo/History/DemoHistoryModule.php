<?php

namespace App\Services\Demo\History;

/**
 * One module's share of the demo history (AI-RULES 16). It builds six months of records THROUGH THE MODULE'S OWN SERVICES (so charges,
 * stock movements, alerts and audit rows are real), stepping the clock with HistoryContext::at(). Each phase adds its own class here and
 * lists it in config/demo_history.php.
 */
interface DemoHistoryModule
{
    /** Short name shown in the command output. */
    public function name(): string;

    /** Key in config/modules.php: the class is skipped when that module is switched off for the organization. */
    public function module(): string;

    /** @return string one line, e.g. "412 encounters" */
    public function run(HistoryContext $context): string;
}
