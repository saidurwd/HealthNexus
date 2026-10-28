<?php

namespace Tests\Feature\Portal;

use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalOutbox;
use App\Models\Portal\PortalSecurityEvent;
use App\Services\Portal\PhoneNumber;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PortalAuthTest extends PortalTestCase
{
    private function service(): PortalAuthService
    {
        return app(PortalAuthService::class);
    }

    public function test_phone_numbers_in_any_format_are_one_number(): void
    {
        $this->assertSame('8801712345678', PhoneNumber::normalize('01712-345678'));
        $this->assertSame('8801712345678', PhoneNumber::normalize('+880 1712 345678'));
        $this->assertSame('8801712345678', PhoneNumber::normalize('1712345678'));
        $this->assertNull(PhoneNumber::normalize('12345'));
        $this->assertTrue(PhoneNumber::same('01712345678', '+8801712345678'));
    }

    public function test_registration_needs_the_code_sent_to_the_phone_on_file(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);

        $start = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '+880 1712-345678']);
        $code = $this->lastCode('8801712345678');
        $account = $this->service()->completeRegistration($start['challenge'], $code, 'Strong-Pass-1', true);

        $this->assertSame('active', $account->status);
        $this->assertSame($patient->id, $account->patient_id);
        $this->assertTrue($patient->fresh()->portal_enabled);
        $this->assertNotNull($account->password);
        $this->assertNotSame('Strong-Pass-1', $account->getRawOriginal('password'));
    }

    public function test_a_wrong_date_of_birth_looks_exactly_like_a_right_one_and_sends_nothing(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $good = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        PortalOutbox::query()->delete();
        $this->travel(2)->minutes();

        $bad = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1991-01-01', 'phone' => '01712345678']);
        $unknown = $this->service()->startRegistration(['identifier' => 'EPT-NOBODY', 'date_of_birth' => '1990-04-12', 'phone' => '01799999999']);

        $this->assertSame(array_keys($good), array_keys($bad));
        $this->assertSame(array_keys($good), array_keys($unknown));
        $this->assertSame(0, PortalOutbox::query()->count(), 'no text may go out for a miss');
        $this->expectException(PortalException::class);
        $this->service()->completeRegistration($bad['challenge'], '123456', 'Strong-Pass-1', true);
    }

    public function test_a_different_phone_than_the_one_on_file_gets_no_code(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01899999999']);

        $this->assertSame(0, PortalOutbox::query()->where('template', 'otp')->count());
        $this->assertSame(0, PatientPortalAccount::query()->count());
    }

    public function test_asking_for_codes_too_often_is_refused_the_same_way_for_everyone(): void
    {
        $this->patient(['phone' => '01712345678']);
        $this->service()->startRegistration(['identifier' => 'x', 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);

        $this->expectException(PortalException::class);
        $this->service()->startRegistration(['identifier' => 'x', 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
    }

    public function test_a_wrong_code_is_refused_and_five_wrong_tries_burn_the_code(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $start = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        $right = $this->lastCode('8801712345678');
        $wrong = $right === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->service()->completeRegistration($start['challenge'], $wrong, 'Strong-Pass-1', true);
            } catch (PortalException) {
            }
        }
        $this->expectException(PortalException::class);
        $this->service()->completeRegistration($start['challenge'], $right, 'Strong-Pass-1', true);
    }

    public function test_an_expired_code_does_not_work(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $start = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        $code = $this->lastCode('8801712345678');
        $this->travel(11)->minutes();

        $this->expectException(PortalException::class);
        $this->service()->completeRegistration($start['challenge'], $code, 'Strong-Pass-1', true);
    }

    public function test_a_weak_password_and_unaccepted_terms_are_refused(): void
    {
        $patient = $this->patient(['phone' => '01712345678']);
        $start = $this->service()->startRegistration(['identifier' => $patient->enterprise_patient_no, 'date_of_birth' => '1990-04-12', 'phone' => '01712345678']);
        $code = $this->lastCode('8801712345678');

        try {
            $this->service()->completeRegistration($start['challenge'], $code, 'short', true);
            $this->fail('weak password accepted');
        } catch (ValidationException) {
        }
        $this->expectException(PortalException::class);
        $this->service()->completeRegistration($start['challenge'], $code, 'Strong-Pass-1', false);
    }

    public function test_a_young_person_cannot_register_alone_and_the_dead_cannot_register(): void
    {
        $child = $this->patient(['phone' => '01712345678', 'date_of_birth' => now()->subYears(10)->toDateString()]);
        $this->service()->startRegistration(['identifier' => $child->enterprise_patient_no, 'date_of_birth' => $child->date_of_birth->toDateString(), 'phone' => '01712345678']);
        $this->assertSame(0, PortalOutbox::query()->count());
    }

    public function test_a_front_desk_invitation_activates_with_the_date_of_birth(): void
    {
        $patient = $this->patient(['phone' => '01800000000']);
        [$account, $code] = $this->service()->invite($patient);

        try {
            $this->service()->claimInvitation($code, '1999-01-01', 'Strong-Pass-1', true);
            $this->fail('wrong date of birth accepted');
        } catch (PortalException) {
        }
        $active = $this->service()->claimInvitation($code, '1990-04-12', 'Strong-Pass-1', true);
        $this->assertSame('active', $active->status);
        $this->assertSame($account->id, $active->id);

        $this->expectException(PortalException::class);
        $this->service()->claimInvitation($code, '1990-04-12', 'Strong-Pass-1', true); // a code works once
    }

    public function test_login_from_a_new_device_needs_a_code_and_a_known_device_does_not(): void
    {
        $account = $this->account($this->patient(['phone' => '01712345678']));

        $first = $this->service()->attempt('01712345678', 'Portal-Pass-123', 'device-a');
        $this->assertTrue($first['otp_required']);
        $signedIn = $this->service()->completeLoginOtp($first['challenge'], $this->lastCode('8801712345678'));
        $this->assertSame($account->id, $signedIn->id);
        $this->service()->registerDevice($signedIn, 'device-a', 'Phone', 'android', false);

        $again = $this->service()->attempt('+8801712345678', 'Portal-Pass-123', 'device-a');
        $this->assertFalse($again['otp_required']);
        $this->assertSame($account->id, $again['account']->id);
    }

    public function test_wrong_passwords_lock_the_phone_number_after_five_tries(): void
    {
        $this->account($this->patient(['phone' => '01712345678']));

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->service()->attempt('01712345678', 'nope-'.$i, 'device-a');
            } catch (PortalException $e) {
                $this->assertSame(422, $e->status);
            }
        }
        try {
            $this->service()->attempt('01712345678', 'Portal-Pass-123', 'device-a');
            $this->fail('the right password must not work while locked');
        } catch (PortalException $e) {
            $this->assertSame(429, $e->status);
        }
        $this->assertTrue(PatientPortalAccount::query()->first()->isLocked());

        $this->travel(16)->minutes();
        PatientPortalAccount::query()->update(['locked_until' => null, 'status' => 'active']);
        $this->assertNotNull($this->service()->attempt('01712345678', 'Portal-Pass-123', 'device-a'));
    }

    public function test_an_unknown_phone_gets_the_same_failure_as_a_wrong_password(): void
    {
        $this->account($this->patient(['phone' => '01712345678']));
        $messages = [];
        foreach (['01712345678', '01799999999'] as $phone) {
            try {
                $this->service()->attempt($phone, 'wrong-pass-1', 'device-a');
            } catch (PortalException $e) {
                $messages[] = [$e->getMessage(), $e->status];
            }
        }
        $this->assertSame($messages[0], $messages[1]);
    }

    public function test_a_suspended_login_cannot_sign_in(): void
    {
        $account = $this->account($this->patient(['phone' => '01712345678']));
        $this->service()->setStatus($account, 'suspended', 'misuse');

        $this->expectException(PortalException::class);
        $this->service()->attempt('01712345678', 'Portal-Pass-123', 'device-a');
    }

    public function test_revoking_a_device_deletes_its_token(): void
    {
        $account = $this->account();
        [$device, $plain] = $this->service()->registerDevice($account, 'device-a', 'Phone', 'android', true);
        $this->assertNotNull($plain);
        $this->assertSame(1, $account->tokens()->count());
        $this->assertSame(['portal'], $account->tokens()->first()->abilities);

        $this->service()->revokeDevice($account, $device);
        $this->assertSame(0, $account->tokens()->count());
        $this->assertNotNull($device->fresh()->revoked_at);
    }

    public function test_password_reset_signs_every_device_out(): void
    {
        $account = $this->account($this->patient(['phone' => '01712345678']));
        $this->service()->registerDevice($account, 'device-a', 'Phone', 'android', true);

        $start = $this->service()->startReset('01712345678');
        $this->service()->completeReset($start['challenge'], $this->lastCode('8801712345678'), 'Brand-New-Pass-9');

        $this->assertSame(0, $account->tokens()->count());
        $this->assertTrue(Hash::check('Brand-New-Pass-9', $account->fresh()->password));
        $this->assertGreaterThan(0, PortalSecurityEvent::query()->where('kind', 'password_reset')->count());
    }
}
