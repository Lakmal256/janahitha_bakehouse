<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tables whose rows the browser app addresses by `source_key`.
     *
     * @var list<string>
     */
    private array $keyedTables = ['users', 'routes', 'shops', 'items'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('source_key')->nullable()->unique()->after('id');
            $table->string('legacy_password_hash')->nullable()->after('password');
            $table->string('legacy_password_salt')->nullable()->after('legacy_password_hash');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('shop_name')->nullable()->after('order_id');
            $table->string('pay_method')->nullable()->after('paid');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->timestamp('generated_at')->nullable()->after('paid_at');
            $table->timestamp('edited_at')->nullable()->after('generated_at');
        });

        foreach ($this->keyedTables as $table) {
            DB::table($table)->whereNull('source_key')->lazyById()->each(function (object $row) use ($table): void {
                DB::table($table)->where('id', $row->id)->update(['source_key' => Str::lower((string) Str::ulid())]);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Rolling back drops the payment details and legacy password hashes stored in these columns.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['shop_name', 'pay_method', 'paid_at', 'generated_at', 'edited_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'legacy_password_hash', 'legacy_password_salt']);
        });
    }
};
