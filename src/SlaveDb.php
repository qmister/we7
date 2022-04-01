<?php


namespace Qmister\We7;


class SlaveDb extends DB
{
    /**
     * @var
     */
    private $weight;
    /**
     * @var int
     */
    private $slavequery = 0;
    /**
     * @var null
     */
    private $slaveid = null;

    /**
     * @param $sql
     * @return bool|\PDOStatement
     */
    public function prepare($sql)
    {
        $this->init_connect($sql);

        return parent::prepare($sql);
    }

    /**
     * @param $sql
     * @param array $params
     * @return bool|int
     */
    public function query($sql, $params = array())
    {
        if (!empty($params)) {
            return parent::query($sql, $params);
        }
        $this->init_connect($sql);
        $result = $this->pdo->exec($sql);
        $this->logging($sql, [], $this->pdo->errorInfo());
        return $result;
    }

    /**
     * @return bool
     */
    public function slave_connect()
    {
        $this->slave_choose();
        if ($this->slaveid) {
            if (!isset($this->link[$this->slaveid])) {
                $this->connect($this->slaveid);
            }
            ++$this->slavequery;
            $this->pdo = $this->link[$this->slaveid];
        }

        return true;
    }

    protected function slave_choose()
    {
        if (!isset($this->weight)) {
            foreach ($this->cfg['slave'] as $key => $value) {
                $this->weight .= str_repeat($key, 1 + intval($value['weight']));
            }
        }
        $sid           = $this->weight[mt_rand(0, strlen($this->weight) - 1)];
        $this->slaveid = 'slave_' . $sid;
        if (!isset($this->cfg[$this->slaveid])) {
            $this->cfg[$this->slaveid] = $this->cfg['slave'][$sid];
        }
    }

    public function init_connect($sql)
    {
        if (!(true == $this->cfg['slave_status'] && !empty($this->cfg['slave']))) {
            $this->master_connect();
        } else {
            $sql          = trim($sql);
            $sql_lower    = strtolower($sql);
            $slave_except = false;
            if (!strexists($sql_lower, 'where ')) {
                $tablename = substr($sql_lower, strpos($sql_lower, 'from ') + 5);
            } else {
                $tablename = substr($sql_lower, strpos($sql_lower, 'from ') + 5, strpos($sql_lower, ' where') - strpos($sql_lower, 'from ') - 5);
            }
            $tablename = trim($tablename, '`');
            $tablename = str_replace($this->tablepre, '', $tablename);
            if (!empty($this->cfg['common']['slave_except_table']) && in_array(strtolower($tablename), $this->cfg['common']['slave_except_table'])) {
                $slave_except = true;
            }
            if (!(!$slave_except && 'SELECT' === strtoupper(substr($sql, 0, 6)) && $this->slave_connect())) {
                $this->master_connect();
            }
        }

        return true;
    }

    public function master_connect()
    {
        if (!isset($this->link['master'])) {
            $this->connect('master');
        }
        $this->pdo = $this->link['master'];
    }

    public function insertid()
    {
        $this->master_connect();

        return parent::insertid();
    }

    public function begin()
    {
        $this->master_connect();
        parent::begin();
    }

    public function commit()
    {
        $this->master_connect();
        parent::commit();
    }

    public function rollback()
    {
        $this->master_connect();
        parent::rollback();
    }
}