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
            $table->string('login_code')->nullable()->after('password');
            $table->boolean('active')->default(true)->after('login_code');
            $table->boolean('can_access_billing')->default(false)->after('active');
            $table->boolean('can_manage_users')->default(false)->after('can_access_billing');
            $table->string('default_area', 20)->default('billing')->after('can_manage_users');
        });

        // Preserve access for users that existed before Books permissions were added.
        DB::table('users')->update([
            'can_access_billing' => true,
            'can_manage_users' => false,
            'default_area' => 'billing',
        ]);

        // The existing ClientBill login was explicitly tied to this owner account.
        DB::table('users')
            ->where('email', 'fudge@jordan.net.au')
            ->update(['can_manage_users' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_code',
                'active',
                'can_access_billing',
                'can_manage_users',
                'default_area',
            ]);
        });
    }
};
