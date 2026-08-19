<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('ar')->after('vendor_category_id');
        });

        $this->wrapExistingData('venues', ['name', 'address', 'description']);
        $this->wrapExistingData('venue_requests', ['name', 'address', 'description']);
        $this->wrapExistingData('services', ['name', 'description']);
        $this->wrapExistingData('service_categories', ['name', 'description']);
        $this->wrapExistingData('events', ['event_name', 'event_type', 'note']);

        Schema::table('venues', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('address')->change();
        });

        Schema::table('venue_requests', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('address')->nullable()->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->text('event_name')->change();
            $table->text('event_type')->change();
        });
    }

    public function down(): void
    {
        $this->unwrapData('venues', ['name', 'address', 'description']);
        $this->unwrapData('venue_requests', ['name', 'address', 'description']);
        $this->unwrapData('services', ['name', 'description']);
        $this->unwrapData('service_categories', ['name', 'description']);
        $this->unwrapData('events', ['event_name', 'event_type', 'note']);

        Schema::table('venues', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('address', 255)->change();
        });

        Schema::table('venue_requests', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->change();
            $table->string('address', 255)->nullable()->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('event_name', 255)->change();
            $table->string('event_type', 255)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }

    private function wrapExistingData(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            DB::statement("
                UPDATE `{$table}`
                SET `{$column}` = CONCAT('{\"ar\": \"', REPLACE(REPLACE(`{$column}`, '\\\\', '\\\\\\\\'), '\"', '\\\\\"'), '\"}')
                WHERE `{$column}` IS NOT NULL
                AND `{$column}` != ''
                AND NOT JSON_VALID(`{$column}`)
            ");
        }
    }

    private function unwrapData(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            DB::statement("
                UPDATE `{$table}`
                SET `{$column}` = JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.ar'))
                WHERE `{$column}` IS NOT NULL
                AND JSON_VALID(`{$column}`)
            ");
        }
    }
};
