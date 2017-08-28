<?php
namespace app\admin\logic;
use think\Db;
class DbMysqlLogic implements DbMysqlInterface {
    /**
     * DB connect
     *
     * @access public
     *
     * @return resource connection link
     */
    public function connect(){
        echo __METHOD__, '<br/>';
    }

    /**
     * Disconnect from DB
     *
     * @access public
     *
     * @return viod
     */
    public function disconnect(){
        echo __METHOD__, '<br/>';

    }

    /**
     * Free result
     *
     * @access public
     * @param resource $result query resourse
     *
     * @return viod
     */
    public function free($result){
        echo __METHOD__, '<br/>';

    }

    /**
     * Execute simple query
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return resource|bool query result
     */
    public function query($sql,$args){
        $args = func_get_args();
        $sql = array_shift($args);
        $sqls = preg_split('/\?[FTN]/', $sql,-1,PREG_SPLIT_NO_EMPTY);
        $lastSql = "";
        foreach ($sqls as $key=>$value){
            $lastSql .= $value . $args[$key];
        }

        $res = Db::table('xm_permission')->query($lastSql);

        return $res;
    }

    /**
     * Insert query method
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return int|false last insert id
     */
    public function insert($sql,$args){

        $args = func_get_args();

        $sql = array_shift($args);
        $tableName = array_shift($args);
        $sql = str_replace('?T', $tableName, $sql);

        $data = array_shift($args);
        $dataStr = [];
        foreach ($data as $key=>$value){
            $dataStr[] = '`'.$key.'`="'.$value.'"';
        }

        $dataStr = join(',', $dataStr);

        $sql = str_replace('?%', $dataStr, $sql);

        $res = Db::table('xm_permission')->execute($sql);
        return $res;

    }

    /**
     * Update query method
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return int|false affected rows
     */
    public function update($sql,$args){
        echo __METHOD__, '<br/>';

    }

    /**
     * Get all query result rows as associated array
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return array associated data array (two level array)
     */
    public function getAll($sql,$args){
        echo __METHOD__, '<br/>';

    }

    /**
     * Get all query result rows as associated array with first field as row key
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return array associated data array (two level array)
     */
    public function getAssoc($sql,$args){


    }

    /**
     * Get only first row from query
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return array associated data array
     */
    public function getRow($sql, $arg){

        $args = func_get_args();

        $sql = array_shift($args);

        $sqls = preg_split('/\?[FTN]/', $sql,-1,PREG_SPLIT_NO_EMPTY);

        $lastSql = "";
        foreach ($sqls as $key => $value){

            $lastSql .= $value . $args[$key];
        }

        $rows = Db::table('xm_permission')->query($lastSql);
        $row = array_shift($rows);

        return $row;
    }

    /**
     * Get first column of query result
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return array one level data array
     */
    public function getCol($sql,$args){


    }

    /**
     * Get one first field value from query result
     *
     * @access public
     * @param string $sql SQL query
     * @param array $args query arguments
     *
     * @return string field value
     */
    public function getOne($sql,$args){


    }
}