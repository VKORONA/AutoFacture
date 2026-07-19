<?php

use Crater\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRelationshipOfCompany extends Migration
{
    public function up()
    {
        foreach (User::all() as $user) {
            if ($user->company_id) {
                $user->companies()->syncWithoutDetaching([$user->company_id]);
                $user->company_id = null;
                $user->save();
            }
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }

    public function down()
    {
        // Migration historique non réversible.
    }
}
