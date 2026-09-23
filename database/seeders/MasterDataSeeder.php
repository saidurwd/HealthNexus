<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Gender;
use App\Models\IdentificationType;
use App\Models\MaritalStatus;
use App\Models\PatientType;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedCurrencies();
        $this->seedIdentificationTypes();
        $this->seedGenders();
        $this->seedMaritalStatuses();
        $this->seedPatientTypes();
        $this->seedAppointmentTypes();
    }

    private function seedCountries(): void
    {
        $countries = [
            ['name' => 'Bangladesh', 'code' => 'BD', 'currency_code' => 'BDT', 'currency_symbol' => '৳', 'phone_code' => '+880', 'timezone' => 'Asia/Dhaka', 'date_format' => 'd/m/Y', 'time_format' => 'H:i'],
            ['name' => 'United States', 'code' => 'US', 'currency_code' => 'USD', 'currency_symbol' => '$', 'phone_code' => '+1', 'timezone' => 'America/New_York', 'date_format' => 'm/d/Y', 'time_format' => 'h:i A'],
            ['name' => 'United Kingdom', 'code' => 'GB', 'currency_code' => 'GBP', 'currency_symbol' => '£', 'phone_code' => '+44', 'timezone' => 'Europe/London', 'date_format' => 'd/m/Y', 'time_format' => 'H:i'],
            ['name' => 'India', 'code' => 'IN', 'currency_code' => 'INR', 'currency_symbol' => '₹', 'phone_code' => '+91', 'timezone' => 'Asia/Kolkata', 'date_format' => 'd/m/Y', 'time_format' => 'H:i'],
            ['name' => 'Canada', 'code' => 'CA', 'currency_code' => 'CAD', 'currency_symbol' => 'C$', 'phone_code' => '+1', 'timezone' => 'America/Toronto', 'date_format' => 'Y-m-d', 'time_format' => 'H:i'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['code' => $country['code']], $country);
        }
    }

    private function seedCurrencies(): void
    {
        $currencies = [
            ['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳', 'decimal_places' => 2],
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'decimal_places' => 2],
            ['name' => 'British Pound', 'code' => 'GBP', 'symbol' => '£', 'decimal_places' => 2],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'decimal_places' => 2],
            ['name' => 'Canadian Dollar', 'code' => 'CAD', 'symbol' => 'C$', 'decimal_places' => 2],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'decimal_places' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['code' => $currency['code']], $currency);
        }
    }

    private function seedIdentificationTypes(): void
    {
        $types = [
            ['name' => 'National ID', 'code' => 'NID', 'description' => 'National Identity Card', 'issuing_authority' => 'Government', 'is_primary' => true],
            ['name' => 'Passport', 'code' => 'PASSPORT', 'description' => 'International Passport', 'issuing_authority' => 'Government', 'is_primary' => false],
            ['name' => 'Birth Certificate', 'code' => 'BIRTH_CERT', 'description' => 'Birth Certificate', 'issuing_authority' => 'Local Government', 'is_primary' => false],
            ['name' => 'Driving License', 'code' => 'DL', 'description' => 'Driver License', 'issuing_authority' => 'Transport Authority', 'is_primary' => false],
            ['name' => 'Voter ID', 'code' => 'VOTER_ID', 'description' => 'Voter Identity Card', 'issuing_authority' => 'Election Commission', 'is_primary' => false],
        ];

        foreach ($types as $type) {
            IdentificationType::firstOrCreate(['code' => $type['code']], $type);
        }
    }

    private function seedGenders(): void
    {
        $genders = [
            ['code' => 'male', 'name' => 'Male', 'sort_order' => 1],
            ['code' => 'female', 'name' => 'Female', 'sort_order' => 2],
            ['code' => 'other', 'name' => 'Other', 'sort_order' => 3],
            ['code' => 'unknown', 'name' => 'Unknown', 'sort_order' => 4],
        ];

        foreach ($genders as $gender) {
            Gender::firstOrCreate(['code' => $gender['code']], $gender);
        }
    }

    private function seedMaritalStatuses(): void
    {
        $statuses = [
            ['code' => 'single', 'name' => 'Single', 'sort_order' => 1],
            ['code' => 'married', 'name' => 'Married', 'sort_order' => 2],
            ['code' => 'divorced', 'name' => 'Divorced', 'sort_order' => 3],
            ['code' => 'widowed', 'name' => 'Widowed', 'sort_order' => 4],
            ['code' => 'separated', 'name' => 'Separated', 'sort_order' => 5],
            ['code' => 'unknown', 'name' => 'Unknown', 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            MaritalStatus::firstOrCreate(['code' => $status['code']], $status);
        }
    }

    private function seedPatientTypes(): void
    {
        $types = [
            ['code' => 'general', 'name' => 'General', 'sort_order' => 1],
            ['code' => 'vip', 'name' => 'VIP', 'sort_order' => 2],
            ['code' => 'corporate', 'name' => 'Corporate', 'sort_order' => 3],
            ['code' => 'staff', 'name' => 'Staff', 'sort_order' => 4],
            ['code' => 'insurance', 'name' => 'Insurance', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            PatientType::firstOrCreate(['code' => $type['code']], $type);
        }
    }

    /**
     * company_id left null — these are system-wide defaults every company sees; a hospital can
     * add its own via Administration > Appointment Types without touching these.
     */
    private function seedAppointmentTypes(): void
    {
        $types = [
            ['code' => 'new_consultation', 'name' => 'New Consultation', 'sort_order' => 1],
            ['code' => 'follow_up', 'name' => 'Follow-up', 'is_follow_up_type' => true, 'sort_order' => 2],
            ['code' => 'review', 'name' => 'Review', 'sort_order' => 3],
            ['code' => 'second_opinion', 'name' => 'Second Opinion', 'sort_order' => 4],
            ['code' => 'procedure', 'name' => 'Procedure', 'sort_order' => 5],
            ['code' => 'health_checkup', 'name' => 'Health Checkup', 'sort_order' => 6],
            ['code' => 'referral', 'name' => 'Referral', 'sort_order' => 7],
            ['code' => 'telemedicine', 'name' => 'Telemedicine', 'is_telemedicine_type' => true, 'sort_order' => 8],
            ['code' => 'vaccination', 'name' => 'Vaccination', 'sort_order' => 9],
            ['code' => 'diagnostic', 'name' => 'Diagnostic', 'sort_order' => 10],
            ['code' => 'pre_operative', 'name' => 'Pre-operative', 'sort_order' => 11],
            ['code' => 'post_operative', 'name' => 'Post-operative', 'sort_order' => 12],
            ['code' => 'corporate', 'name' => 'Corporate', 'sort_order' => 13],
            ['code' => 'package', 'name' => 'Package', 'sort_order' => 14],
            ['code' => 'other', 'name' => 'Other', 'sort_order' => 15],
        ];

        foreach ($types as $type) {
            AppointmentType::firstOrCreate(['company_id' => null, 'code' => $type['code']], $type);
        }
    }
}
