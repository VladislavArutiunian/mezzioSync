<?php

namespace Sync;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseManager // TODO: PHPDocs
{
    /**
     * Returnes instance for container
     *
     * @param array $config
     * @return Capsule
     */
    public function init(array $config): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }
}
