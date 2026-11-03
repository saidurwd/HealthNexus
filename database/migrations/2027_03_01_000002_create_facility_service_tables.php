<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Facility and support services (Phase 22), part 2: sterile services, linen, waste, diet, ambulances, the mortuary, visitors. The linen ledger and the gate log only grow (triggers). */
return new class extends Migration
{
    public function up(): void
    {
        // ---- sterile services
        Schema::create('fac_instrument_sets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->unsignedSmallInteger('instrument_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fac_sterilisation_batches', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('asset_id')->nullable()->constrained('fac_assets')->nullOnDelete(); // the steriliser
            $table->string('method', 8); // steam | eto | plasma
            $table->string('status', 10)->default('loading'); // loading | running | passed | failed | released | recalled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->decimal('temperature_c', 5, 1)->nullable();
            $table->decimal('pressure_kpa', 7, 1)->nullable();
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->string('indicator_result', 6)->nullable(); // pass | fail
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->string('recall_reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('fac_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('fac_sterilisation_batches')->cascadeOnDelete();
            $table->foreignId('set_id')->constrained('fac_instrument_sets')->cascadeOnDelete();
            $table->string('label', 40); // the pack's label
            $table->date('use_by')->nullable();
            $table->string('status', 10)->default('packed'); // packed | issued | returned | withdrawn
            $table->unsignedBigInteger('surgery_id')->nullable(); // ot_surgeries.id it was used in
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['batch_id', 'label']);
            $table->index('surgery_id');
        });

        // ---- linen
        Schema::create('fac_linen_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->unsignedInteger('par_level')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fac_linen_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('linen_type_id')->constrained('fac_linen_types')->cascadeOnDelete();
            $table->string('movement', 16); // issue | collect_soiled | send_laundry | receive_clean | receive_new | condemn
            $table->string('location', 80)->nullable(); // the ward or store it concerns
            $table->integer('quantity');
            $table->date('moved_on');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 200)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['organization_id', 'linen_type_id', 'moved_on'], 'fac_linen_type_idx');
        });

        // ---- biomedical waste
        Schema::create('fac_waste_manifests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('contractor', 150);
            $table->string('vehicle_no', 30)->nullable();
            $table->date('collected_on');
            $table->decimal('total_kg', 10, 2)->default(0);
            $table->string('status', 12)->default('prepared'); // prepared | handed_over | confirmed
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_no', 60)->nullable();
            $table->date('disposed_on')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
        });

        Schema::create('fac_waste_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on');
            $table->string('category', 14);
            $table->string('location', 80);
            $table->decimal('weight_kg', 8, 2);
            $table->foreignId('manifest_id')->nullable()->constrained('fac_waste_manifests')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'recorded_on', 'category'], 'fac_waste_day_idx');
        });

        // ---- diet and kitchen
        Schema::create('fac_diet_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('description', 300)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fac_diet_orders', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('admission_id'); // ipd_admissions.id
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->foreignId('diet_type_id')->constrained('fac_diet_types')->cascadeOnDelete();
            $table->string('texture', 14)->default('normal');
            $table->string('allergies', 300)->nullable();
            $table->string('note', 300)->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('status', 8)->default('active'); // active | stopped
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'ward_id'], 'fac_diet_active_idx');
            $table->index('admission_id');
        });

        Schema::create('fac_meal_lists', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('service_date');
            $table->string('meal', 10);
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->string('status', 10)->default('prepared'); // prepared | served
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'service_date', 'meal', 'ward_id'], 'fac_meal_list_unique');
        });

        Schema::create('fac_meal_trays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_list_id')->constrained('fac_meal_lists')->cascadeOnDelete();
            $table->foreignId('diet_order_id')->constrained('fac_diet_orders')->cascadeOnDelete();
            $table->string('status', 8)->default('planned'); // planned | served | refused | held
            $table->string('note', 200)->nullable();
            $table->unique(['meal_list_id', 'diet_order_id']);
        });

        // ---- ambulances
        Schema::create('fac_ambulances', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('registration_no', 30);
            $table->string('type', 10);
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('status', 12)->default('available'); // available | on_trip | maintenance | retired
            $table->unsignedInteger('odometer_km')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'registration_no']);
        });

        Schema::create('fac_ambulance_trips', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('ambulance_id')->nullable()->constrained('fac_ambulances')->nullOnDelete();
            $table->string('kind', 10);
            $table->string('status', 12)->default('requested'); // requested | dispatched | arrived | completed | cancelled
            $table->string('from_place', 150);
            $table->string('to_place', 150);
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ed_visit_id')->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('crew_note', 200)->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->unsignedInteger('odometer_end')->nullable();
            $table->string('cancel_reason', 300)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'requested_at'], 'fac_trip_status_idx');
        });

        Schema::create('fac_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->constrained('fac_ambulances')->cascadeOnDelete();
            $table->date('filled_on');
            $table->decimal('litres', 7, 2);
            $table->decimal('cost', 10, 2);
            $table->unsignedInteger('odometer_km')->nullable();
            $table->timestamps();
        });

        // ---- mortuary
        Schema::create('fac_mortuary_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('fac_mortuary_cases', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // ed_visit | ipd_admission
            $table->unsignedBigInteger('source_id');
            $table->timestamp('died_at');
            $table->string('status', 16)->default('pending'); // pending | received | stored | release_pending | released
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // the second person who checked identity
            $table->string('tag_no', 30)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('fac_mortuary_units')->nullOnDelete();
            $table->boolean('police_case')->default(false);
            $table->boolean('police_cleared')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->string('released_to', 150)->nullable();
            $table->string('relation', 60)->nullable();
            $table->string('id_type', 20)->nullable();
            $table->text('id_number')->nullable(); // encrypted
            $table->foreignId('authorised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete(); // who asked for the release; authorised_by is the second person
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->unique(['organization_id', 'source_type', 'source_id'], 'fac_mortuary_source_unique');
            $table->index(['organization_id', 'status']);
        });

        // ---- visitors and the gate
        Schema::create('fac_visitor_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('reason', 300);
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'active']);
        });

        Schema::create('fac_visitor_passes', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('kind', 10); // visitor | attendant
            $table->string('visitor_name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('relation', 60)->nullable();
            $table->string('id_type', 20)->nullable();
            $table->text('id_number')->nullable(); // encrypted
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admission_id');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->string('status', 10)->default('active'); // active | expired | revoked
            $table->string('revoke_reason', 200)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'valid_until'], 'fac_pass_status_idx');
            $table->index('admission_id');
        });

        Schema::create('fac_gate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pass_id')->nullable()->constrained('fac_visitor_passes')->nullOnDelete();
            $table->string('direction', 3); // in | out
            $table->string('gate', 40)->nullable();
            $table->string('outcome', 10)->default('allowed'); // allowed | refused
            $table->string('note', 200)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['organization_id', 'created_at']);
            $table->index('pass_id');
        });

        $this->protect();
    }

    /** The linen ledger and the gate log only grow — by the application or by raw SQL. */
    private function protect(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        foreach (['fac_linen_movements' => 'the linen ledger only grows: add a correcting movement', 'fac_gate_logs' => 'the gate log only grows'] as $t => $text) {
            foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $k => $op) {
                DB::unprepared($sqlite
                    ? "CREATE TRIGGER {$t}_no_{$k} BEFORE $op ON $t BEGIN SELECT RAISE(ABORT, '$text'); END"
                    : "CREATE TRIGGER {$t}_no_{$k} BEFORE $op ON $t FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$text'; END");
            }
        }
    }

    public function down(): void
    {
        foreach (['fac_linen_movements', 'fac_gate_logs'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_no_update");
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_no_delete");
        }
        foreach (['fac_gate_logs', 'fac_visitor_passes', 'fac_visitor_blocks', 'fac_mortuary_cases', 'fac_mortuary_units', 'fac_fuel_logs', 'fac_ambulance_trips', 'fac_ambulances', 'fac_meal_trays', 'fac_meal_lists', 'fac_diet_orders',
            'fac_diet_types', 'fac_waste_records', 'fac_waste_manifests', 'fac_linen_movements', 'fac_linen_types', 'fac_batch_items', 'fac_sterilisation_batches', 'fac_instrument_sets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
