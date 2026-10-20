
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'member_code')) {
                $table->string('member_code')->unique();
            }

            if (!Schema::hasColumn('members', 'name')) {
                $table->string('name');
            }

            if (!Schema::hasColumn('members', 'email')) {
                $table->string('email')->unique();
            }

            if (!Schema::hasColumn('members', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (!Schema::hasColumn('members', 'address')) {
                $table->text('address')->nullable();
            }

            if (!Schema::hasColumn('members', 'position')) {
                $table->string('position')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Tidak menghapus kolom agar data tetap aman.
    }
};
