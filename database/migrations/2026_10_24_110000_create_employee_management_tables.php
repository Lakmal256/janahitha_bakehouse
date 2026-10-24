<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('source_key')->nullable()->unique();
            $table->string('name');
            $table->string('phone', 50);
            $table->string('location');
            $table->timestamps();
            $table->index('name');
            $table->index('location');
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->string('source_key')->nullable()->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('request_date');
            $table->string('salary_month', 7);
            $table->string('status', 30)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'salary_month', 'status']);
            $table->index(['salary_month', 'status']);
            $table->index('request_date');
        });

        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->string('source_key')->nullable()->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('leave_type', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'start_date']);
            $table->index(['status', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('employees');
    }
};
