<?php


namespace Qmister\We7;


class DB
{
    /**
     * @var array
     */
    protected $cfg;
    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $logging;

    /**
     * @var array
     */
    protected $link = array();

    /**
     * @var
     */
    protected $tablepre;


    /**
     * DB constructor.
     * @param string $name
     */
    public function __construct($name = 'master')
    {
        global $_W;
        $this->cfg  = $_W['db'];
        $this->name = $name;
        $this->connect($name);
    }


    /**
     * @param string $name
     * @return void
     */
    public function connect($name = 'master')
    {
        if (is_array($name)) {
            $cfg = $name;
        } else {
            $cfg = $this->cfg[$name];
        }
        $this->tablepre = $cfg['tablepre'];

        $dsn = "mysql:dbname={$cfg['database']};host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";

        $options = array_merge(isset($cfg['options']) ? $cfg['options'] : [], [
            \PDO::ATTR_PERSISTENT => $cfg['pconnect']
        ]);

        $pdo = new \PDO($dsn, $cfg['username'], $cfg['password'], $options);
        $sql = "SET NAMES '{$cfg['charset']}';";
        $pdo->exec($sql);
        $pdo->exec("SET sql_mode='';");
        if ('root' == $cfg['username'] && in_array($cfg['host'], array('localhost', '127.0.0.1'))) {
            $pdo->exec('SET GLOBAL max_allowed_packet = 2*1024*1024*10;');
        }
        $this->pdo = $pdo;
        if (is_string($name)) {
            $this->link[$name] = $this->pdo;
        }
        $this->logging($sql, [], []);
    }


    /**
     * 返回带前缀表名
     * @param $table
     * @return string
     */
    public function tablename($table)
    {
        return $this->tablepre . $table;
    }

