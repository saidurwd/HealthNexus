<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\IdentificationType;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedCurrencies();
        $this->seedIdentificationTypes();
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
}
