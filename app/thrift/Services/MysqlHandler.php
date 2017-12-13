<?php
// +----------------------------------------------------------------------
// | AppHandler.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 limingxinleo All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <https://github.com/limingxinleo>
// +----------------------------------------------------------------------
namespace App\Thrift\Services;

use App\Models\Test;
use App\Utils\DB;
use Xin\Thrift\MysqlService\MysqlIf;
use Xin\Thrift\MysqlService\ThriftException;

class MysqlHandler extends Handler implements MysqlIf
{
    public function save()
    {
        DB::begin();
        $model = new Test();
        $model->name = 'save';
        $model->age = 1;
        $model->save();
        sleep(2);
        DB::commit();
        return true;
    }

    public function notSave()
    {
        DB::begin();
        $model = new Test();
        $model->name = 'not_save';
        $model->age = 0;
        $model->save();
        sleep(3);
        DB::rollback();
        return true;
    }

}