<?php

namespace App\Thrift\Clients;

use App\Thrift\Client;
use Xin\Thrift\MysqlService\MysqlClient as MysqlServiceClient;

class MysqlClient extends Client
{
    protected $host = '127.0.0.1';

    protected $port = '10086';

    protected $service = 'mysql';

    protected $clientName = MysqlServiceClient::class;

    protected $recvTimeoutMilliseconds = 5000;

    protected $sendTimeoutMilliseconds;

    /**
     * @desc
     * @author limx
     * @param array $config
     * @return MysqlServiceClient
     */
    public static function getInstance($config = [])
    {
        return parent::getInstance($config);
    }


}

