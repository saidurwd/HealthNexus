<?php

namespace Tests\Feature\Dashboard;

use PHPUnit\Framework\Attributes\DataProvider;

/** The Stores, People, Patient relations, Portal, Connections, Facility and Quality pages on the dashboard standard. */
class SupportBoardsTest extends FrontDoorTestCase
{
    /** @return array<string, array{0:string,1:string,2:string,3:string,4:string}> */
    public static function boards(): array
    {
        return [
            'stores' => ['/inventory', 'inventory.dashboard.view', 'inventory.item.view', 'stocked', 'Value of stock held'],
            'people' => ['/hr', 'hr.dashboard.view', 'hr.employee.view', 'absent', 'People working here'],
            'patient relations' => ['/crm', 'crm.dashboard.view', 'crm.interaction.view', 'heard', 'Complaints open'],
            'portal' => ['/portal', 'portal.dashboard.view', 'portal.account.view', 'portal being used', 'Patients with a login'],
            'connections' => ['/interop', 'interoperability.dashboard.view', 'interoperability.message.view', 'delivering', 'Partners switched on'],
            'facility' => ['/facility', 'facility.dashboard.view', 'facility.asset.view', 'keeping the wards running', 'Equipment in the register'],
            'quality' => ['/quality', 'quality.dashboard.view', 'quality.incident.view', 'learning from incidents', 'Open incidents'],
        ];
    }

    #[DataProvider('boards')]
    public function test_each_page_states_its_question_and_the_time_of_its_figures(string $url, string $right, string $other, string $question, string $tile): void
    {
        $html = $this->assertDashboardStatesItself($url, $this->actor([$right]));

        $this->assertStringContainsString($question, $html);
        $this->assertStringContainsString($tile, $html);
    }

    #[DataProvider('boards')]
    public function test_a_person_without_the_dashboard_right_is_refused(string $url, string $right, string $other, string $question, string $tile): void
    {
        $this->assertDashboardRefuses($url, $this->actor([$other]));
    }

    public function test_a_second_visit_is_served_from_the_cache_and_a_refresh_works_it_out_again(): void
    {
        $user = $this->actor(['inventory.dashboard.view']);
        $first = $this->as($user)->get('/inventory')->assertOk()->getContent();
        $this->filled('inventory_items', ['is_active' => true, 'deleted_at' => null]);

        $this->assertSame($first, $this->as($user)->get('/inventory')->getContent(), 'the figures are kept until someone asks for fresh ones');
        $this->assertNotSame($first, $this->as($user)->get('/inventory?fresh=1')->assertOk()->getContent());
    }
}
