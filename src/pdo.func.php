<?php

//$config['db']['master']['host'] = '127.0.0.1';
//$config['db']['master']['username'] = 'root'; //此处 root 改成你的数据库用户名。
//$config['db']['master']['password'] = 'root'; //此处 root 改成你的数据库密码。
//$config['db']['master']['port'] = '3306';
//$config['db']['master']['database'] = 'we7'; //此处 we7 改成你的数据库名称。
//$config['db']['master']['charset'] = 'utf8';
//$config['db']['master']['pconnect'] = 0;
//$config['db']['master']['tablepre'] = 'ims_';
//$config['db']['slave_status'] = false;
//$config['db']['slave']['1']['host'] = '';
//$config['db']['slave']['1']['username'] = '';
//$config['db']['slave']['1']['password'] = '';
//$config['db']['slave']['1']['port'] = '3307';
//$config['db']['slave']['1']['database'] = '';
//$config['db']['slave']['1']['charset'] = 'utf8';
//$config['db']['slave']['1']['pconnect'] = 0;
//$config['db']['slave']['1']['tablepre'] = 'ims_';
//$config['db']['slave']['1']['weight'] = 0;
//$config['db']['common']['slave_except_table'] = array('core_sessions');


use Qmister\We7\DB;
use Qmister\We7\SlaveDb;

if (!function_exists('we7pdo')) {

    function we7pdo()
    {
        global $_W;
        static $db;
        if (empty($_W['db'])) {
            return null;
        }
        if (empty($db)) {
            if ($_W['db']['slave_status'] == true && !empty($_W['db']['slave'])) {
                $db = new SlaveDb('master');
            } else {
                if (empty($_W['db']['master'])) {
                    $_W['db']['master'] = $GLOBALS['_W']['db'];
                }
                $db = new DB('master');
            }
        }
        return $db;
    }
}

if (!function_exists('pdo_query')) {
    /**
     * 执行一条非查询语句.
     *
     * @param string $sql
     * @param array $params
     *
     * @return mixed 成功返回受影响的行数,失败返回FALSE
     */
    function pdo_query($sql, $params = array())
    {
        return we7pdo()->query($sql, $params);
    }

}

if (!function_exists('pdo_fetchcolumn')) {
    /**
     * 执行SQL返回第一个字段.
     *
     * @param string $sql
     * @param array $params
     * @param int $column 返回查询结果的某列，默认为第一列
     *
     * @return mixed
     */
    function pdo_fetchcolumn($sql, $params = array(), $column = 0)
    {
        return we7pdo()->fetchcolumn($sql, $params, $column);
    }
}

if (!function_exists('pdo_fetch')) {
    /**
     * 执行SQL返回第一行.
     *
     * @param string $sql
     * @param array $params
     *
     * @return mixed
     */
    function pdo_fetch($sql, $params = array())
    {
        return we7pdo()->fetch($sql, $params);
    }

}
if (!function_exists('pdo_fetchall')) {
    /**
     * 执行SQL返回全部记录.
     *
     * @param string $sql
     * @param array $params
     * @param string $keyfield 将该字段的值作为结果索引
     *
     * @return mixed
     */
    function pdo_fetchall($sql, $params = array(), $keyfield = '')
    {
        return we7pdo()->fetchall($sql, $params, $keyfield);
    }
}

if (!function_exists('pdo_get')) {
    /**
     * 只能查询单条记录, 查询条件为 AND 的情况.
     *
     * @param string $tablename
     * @param array $condition 查询条件
     * @param array $fields
     *
     * @return mixed
     */
    function pdo_get($tablename, $condition = array(), $fields = array())
    {
        return we7pdo()->get($tablename, $condition, $fields);
    }
}

if (!function_exists('pdo_getall')) {
    /**
     * 获取全部记录, 查询条件为 AND 的情况.
     *
     * @param string $tablename
     * @param array $condition 查询条件
     * @param array $fields 获取字段名
     * @param string $keyfield
     * @return mixed
     */
    function pdo_getall($tablename, $condition = array(), $fields = array(), $keyfield = '', $orderby = array(), $limit = array())
    {
        return we7pdo()->getall($tablename, $condition, $fields, $keyfield, $orderby, $limit);
    }
}


if (!function_exists('pdo_getslice')) {
    /**
     * 获取多条记录, 查询条件为 AND 的情况.
     *
     * @param string $tablename
     * @param array $condition
     * @param array|int|string $limit 分页，array(当前页, 每页条页)|直接string
     * @param null $total
     * @param array $fields 获取字段名
     * @param string $keyfield
     * @param array $orderby
     * @return mixed
     */
    function pdo_getslice($tablename, $condition = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '', $orderby = array())
    {
        return we7pdo()->getslice($tablename, $condition, $limit, $total, $fields, $keyfield, $orderby);
    }
}

