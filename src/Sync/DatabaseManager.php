<?php

namespace Sync;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Класс DatabaseManager
 *
 * Класс для иньекции зависимости в контейнер
 */
class DatabaseManager
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
