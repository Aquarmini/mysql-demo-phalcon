<?php

namespace App\Tasks\Mysql;

use App\Tasks\Task;
use App\Thrift\Clients\MysqlClient;
use Xin\Cli\Color;
use swoole_process;

class TransTask extends Task
{
    public function mainAction()
    {
        echo Color::head('Help:') . PHP_EOL;
        echo Color::colorize('  事务测试') . PHP_EOL . PHP_EOL;

        echo Color::head('Usage:') . PHP_EOL;
        echo Color::colorize('  php run mysql:trans@[action]', Color::FG_LIGHT_GREEN) . PHP_EOL . PHP_EOL;

        echo Color::head('Actions:') . PHP_EOL;
        echo Color::colorize('  save        保存测试', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  notsave     不保存测试', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  run         并发测试', Color::FG_LIGHT_GREEN) . PHP_EOL;
    }

    public function runAction()
    {
        for ($i = 0; $i < 2; $i++) {
            $process = new swoole_process([$this, 'run']);
            $process->write($i);
            $pid = $process->start();
        }
        swoole_process::wait();
    }

    public function run(swoole_process $worker)
    {
        $client = MysqlClient::getInstance();
        $recv = $worker->read();
        if ($recv == 1) {
            for ($i = 0; $i < 10; $i++) {
                $res = $client->notSave();
            }
        } else {
            for ($i = 0; $i < 10; $i++) {
                $res = $client->save();
            }
        }
    }

    public function saveAction()
    {
        $client = MysqlClient::getInstance();
        $res = $client->save();
    }

    public function notsaveAction()
    {
        $client = MysqlClient::getInstance();
        $res = $client->notSave();
    }
}

