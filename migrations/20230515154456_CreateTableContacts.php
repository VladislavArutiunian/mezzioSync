<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use Sync\Model\Account;

class CreateTableContacts extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignIdFor(Account::class, 'account_id');
            $table->integer('kommo_contact_id');
            $table->json('emails');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('contacts');
    }
}
