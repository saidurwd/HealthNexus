<?php

namespace Tests\Feature\Ux;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Every dropdown in the product is searchable (owner instruction 2026-11-02; Tom Select, copied into public/vendor so it works without internet). The behaviour itself is proved in a real browser by
 * `npm run test:browser` (tests/Js/searchable-select.mjs); this test proves the pieces are in place and that every kind of page loads them.
 */
class SearchableSelectTest extends TestCase
{
    use RefreshDatabase;

    private const JS = 'vendor/tom-select/tom-select.complete.min.js';

    private const CSS = 'vendor/tom-select/tom-select.bootstrap5.min.css';

    public function test_the_library_is_in_the_repository_unchanged_with_its_licence(): void
    {
        $this->assertSame('28d785eb15b9a3fb56d6869ee57952e0908d003a0cf911eaae7a14a8bea9bc76', hash_file('sha256', public_path(self::JS)), 'Tom Select 2.3.1 script is as published');
        $this->assertSame('e0cc069600681c900b5e3b3660a65be2c32a852c3bfb23329870286b47302461', hash_file('sha256', public_path(self::CSS)), 'and its Bootstrap 5 theme');
        $this->assertStringContainsString('Apache License', (string) file_get_contents(public_path('vendor/tom-select/LICENSE.txt')));
        $this->assertStringContainsString('2.3.1', (string) file_get_contents(public_path('vendor/tom-select/README.txt')));
        foreach (['js/searchable-select.js', 'css/searchable-select.css'] as $own) {
            $this->assertFileExists(public_path($own));
        }
    }

    public function test_nothing_is_loaded_from_the_internet(): void
    {
        $js = (string) file_get_contents(public_path('js/searchable-select.js')).(string) file_get_contents(public_path(self::JS));
        $this->assertSame(0, preg_match('~(?<!w3\.org/)https?://(?!www\.w3\.org)~', $js), 'no web address in the scripts');
        $plugin = config('adminlte.plugins.TomSelect');
        $this->assertTrue($plugin['active'], 'switched on for every AdminLTE page');
        foreach ($plugin['files'] as $file) {
            $this->assertTrue($file['asset'], 'a local file, not a CDN: '.$file['location']);
            $this->assertFileExists(public_path($file['location']));
        }
        $this->assertSame([self::JS, 'js/searchable-select.js'], collect($plugin['files'])->where('type', 'js')->pluck('location')->values()->all(), 'the library first, then our script');
    }

    public function test_the_script_knows_the_words_in_both_languages_and_how_to_opt_out(): void
    {
        $js = (string) file_get_contents(public_path('js/searchable-select.js'));
        $this->assertStringContainsString('No matches found', $js);
        $this->assertStringContainsString('কোনো মিল পাওয়া যায়নি', $js);
        $this->assertStringContainsString('data-plain', $js);
    }

    public function test_a_staff_page_loads_the_library_and_the_script_in_that_order_and_the_style(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $org = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $org->id]);
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $org->id]);
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $org->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($org->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(self::CSS, $html);
        $this->assertStringContainsString('css/searchable-select.css', $html);
        $lib = strpos($html, self::JS);
        $own = strpos($html, 'js/searchable-select.js');
        $this->assertNotFalse($lib);
        $this->assertNotFalse($own);
        $this->assertLessThan($own, $lib, 'the library is loaded before the script that uses it');
    }

    public function test_the_sign_in_pages_load_it_too_so_the_hospital_and_branch_choice_is_searchable(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('js/searchable-select.js', $html);
        $this->assertStringContainsString('vendor/tom-select/tom-select.complete.min.js', $html);
        $this->assertStringContainsString('id="organization_id"', (string) file_get_contents(resource_path('views/auth/context.blade.php')), 'the choice of hospital is a dropdown on a page of the same layout');
    }

    public function test_the_patient_portal_pages_load_it_as_well(): void
    {
        $layout = (string) file_get_contents(resource_path('views/portal/layout.blade.php'));
        $this->assertStringContainsString('vendor/tom-select/tom-select.complete.min.js', $layout);
        $this->assertStringContainsString('js/searchable-select.js', $layout);
        $this->assertStringContainsString('vendor/tom-select/tom-select.bootstrap5.min.css', $layout);
        $this->assertStringContainsString('css/searchable-select.css', $layout);
    }

    public function test_no_screen_switches_the_search_off_without_a_reason_recorded_here(): void
    {
        // A dropdown is opted out with data-plain. Today none is: list any that is, with why, in this array.
        $allowed = [];
        $found = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php') && str_contains((string) file_get_contents($file->getPathname()), 'data-plain')) {
                $found[] = str_replace(resource_path('views').'/', '', $file->getPathname());
            }
        }
        $this->assertEqualsCanonicalizing($allowed, $found, 'a screen that opts out of the dropdown search must be listed (with its reason) in this test');
    }
}
