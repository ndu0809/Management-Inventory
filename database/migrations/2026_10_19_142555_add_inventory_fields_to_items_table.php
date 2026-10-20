
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'status')) {
                $table->string('status')->default('Baik');
            }

            if (! Schema::hasColumn('items', 'assigned')) {
                $table->string('assigned')->nullable();
            }

            if (! Schema::hasColumn('items', 'department')) {
                $table->string('department')->nullable();
            }

            if (! Schema::hasColumn('items', 'serial_number')) {
                $table->string('serial_number')->nullable();
            }

            if (! Schema::hasColumn('items', 'specification')) {
                $table->text('specification')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $columns = [
                'status',
                'assigned',
                'department',
                'serial_number',
                'specification',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