if (!function_exists('pdo_getcolumn')) {
    /**
     * @param $tablename
     * @param array $condition
     * @param string $field
     * @return bool|mixed
     */
    function pdo_getcolumn($tablename, $condition = array(), $field = '')
    {
        return we7pdo()->getcolumn($tablename, $condition, $field);
    }
}

if (!function_exists('pdo_exists')) {
    /**
     * 返回满足条件的记录是否存在.
     * @param string $tablename
     * @param array $condition
     * @return bool
     */
    function pdo_exists($tablename, $condition = array())
    {
        return we7pdo()->exists($tablename, $condition);
    }
}


if (!function_exists('pdo_count')) {
    /**
     * 返回满足条件的记录数.
     *
     * @param string $tablename
     * @param array $condition
     * @return int
     */
    function pdo_count($tablename, $condition = array())
    {
        return we7pdo()->count($tablename, $condition);
    }
}
if (!function_exists('pdo_update')) {
    /**
     * 更新记录.
     *
     * @param string $table 数据表名
     * @param array $data 更新记录
     * @param array $params 更新条件
     * @param string $glue 条件类型 可以为AND OR
     *
     * @return mixed
     */
    function pdo_update($table, $data = array(), $params = array(), $glue = 'AND')
    {
        return we7pdo()->update($table, $data, $params, $glue);
    }
}
if (!function_exists('pdo_insert')) {
    /**
     * 添加或更新纪录.
     *
     * @param string $table 数据表名
     * @param array $data 插入数据
     * @param bool $replace 是否执行REPLACE INTO
     *
     * @return mixed
     */
    function pdo_insert($table, $data = array(), $replace = false)
    {
        return we7pdo()->insert($table, $data, $replace);
    }
}
if (!function_exists('pdo_delete')) {
    /**
     * 删除记录.
     *
     * @param string $table 数据表名
     * @param array $params 参数列表
     * @param string $glue 条件类型 可以为AND OR
     *
     * @return mixed
     */
    function pdo_delete($table, $params = array(), $glue = 'AND')
    {
        return we7pdo()->delete($table, $params, $glue);
    }
}
if (!function_exists('pdo_insertid')) {
    /**
     * 获取上一步 INSERT 操作产生的 ID.
     *
     * @return int
     */
    function pdo_insertid()
    {
        return we7pdo()->insertid();
    }
}

if (!function_exists('pdo_begin')) {
    /**
     * 启动一个事务，关闭自动提交.
     *
     * @return void
     */
    function pdo_begin()
    {
        we7pdo()->begin();
    }
}
if (!function_exists('pdo_commit')) {
    /**
     * 提交一个事务，恢复自动提交.
     *
     * @return void
     */
    function pdo_commit()
    {
        we7pdo()->commit();
    }
}

if (!function_exists('pdo_rollback')) {
    /**
     * 回滚一个事务，恢复自动提交.
     *
     * @return void
     */
    function pdo_rollback()
    {
        we7pdo()->rollBack();
    }
}

if (!function_exists('pdo_logging')) {
    /**
     * 获取pdo操作错误信息列表.
     * @return array
     */
    function pdo_logging()
    {
        return we7pdo()->getLogging();
    }
}

if (!function_exists('pdo_fieldexists')) {
    /**
     * 查询字段是否存在
     * 成功返回TRUE，失败返回FALSE.
     *
     * @param string $tablename 查询表名
     * @param string $fieldname 查询字段名
     *
     * @return boolean
     */
    function pdo_fieldexists($tablename, $fieldname = '')
    {
        return we7pdo()->fieldexists($tablename, $fieldname);
    }
}

if (!function_exists('pdo_fieldmatch')) {
    /**
     * @param $tablename
     * @param $fieldname
     * @param string $datatype
     * @param string $length
     * @return bool
     */
    function pdo_fieldmatch($tablename, $fieldname, $datatype = '', $length = '')
    {
        return we7pdo()->fieldmatch($tablename, $fieldname, $datatype, $length);
    }
}

if (!function_exists('pdo_indexexists')) {
    /**
     * 查询索引是否存在
     * 成功返回TRUE，失败返回FALSE.
     *
     * @param string $tablename 查询表名
     * @param $indexname
     *
     * @return boolean
     */
    function pdo_indexexists($tablename, $indexname)
    {
        return we7pdo()->indexexists($tablename, $indexname);
    }
}

if (!function_exists('pdo_fetchallfields')) {
    /**
     * 获取所有字段名称.
     *
     * @param string $tablename 数据表名
     *
     * @return array
     */
    function pdo_fetchallfields($tablename)
    {
        $fields = pdo_fetchall("DESCRIBE {$tablename}", array(), 'Field');
        $fields = array_keys($fields);
        return $fields;
    }
}

if (!function_exists('pdo_tableexists')) {
    /**
     * 检测数据表是否存在.
     *
     * @param string $tablename 数据表名
     *
     * @return boolean
     */
    function pdo_tableexists($tablename)
    {
        return we7pdo()->tableexists($tablename);
    }
}

