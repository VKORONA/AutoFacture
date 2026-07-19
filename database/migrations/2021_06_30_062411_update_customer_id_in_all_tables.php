<?php

use Crater\Models\Address;
use Crater\Models\Customer;
use Crater\Models\CustomField;
use Crater\Models\CustomFieldValue;
use Crater\Models\Estimate;
use Crater\Models\Expense;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Crater\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UpdateCustomerIdInAllTables extends Migration
{
    public function up()
    {
        $users = User::where('role', 'customer')->get();
        $users->makeVisible('password', 'remember_token');

        foreach ($users as $user) {
            $newCustomer = Customer::create($user->toArray());

            Address::where('user_id', $user->id)->update([
                'customer_id' => $newCustomer->id,
                'user_id' => null,
            ]);
            Expense::where('user_id', $user->id)->update([
                'customer_id' => $newCustomer->id,
                'user_id' => null,
            ]);
            Estimate::where('user_id', $user->id)->update([
                'customer_id' => $newCustomer->id,
                'user_id' => null,
            ]);
            Invoice::where('user_id', $user->id)->update([
                'customer_id' => $newCustomer->id,
                'user_id' => null,
            ]);
            Payment::where('user_id', $user->id)->update([
                'customer_id' => $newCustomer->id,
                'user_id' => null,
            ]);

            CustomFieldValue::where('custom_field_valuable_id', $user->id)
                ->where('custom_field_valuable_type', 'Crater\\Models\\User')
                ->update([
                    'custom_field_valuable_type' => 'Crater\\Models\\Customer',
                    'custom_field_valuable_id' => $newCustomer->id,
                ]);
        }

        foreach (CustomField::where('model_type', 'User')->get() as $customField) {
            $customField->model_type = 'Customer';
            $customField->slug = Str::upper(
                'CUSTOM_'.$customField->model_type.'_'.Str::slug($customField->label, '_')
            );
            $customField->save();
        }

        /*
         * Laravel 12 reconstruit les tables SQLite pour supprimer une colonne.
         * Les anciennes clés étrangères Crater rendent cette reconstruction
         * invalide. SQLite n'est utilisé que pour les tests : on y conserve
         * ces colonnes historiques, tandis que MySQL/MariaDB les supprime.
         */
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            foreach (['estimates', 'expenses', 'invoices', 'payments'] as $tableName) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                });
            }
        }

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('unit');
        });

        User::where('role', 'customer')->delete();
    }

    public function down()
    {
        // Migration historique non réversible.
    }
}
