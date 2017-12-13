<?php

namespace App\Tasks\Mysql;

use App\Models\Test;
use App\Models\Test1;
use App\Models\Test2;
use App\Models\User;
use App\Tasks\Task;
use Phalcon\Db\Adapter;
use Xin\Cli\Color;

class MultiTask extends Task
{
    public function mainAction()
    {
        echo Color::head('Help:') . PHP_EOL;
        echo Color::colorize('  多数据库测试') . PHP_EOL . PHP_EOL;

        echo Color::head('Usage:') . PHP_EOL;
        echo Color::colorize('  php run mysql:multi@[action]', Color::FG_LIGHT_GREEN) . PHP_EOL . PHP_EOL;

        echo Color::head('Actions:') . PHP_EOL;
        echo Color::colorize('  save          保存测试age偶数连接db2,奇数连接db', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  bsave         并发保存测试age偶数连接db2,奇数连接db', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  read          根据age分别读取数据', Color::FG_LIGHT_GREEN) . PHP_EOL;

        echo Color::colorize('  save2         分模型 保存测试age偶数连接db2,奇数连接db', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  bsave2        分模型 并发保存测试age偶数连接db2,奇数连接db', Color::FG_LIGHT_GREEN) . PHP_EOL;
        echo Color::colorize('  read2         分模型 根据age分别读取数据', Color::FG_LIGHT_GREEN) . PHP_EOL;


    }

    public function getConnectionByAge($age)
    {
        if ($age % 2 === 0) {
            return 'db2';
        }
        return 'db';
    }

    public function getConnectionIdByAge($age)
    {
        if ($age % 2 === 0) {
            return '2';
        }
        return '';
    }

    public function delete()
    {
        $sql = 'DELETE FROM test;';
        /** @var Adapter $db */
        $db = di('db');
        $r1 = $db->execute($sql);
        $db = di('db2');
        $r2 = $db->execute($sql);

        return $r1 && $r2;
    }

    public function saveAction()
    {
        $age = rand(1, 100);
        $test = new Test();
        $test->setWriteConnectionService($this->getConnectionByAge($age));
        $test->name = 'save';
        $test->age = $age;
        $test->save();
    }

    public function save2Action()
    {
        $age = rand(1, 100);
        $id = $this->getConnectionIdByAge($age);
        $class = '\\App\\Models\\Test' . $id;
        /** @var Test1 $test */
        $test = new $class();
        $test->name = 'save2';
        $test->age = $age;
        $test->save();
    }

    public function bsaveAction()
    {
        $age = rand(1, 100);
        $test = new Test();
        $test->setWriteConnectionService($this->getConnectionByAge($age));
        $test->name = 'bsave';
        $test->age = $age;

        $age = rand(1, 100);
        $test2 = new Test();
        $test2->setWriteConnectionService($this->getConnectionByAge($age));
        $test2->name = 'bsave';
        $test2->age = $age;

        $test->save();
        $test2->save();

        echo Color::colorize('当设置T1模型连接为db，设置T2模型连接为db2时，T1模型的连接也会被重置为db2', Color::FG_LIGHT_RED) . PHP_EOL;
    }

    public function bsave2Action()
    {
        $age = 99;
        $id = $this->getConnectionIdByAge($age);
        $class = '\\App\\Models\\Test' . $id;
        /** @var Test1 $test */
        $test = new $class();
        $test->name = 'bsave2';
        $test->age = $age;
        $test->save();

        $age = 100;
        $id = $this->getConnectionIdByAge($age);
        $class = '\\App\\Models\\Test' . $id;
        /** @var Test1 $test */
        $test2 = new $class();
        $test2->name = 'bsave2';
        $test2->age = $age;

        $test->save();
        $test2->save();

        echo Color::success('分模型对应msyql连接OK') . PHP_EOL;
    }

    public function readAction()
    {
        $res = $this->delete();
        echo Color::colorize('数据清理' . ($res ? '成功' : '失败'), Color::FG_LIGHT_GREEN) . PHP_EOL;
        $sql = "INSERT INTO test(`name`,age) VALUES('read',?);";
        $age1 = 99;
        /** @var Adapter $db */
        $db = di($this->getConnectionByAge($age1));
        $db->execute($sql, [$age1]);

        $age2 = 98;
        /** @var Adapter $db */
        $db = di($this->getConnectionByAge($age2));
        $db->execute($sql, [$age2]);
        echo Color::colorize('数据初始化', Color::FG_LIGHT_GREEN) . PHP_EOL;

        $test1 = Test::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age1]
        ]);

        if ($test1->name == 'read' && $test1->age == $age1) {
            echo Color::colorize('数据读取成功' . json_encode($test1->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test2 = Test::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age2]
        ]);

        if ($test2->name == 'read' && $test2->age == $age2) {
            echo Color::colorize('数据读取成功' . json_encode($test2->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test1->name = 'write1';
        $test2->name = 'write2';

        $test1->save();
        $test2->save();

        $test1 = Test::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age1]
        ]);

        if ($test1->name == 'write1' && $test1->age == $age1) {
            echo Color::colorize('数据写入成功' . json_encode($test1->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test2 = Test::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age2]
        ]);

        if ($test2->name == 'write2' && $test2->age == $age2) {
            echo Color::colorize('数据写入成功' . json_encode($test2->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        } else {
            echo Color::colorize('数据写入失败' . json_encode($test2->toArray()), Color::FG_LIGHT_RED) . PHP_EOL;
        }

    }

    public function read2Action()
    {
        $res = $this->delete();
        echo Color::colorize('数据清理' . ($res ? '成功' : '失败'), Color::FG_LIGHT_GREEN) . PHP_EOL;
        $sql = "INSERT INTO test(`name`,age) VALUES('read',?);";
        $age1 = 99;
        /** @var Adapter $db */
        $db = di($this->getConnectionByAge($age1));
        $db->execute($sql, [$age1]);

        $age2 = 98;
        /** @var Adapter $db */
        $db = di($this->getConnectionByAge($age2));
        $db->execute($sql, [$age2]);
        echo Color::colorize('数据初始化', Color::FG_LIGHT_GREEN) . PHP_EOL;

        $test1 = Test1::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age1]
        ]);

        if ($test1->name == 'read' && $test1->age == $age1) {
            echo Color::colorize('数据读取成功' . json_encode($test1->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test2 = Test2::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age2]
        ]);

        if ($test2->name == 'read' && $test2->age == $age2) {
            echo Color::colorize('数据读取成功' . json_encode($test2->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test1->name = 'write1';
        $test2->name = 'write2';

        $test1->save();
        $test2->save();

        $test1 = Test1::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age1]
        ]);

        if ($test1->name == 'write1' && $test1->age == $age1) {
            echo Color::colorize('数据写入成功' . json_encode($test1->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        }

        $test2 = Test2::findFirst([
            'conditions' => 'age = ?0',
            'bind' => [$age2]
        ]);

        if ($test2->name == 'write2' && $test2->age == $age2) {
            echo Color::colorize('数据写入成功' . json_encode($test2->toArray()), Color::FG_LIGHT_GREEN) . PHP_EOL;
        } else {
            echo Color::colorize('数据写入失败' . json_encode($test2->toArray()), Color::FG_LIGHT_RED) . PHP_EOL;
        }

    }
}

