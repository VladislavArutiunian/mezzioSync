<?php

namespace Sync\Service;

use Pheanstalk\Pheanstalk;

class QueueService
{
    private Pheanstalk $connection;

    public function __construct(Pheanstalk $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Pheanstalk
     */
    public function getConnection(): Pheanstalk
    {
        return $this->connection;
    }
}