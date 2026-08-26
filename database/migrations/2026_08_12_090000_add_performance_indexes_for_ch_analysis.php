<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(
                ['patient_id', 'therapy_id', 'service_type_id', 'appointment_date'],
                'appointments_apuracao_ch_index'
            );

            $table->index('appointment_date', 'appointments_appointment_date_index');
            $table->index('professional_id', 'appointments_professional_id_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index(['unit_id', 'deleted_at'], 'patients_unit_deleted_index');
        });

        Schema::table('requested_services', function (Blueprint $table) {
            $table->index(['patient_id', 'therapy_id', 'service_type_id'], 'requested_services_apuracao_index');
            $table->index('month_year', 'requested_services_month_year_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_apuracao_ch_index');
            $table->dropIndex('appointments_appointment_date_index');
            $table->dropIndex('appointments_professional_id_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_unit_deleted_index');
        });

        Schema::table('requested_services', function (Blueprint $table) {
            $table->dropIndex('requested_services_apuracao_index');
            $table->dropIndex('requested_services_month_year_index');
        });
    }
};
