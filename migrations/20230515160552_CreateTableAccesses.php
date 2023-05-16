<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use Sync\Model\Account;

class CreateTableAccesses extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('accesses', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignIdFor(Account::class, 'account_id');
            $table->json('kommo_access_token');
            $table->string('unisender_api_key');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('accesses');
    }
}