    /**
     * 查询一条记录,比fetch简单
     * @param       $tablename
     * @param array $params
     * @param array $fields
     * @param array $orderby
     * @return bool|mixed
     */
    public function get($tablename, $params = array(), $fields = array(), $orderby = array())
    {
        $select     = SqlPaser::parseSelect($fields);
        $condition  = SqlPaser::parseParameter($params, 'AND');
        $orderbysql = SqlPaser::parseOrderby($orderby);
        $sql        = "{$select} FROM "
            . $this->tablename($tablename)
            . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '')
            . " $orderbysql LIMIT 1";
        return $this->fetch($sql, $condition['params']);
    }

    /**
     * 查询多条数据
     * @param        $tablename
     * @param array  $params
     * @param array  $fields
     * @param string $keyfield
     * @param array  $orderby
     * @param array  $limit
     * @return array|bool
     */
    public function getall($tablename, $params = array(), $fields = array(), $keyfield = '', $orderby = array(), $limit = array())
    {
        $select     = SqlPaser::parseSelect($fields);
        $condition  = SqlPaser::parseParameter($params, 'AND');
        $limitsql   = SqlPaser::parseLimit($limit);
        $orderbysql = SqlPaser::parseOrderby($orderby);
        $sql        = "{$select} FROM "
            . $this->tablename($tablename)
            . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '')
            . $orderbysql . $limitsql;
        return $this->fetchall($sql, $condition['params'], $keyfield);
    }

    /**
     * 查询区间记录
     * @param        $tablename
     * @param array  $params
     * @param array  $limit
     * @param null   $total
     * @param array  $fields
     * @param string $keyfield
     * @param array  $orderby
     * @return array|bool
     */
    public function getslice($tablename, $params = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '', $orderby = array())
    {
        $select    = SqlPaser::parseSelect($fields);
        $condition = SqlPaser::parseParameter($params, 'AND');
        $limitsql  = SqlPaser::parseLimit($limit);

        if (!empty($orderby)) {
            if (is_array($orderby)) {
                $orderbysql = implode(',', $orderby);
            } else {
                $orderbysql = $orderby;
            }
        }
        $sql   = "{$select} FROM "
            . $this->tablename($tablename)
            . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '')
            . (!empty($orderbysql) ? " ORDER BY $orderbysql " : '') . $limitsql;
        $total = $this->fetchcolumn('SELECT COUNT(*) FROM ' . $this->tablename($tablename) . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : ''), $condition['params']);
        return $this->fetchall($sql, $condition['params'], $keyfield);
    }

    /**
     * 返回单字段值
     * @param        $tablename
     * @param array  $params
     * @param string $field
     * @return bool|mixed
     */
    public function getcolumn($tablename, $params = array(), $field = '')
    {
        $result = $this->get($tablename, $params, $field);
        if (!empty($result)) {
            if (strexists($field, '(')) {
                return array_shift($result);
            } else {
                return $result[$field];
            }
        } else {
            return false;
        }
    }

    /**
     * 更新记录.
     * @param        $table
     * @param array  $data 要更新的数据数组 [ '字段名' => '值']
     * @param array  $params 更新条件 ['字段名' => '值']
     * @param string $glue 可以为AND OR
     * @return bool|int
     */
    public function update($table, $data = array(), $params = array(), $glue = 'AND')
    {
        $fields    = SqlPaser::parseParameter($data, ',');
        $condition = SqlPaser::parseParameter($params, $glue);
        $params    = array_merge($fields['params'], $condition['params']);
        $sql       = 'UPDATE ' . $this->tablename($table) . " SET {$fields['fields']}";
        $sql       .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return $this->query($sql, $params);
    }

    /**
     * 插入数据
     * @param       $table
     * @param array $data
     * @param bool  $replace
     * @return bool|int
     */
    public function insert($table, $data = array(), $replace = false)
    {
        $cmd       = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        $condition = SqlPaser::parseParameter($data, ',');
        $sql       = "$cmd " . $this->tablename($table) . " SET {$condition['fields']}";
        return $this->query($sql, $condition['params']);
    }

    /**
     * 插入数据id
     * @return string
     */
    public function insertid()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 删除记录.
     * @param        $table
     * @param array  $params
     * @param string $glue
     * @return bool|int
     */
    public function delete($table, $params = array(), $glue = 'AND')
    {
        $condition = SqlPaser::parseParameter($params, $glue);
        $sql       = 'DELETE FROM ' . $this->tablename($table);
        $sql       .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return $this->query($sql, $condition['params']);
    }

    /**
     * 检测一条记录是否存在.
     * @param       $tablename
     * @param array $params
     * @return bool
     */
    public function exists($tablename, $params = array())
    {
        $row = $this->get($tablename, $params);
        if (empty($row) || !is_array($row) || 0 == count($row)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param       $tablename
     * @param array $params
     * @return int
     */
    public function count($tablename, $params = array())
    {
        $total = $this->getcolumn($tablename, $params, 'count(*)');
        return intval($total);
    }


    /**
     * 查询字段是否存在
     * @param $tablename
     * @param $fieldname
     * @return bool
     */
    public function fieldexists($tablename, $fieldname)
    {
        if (!$this->tableexists($tablename)) {
            return false;
        }
        $fields = $this->fetchall("SHOW COLUMNS FROM " . $this->tablename($tablename));
        if (empty($fields)) {
            return false;
        }
        foreach ($fields as $field) {
            if ($fieldname === $field['Field']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 查询字段类型是否匹配
     * 成功返回TRUE，失败返回FALSE，字段存在，但类型错误返回-1.
     * @param string $tablename 查询表名
     * @param string $fieldname 查询字段名
     * @param string $datatype 查询字段类型
     * @param string $length 查询字段长度
     * @return boolean
     */
    public function fieldmatch($tablename, $fieldname, $datatype = '', $length = '')
    {
        $datatype   = strtolower($datatype);
        $field_info = $this->fetch('DESCRIBE ' . $this->tablename($tablename) . " `{$fieldname}`", array());
        if (empty($field_info)) {
            return false;
        }
        if (!empty($datatype)) {
            $find = strexists($field_info['Type'], '(');
            if (empty($find)) {
                $length = '';
            }
            if (!empty($length)) {
                $datatype .= ("({$length})");
            }

            return 0 === strpos($field_info['Type'], $datatype) ? true : -1;
        }
        return true;
    }

    /**
     * 查询索引是否存在
     * 成功返回TRUE，失败返回FALSE
     * @param string $tablename 查询表名
     * @param array  $indexname 查询索引名
     * @return boolean
     */
    public function indexexists($tablename, $indexname)
    {
        if (!$this->tableexists($tablename)) {
            return false;
        }
        if (!empty($indexname)) {
            $indexs = $this->fetchall('SHOW INDEX FROM ' . $this->tablename($tablename), array(), '');
            if (!empty($indexs) && is_array($indexs)) {
                foreach ($indexs as $row) {
                    if ($row['Key_name'] == $indexname) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 判断某个数据表是否存在.
     * @param $table
     * @return bool
     */
    public function tableexists($table)
    {
        if (!empty($table)) {
            $real_table = preg_match('/[a-zA-Z0-9_]{' . strlen($table) . '}/', $table);
            if (1 !== $real_table) {
                return false;
            }
            $tablename = (0 === strpos($table, $this->tablepre)) ? ($table) : ($this->tablepre . $table);
            $data      = $this->fetch("SHOW TABLES LIKE '{$tablename}'", array());
            if (!empty($data)) {
                $data = array_values($data);
                if (in_array($tablename, $data)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 启动一个事务，关闭自动提交.
     */
    public function begin()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交一个事务，恢复自动提交.
     * @return void
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * 回滚一个事务，恢复自动提交.
     * @return void
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }


    /**
     * @param $sql
     * @return bool|\PDOStatement
     */
    public function prepare($sql)
    {
        $sqlSafe = SqlPaser::checkquery($sql);
        if (is_error($sqlSafe)) {
            trigger_error($sqlSafe['message'], E_USER_ERROR);
            return false;
        }
        return $this->pdo->prepare($sql);
    }


    /**
     * 支持写操作的更新删除,不能查询
     * @param       $sql
     * @param array $params
     * @return bool|int
     */
    public function query($sql, $params = array())
    {
        $sqlSafe = SqlPaser::checkquery($sql);
        if (is_error($sqlSafe)) {
            trigger_error($sqlSafe['message'], E_USER_ERROR);
            return false;
        }
        if (empty($params)) {
            $result = $this->pdo->exec($sql);
            $this->logging($sql, array(), $this->pdo->errorInfo());
            return $result;
        }
        $statement = $this->prepare($sql);
        $result    = $statement->execute($params);
        $this->logging($sql, $params, $statement->errorInfo());
        if (!$result) {
            return false;
        } else {
            return $statement->rowCount();
        }
    }


    /**
     * 查询一条数据
     * @param       $sql
     * @param array $params
     * @return bool|mixed
     */
    public function fetch($sql, $params = array())
    {
        $statement = $this->prepare($sql);
        $result    = $statement->execute($params);
        $this->logging($sql, $params, $statement->errorInfo());
        if (!$result) {
            return false;
        }
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 查询多条数据
     * @param        $sql
     * @param array  $params
     * @param string $keyfield
     * @return array|bool
     */
    public function fetchall($sql, $params = array(), $keyfield = '')
    {
        $statement = $this->prepare($sql);
        $result    = $statement->execute($params);
        $this->logging($sql, $params, $statement->errorInfo());
        if (!$result) {
            return false;
        }
        if (empty($keyfield)) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $temp   = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $result = array();
            if (!empty($temp)) {
                foreach ($temp as $key => &$row) {
                    if (isset($row[$keyfield])) {
                        $result[$row[$keyfield]] = $row;
                    } else {
                        $result[] = $row;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 执行SQL返回第一个字段.
     * @param       $sql
     * @param array $params
     * @param int   $column
     * @return bool|mixed
     */
    public function fetchcolumn($sql, $params = array(), $column = 0)
    {
        $statement = $this->prepare($sql);
        $result    = $statement->execute($params);
        $this->logging($sql, $params, $statement->errorInfo());
        if (!$result) {
            return false;
        }
        return $statement->fetchColumn($column);
    }

    /**
     * @return array
     */
    public function getLogging()
    {
        return $this->logging;
    }

    /**
     * @param $sql
     * @param $params
     * @param $message
     * @return $this
     */
    protected function logging($sql, $params, $message)
    {
        $info['sql']     = $sql;
        $info['params']  = $params;
        $info['error']   = empty($message) ? $this->pdo->errorInfo() : $message;
        $this->logging[] = $info;
        return $this;
    }
}