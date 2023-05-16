<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use Sync\Model\Account;

class CreateTableIntegrations extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        Capsule::schema()->create('integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignIdFor(Account::class, 'account_id');
            $table->string('client_id');
            $table->string('secret_key');
            $table->string('url');
            $table->timestamps();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('integrations');
    }
}
