namespace php Xin.Thrift.MysqlService
namespace go vendor.mysql.service

exception ThriftException {
  1: i32 code,
  2: string message
}

service Mysql {
    // 开启事务并保存
    bool save() throws (1:ThriftException ex)

    // 开启事务并rollback
    bool notSave() throws (1:ThriftException ex)
}