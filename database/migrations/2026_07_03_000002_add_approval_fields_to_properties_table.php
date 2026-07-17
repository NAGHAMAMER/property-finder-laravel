<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->index()->after('status');
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        // العقارات الموجودة قبل إضافة نظام الموافقات تبقى ظاهرة، أما العقارات الجديدة فتبدأ pending.
        DB::table('properties')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'rejection_reason', 'reviewed_at']);
        });
    }
};
