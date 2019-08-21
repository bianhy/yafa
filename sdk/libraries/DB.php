<?php
/**
 * @file DB.php
 * @author bianhy
 * @date 2019-04-26 17:22
 *
 */

namespace SDK\Libraries;

/*
     * 数据库操作基础类
     *
     */

/*
 * 调试开关, 在包含此文件之外可以定义是否打开调试模式
 *
 * 调试模式下会记录程序执行过程中的信息, 最后调用 $db->dump() 可以显示这些信息
 *
 */

use SDK\Libraries\Database\Builder;
use Psr\Log\AbstractLogger;

defined('DATABASE_DEBUG') || define('DATABASE_DEBUG', false);

defined('DATABASE_LOG_SQL_TIME') || define('DATABASE_LOG_SQL_TIME', 0.1);

/*
 * 默认 mysql 连接超时时间设置为5秒连接不上就认为超时
 *
 * 如在包含此文件前 define('DATABASE_DEFAULT_TIMEOUT', 3);
 * 那么在所有请求中3秒连接不上mysql就算是超时
 *
 */
defined('DATABASE_DEFAULT_TIMEOUT') || define('DATABASE_DEFAULT_TIMEOUT', 1);


/**
 *
 * 全部函数原型
 *
 * <prototype>
 *      static function &getInstance($tag_name) // 获取 Database 单实例
 *      function &__construct($config_callback_function = null) {
 *      function __destruct()
 *      function setTagName($tag_name)
 *      function setAutoClose($switch = true) // 控制每次query()后自动关闭mysql连接的开关
 *      function setConfigFunction($callback_function, $filename = null) // 设置获取mysql host, user, pass的回调函数
 *      function load($const_table, $hash = null, $need_hash = true) // 获取某张表在哪台db上
 *      function lastError()
 *      function connect()
 *      function setReverseOrder($limit, $asc, $desc)
 *      function &queryWithPage($sql, $current_page = 1, $total_rows = 0, $page_size = 25) // 翻页列表函数
 *      function &query($sql, $query_with_page = false) // 普通请求, 返回mysql资源连接
 *      function queryWithReturnInsertId($sql) // 执行插入语句, 并返回mysql自增ID
 *      function &getRow($sql)
 *      function &getRows($sql)
 *      function &getValue($sql)
 *      function getLogs() {
 *      function getPage()
 *      function affectedRows()
 *      function close()
 *      function escape($string)
 *      function dump($ret = false)
 *      protected function logDebug($message, $level = 'info')
 *      protected function logError($message, $level = 'error')
 *      protected function setLastError($message)
 * </prototype>
 *
 * 在此文件最下面有db配置文件函数的示例, 请参照改写
 *
 * 示例代码, 备注, 此类库只提供基本操作, 没涉及到安全检查之类的
 *
 * <code>
 *
 *      $db = new DB;
 *      $db->setConfigFunction("example_callback_function", "./example.php"); // 设置使用db配置函数
 *      $db->setConfigFunction("example_callback_function"); // 功能同上同样设置配置函数
 *      $db->load("table_user", $user);            // 第二步载入 table_user 表相关的配置
 *
 *      // 获取一行数据
 *      $row = $db->getRow("SELECT * FROM table_user WHERE username = $user LIMIT 1");
 *
 *      // 获取年龄大于20岁的用户30个
 *      $rows= $db->getRows("SELECT * FROM table_user WHERE age > 20 LIMIT 30");
 *
 *      // 获取这个用户的年龄字段的值
 *      $age = $db->getValue("SELECT age FROM table_user WHERE username = $user LIMIT 1");
 *
 *      // 翻页列表, 每页显示 50 条, (总数由此模块自动查出来)
 *      $page_list = $db->queryWithPage("SELECT * FROM table_user", $_GET["page"], 0, 50);
 *
 *      // 翻页列表, 总数从cache中取到, 每页30条, (总数从cache中取)
 *     $diary_count = $memcache->get($user."_total_diary_count");
 *     $page_list = $db->queryWithPage("SELECT * FROM table_user", $_GET["page"], $diary_count, 30);
 *
 *      // 关掉每次查询后自动关闭mysql连接的特性
 *      $db->setAutoClose(false);
 *      $db->query($sql);
 *      ....
 *      $db->getRows($sql);
 *      ...
 *      // 上面关掉了自动关闭链接, 所以最后需要手动关掉
 *      $db->close();
 *
 *        // 显示上面的调试信息
 *        $db->dump();
 *
 *        // 在不同的文件中使用同一个实例
 *        a.php 中
 *        $db = Database::getInstance("abc");  // 获取一个标签为 abc 的实例
 *        ...
 *
 *        b.php 中
 *        $db = Database::getInstance("abc");  // 此实例为 a.php 中使用的实例
 *        ...
 *
 *        c.php 中
 *        $db = Database::getInstance("abc");  // 同上
 *
 *        d.php
 *        $db = Database::getInstance("qun");  // 重新获取一个实例, 标识为 qun 使用, 此实例为一个全新的object
 *
 *        $a = new Database;
 *        $a->setTagName("home"); // 将此实例标签为 home, 以便在其他模块中获取这个实例
 *
 * </code>
 *
 */
class DB
{
    /**
     * @var DB
     */
    private static $instances; // 单实例

    private $_config; // 通过 config_callback_function() 取到的db配置信息

    protected $config_callback_function; // 用户自定义的db配置函数
    /**
     * @var \PDO
     */
    protected $connection; // mysql connection
    protected $hash_str; // 拆库,表的key, 通常都是用户名
    protected $last_error; // 最后一次执行操作的错误信息, 没有错误为null
    protected $logs; // 调试信息, DATABASE_DEBUG 打开时才有值
    protected $page = ['reverse_limit'=>100000]; // 翻页查询时包含的相关翻页信息,总记录数,当前第几页..
    protected $affected_rows = 0; // 最后一次操作时所影响的数据条数
    protected $auto_close_mysql_link = true; // 每次执行查询后都自动关闭mysql连接
    /**
     * @var AbstractLogger
     */
    protected $external_logger = null; // 定义外部处理错误的对象,此class必须包含一个log()函数

    /**
     *
     * 获取单 database 类的实例
     *
     * <code>
     *  $db = DB::getInstance("xxx_db_callback_function", "example"); // 获取一个database实例, 并将此实例标识为 example 频道使用
     * </code>
     *
     * @name string $tag_name 为些实例打标签, 以后所有些标签都将返回同一个实例
     * @return DB
     * @throws \ReflectionException
     */
    public static function getInstance()
    {
        $args  = func_get_args();
        $model = get_called_class();
        $key   = md5($model . "|" . json_encode($args));
        if (!isset(self::$instances[$key]) || !(self::$instances[$key] instanceof $model)) {
            $obj                   = new \ReflectionClass($model);
            self::$instances[$key] = $obj->newInstanceArgs($args);
        }
        return self::$instances[$key];
    }

    /**
     * 构造函数
     * new \ReflectionClass($model)->newInstanceArgs($args);
     * DB constructor.
     * @param string $config_callback_function 获取 db 配置的回调函数, 传递函数名
     * @throws DBException
     */
    public function __construct($config_callback_function = "get_db_config")
    {
        if ($config_callback_function) {
            $this->setConfigFunction($config_callback_function);
        }

        return $this;
    }

    public function __destruct()
    {
        is_resource($this->connection) && $this->connection = null;
    }

    /**
     * 设置外部日志处理函数
     *
     * @param object $obj 外部处理错误信息的对像, 此对象必须包含一个 log() 函数
     *
     */
    public function setExternalLogger($obj)
    {
        $this->external_logger = $obj;
    }

    /**
     * 设置是否自动关闭 mysql 连接
     *
     * @param bool $switch 开关
     * @return void
     *
     */
    public function setAutoClose($switch = true)
    {
        $this->auto_close_mysql_link = $switch;
    }

    /**
     *
     * 函数功能: 设置并载入 db 配置文件
     *
     * <code>
     *  $db = new DB;
     *  if (false == $db->setConfigFunction("xxx_db_callback_function") {
     *      echo $db->lastError();
     *      exit;
     *  }
     * </code>
     *
     * @param string $callback_function 获取db信息的回调函数名
     * @return bool
     * @throws DBException
     */
    public function setConfigFunction($callback_function)
    {
        if (!is_callable($callback_function)) {
            $this->setLastError("can not found callback function: $callback_function");
        }

        $this->config_callback_function = $callback_function;
        return true;
    }

    /**
     * 载入配置块, 标识后面的 SQL 将要使用什么host, db, table
     *
     * <code>
     *        $db = new DB;
     *        $db->setConfigFunction("example_db_callback_function", "./example.php");
     *        $db->load("table_user", $_SESSION["Account"]);
     *        $db->query($sql);
     *        $db->load("table_user_fav", $_SESSION["Account"]);
     *        $db->query($sql);
     * </code>
     *
     * @param string $const_table 已经在配置文件中配置好的case块区名
     * @param string $hash 是否需要自动进行 md5($hash) 操作, 台脚本需指定 hash 时将此值置false
     * @return DB |bool
     *
     */
    public function load($const_table, $hash = null)
    {
        if ($this->auto_close_mysql_link) {
            $this->close();
        }
        $this->hash_str = $hash;

        if ($const_table && $this->config_callback_function) {
            $callback_function = $this->config_callback_function;
            if ($this->_config = call_user_func_array($callback_function, array($const_table, $hash))) {
                return $this;
            }
        }

        return false;
    }

    /**
     * 获取最后一次操作的出错信息, 如果最后一次没有错误, 则此值为空
     *
     * <code>
     *        $res = $db->query($sql);
     *        if (false == $res) {
     *            echo $db->lastError();
     *        }
     * </code>
     * @param void
     * @return string
     *
     */
    public function lastError()
    {
        return $this->last_error;
    }

    /**
     * 连接 mysql 数据库
     *
     * <code>
     *        $db = new DB;
     *        $db->setConfigFunction("example_get_db_config");
     *        $db->load("table_user", $_SESSION["Account"]);
     *        $conn = $db->connect();
     * </code>
     *
     * @return bool|\PDO|resource
     * @throws DBException
     */
    protected function connect()
    {
        if (is_resource($this->connection)) {
            return $this->connection;
        }

        if (empty($this->_config)) {
            $this->setLastError('Lost data source');
            return false;
        }

        try {
            $this->connection = new \PDO(
                "mysql:host=" . $this->_config['host'] . ";dbname=" . $this->_config['database'] . ";port=" . $this->_config['port'],
                $this->_config['user'],
                $this->_config['pass'],
                array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                    \PDO::ATTR_PERSISTENT         => false,
                    \PDO::ATTR_TIMEOUT            => DATABASE_DEFAULT_TIMEOUT
                )
            );
        } catch (\PDOException $e) {
            $this->setLastError($e->getMessage());
        }
        return $this->connection;
    }

    /**
     * 设置反向排序的参数
     *
     * <code>
     *        $db = new DB;
     *        $db->setConfigFunction("example_get_db_config");
     *        $db->load("table_user", $_SESSION["Account"]);
     *        $db->setReverseOrder(50000, "auto_id ASC", "auto_id DESC"); // 五万条之前正常排序, 五万条之后反向排序
     *        $db->queryWithPage($sql);
     * </code>
     *
     * @param integer $limit 达到多少条记录后开始反转翻页
     * @param string $asc 正向排序方式
     * @param string $desc 反向排序方式
     * @return void
     *
     */
    public function setReverseOrder($limit, $asc, $desc)
    {
        $this->page['reverse_limit'] = intval($limit);
        $this->page['reverse_asc']   = $asc;
        $this->page['reverse_desc']  = $desc;
    }

    /**
     *
     * 带翻页的查询, 通常在展示数据列表的时候调用此函数, 没有数据返回 null, 出错返回 false
     *
     * 第三个参数 $total_rows 是从外部传递此查询总共有多少条记录
     * 这个参数可以在别的地方被缓存, 翻页的时候用缓存值能有效的减轻db上 SELECT COUNT(*) 的压力
     *
     * @param $sql
     * @param int $current_page 前显示第几页
     * @param int $total_rows  此查询总共有多少条记录
     * @param int $page_size 每页显示多少条
     * @return array |bool
     * @throws DBException
     */
    public function queryWithPage($sql, $current_page = 1, $total_rows = 0, $page_size = 25)
    {
        $this->page['size']        = (int)$page_size;
        $this->page['current']     = (int)$current_page;
        $this->page['total_rows']  = (int)$total_rows;
        $this->page['total_pages'] = 1;

        /*
        * 如果外部没有传递有多少条记录, 需要先把符合此条件的记录总数查出来
        * 如何从外部传递总记录数进来参见 $db->queryWithPage() 函数
        */
        if (1 > $this->page['total_rows']) {
            /*
            * 翻页语句为 SELECT * FROM table WHERE condition = const_variable
            * 需要将最前面的 SELECT * FROM 替换为 SELECT COUNT(1) FROM, 后面操持不变
            */
            if (false == ($count_sql = stristr($sql, 'from'))) {
                $this->setLastError("[E] Parse SQL fail {$sql}");
                return false;
            }
            $count_sql                = "SELECT COUNT(1) " . $count_sql;
            $this->page['total_rows'] = $this->executeSql($count_sql,  [], 'value');
        }

        /*
        * 构造一个翻页的数组, 存放在 $this->page 数组中
        * array(
        *     'total_pages'  => 10, // 总共有 10 页
        *     'total_rows'   => 250, // 总共有 250 条记录
        *     'current'      => 2, // 当前显示第 2 页
        *     'size'         => 25,  // 每页显示 25 条记录
        * )
        */
        $this->page['total_pages'] = ceil($this->page['total_rows'] / $this->page['size']);

        if ($this->page['total_pages'] < $this->page['current']) {
            $this->page['current'] = $this->page['total_pages'];
        }
        if (2 > intval($this->page['current'])) {
            $this->page['current'] = 1;
        }

        /*
        * 是否使用反向排序获取数据, 此方法可以有效减轻 db 负担
        *
        * 具体方法为:
        *
        * 假设数据 10000 条, 每页显示 20 条, 那么有 500 页, 正常情况下显示第 450 页时我们的
        * SQL 语句为:
        * SELECT * FROM table ORDER BY id DESC LIMIT ((500 - 1) * 20), 20;
        * 如果我们将 SQL 改为:
        * SELECT * FROM table ORDER BY id ASC LIMIT (500 - 51), 20
        * 那么执行效率将明显提升, 因为第一个取数据要从头开始偏移到 (500 - 1) * 20 这个位置
        * 而第二条语句则使用ASC作为排序, 并且偏移量更小 (500 - 51) * 20
        *
        * 而我们下面这段代码就是干这个事情的
        *
        */
        $reverse_limit = false;

        if (isset($this->page['reverse_limit'])) {
            if ($this->page['reverse_limit'] < $this->page['total_rows']) {
                $new_page = false;
                if ($this->page['current'] == $this->page['total_pages']) {
                    $new_page = 1;
                } else {
                    $half = floor($this->page['total_pages'] / 2);
                    if ($half < $this->page['current']) {
                        $new_page = $this->page['total_pages'] - ($this->page['current']) + 1;
                    }
                }
                if ($new_page) {
                    $sql = str_replace($this->page['reverse_asc'], $this->page['reverse_desc'], $sql);
                    $sql .= ' LIMIT ' . (($new_page - 1) * $this->page['size']) . ',' . $this->page['size'];
                    $reverse_limit = true;
                }
            }
        }

        if (!$reverse_limit) {
            $sql .= ' LIMIT ' . (($this->page['current'] - 1) * $this->page['size']) . ',' . $this->page['size'];
        }
        /* 执行最终的 SQL 语句 */
        $rows = $this->executeSql($sql, [], 'rows');
        $ret  = ['rows'=>$rows];
        if ($rows && isset($this->page['reverse_limit'])) {
            /*
             * 优化反向排序时数据展示的方式, db中最后一条数据排在页面展示的最下面一条
             */
            if ($this->page['reverse_limit'] < $this->page['total_rows'] && $this->page['current'] > floor($this->page["total_pages"] / 2)) {
                $cnt = sizeof($rows);
                for ($i = $cnt - 1; $i >= 0; $i--) {
                    $new_rows[] = $rows[$i];
                }
                $ret["rows"] = $new_rows;
            }
        }
        $ret["page"] = $this->page;
        return $ret;
    }

    /**
     * 向数据库发送操作请求
     *
     *
     * @param string $sql 要操作的 SQL 语句
     * @return false or true or rowcount
     *
     */
    public function query($sql)
    {
        return $this->execute($sql);
    }


    public function select($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'rows');
    }


    public function insert($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'lastInsertId');
    }


    public function update($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'rowCount');
    }

    public function delete($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'rowCount');
    }


    public function executeSql($sql, $bindings = [], $fetch = false)
    {
        if (!is_resource($this->connection) && !$this->connect()) {
            return false;
        }

        if (!empty($this->_config['table_alias'])) {
            $sql = preg_replace('/([from|update|into]\s+)`?' . $this->_config['table'] . '`? ?/is', '\1`' . $this->_config['table_alias'] . '` ', $sql, 2);
        }

        $result   = false;

        $sth      = $this->connection->prepare($sql);
        if (!($sth instanceof \PDOStatement)) {
            $this->setLastError('prepare sql error:'.$sql);
        }

        $sth->execute($this->prepareBindings($bindings));

        if ($sth->errorCode() != '00000') {
            $this->setLastError($sql.':'. json_encode($bindings) .':'.implode(',',$sth->errorInfo()), $sth->errorCode());
            return $result;
        }

        if ($fetch !== false) {

            switch ($fetch) {
                case 'value':
                    $result = $sth->fetchColumn();
                    break;
                case 'row':
                    $result = $sth->fetch(\PDO::FETCH_ASSOC);
                    break;
                case 'rows':
                    $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                case 'lastInsertId':
                    $result = $this->connection->lastInsertId();
                    break;
                case 'rowCount':
                    $result = $sth->rowCount();
                    break;
                default:
                    $result = $sth;
                    break;
            }
        } else {
            $type = strtolower(substr(trim($sql), 0, 6));
            switch ($type) {
                case 'update':
                case 'delete':
                    $result = $sth->rowcount();//返回影响的行数
                    break;
                case 'insert':
                    $result = $this->connection->lastInsertId();
                    break;
                case 'select':
                    $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                default:
                    break;
            }
        }

        $sth = null;

        if ($this->auto_close_mysql_link) {
            $this->close();
        }
        return $result;
    }


    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $bindings[$key] = $value->format('Y-m-d H:i:s');
            } elseif ($value === false) {
                $bindings[$key] = 0;
            }
        }
        return $bindings ?$bindings : null;
    }

    public function queryWithReturnInsertId($sql, $bindings = [])
    {
        return $this->insert($sql, $bindings);
    }

    public function getRow($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'row');
    }

    public function getRows($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'rows');
    }

    public function getValue($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'value');
    }

    public function getColumn($sql, $bindings = [])
    {
        return $this->executeSql($sql, $bindings, 'value');
    }

    public function execute($sql, $bindings = [], $fetch = false)
    {
        return $this->executeSql($sql, $bindings, $fetch);
    }

    /**
     * 返回调试信息中的数据
     *
     * @param void
     * @return null or array()
     *
     */
    public function getLogs()
    {
        if ($this->external_logger) {
            return null;
        } else {
            return $this->logs;
        }
    }

    /**
     * 获取上次 SQL 操作后所影响的数据行数
     *
     * @param void
     * @return int
     *
     */
    public function affectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * 获取翻页数组
     *
     * @param void
     * @return array()
     *
     */
    public function getPage()
    {
        return $this->page;
    }

    public function close()
    {
        is_resource($this->connection);
        $this->connection = null;
    }


    public function dump($ret = false)
    {
        if (DATABASE_DEBUG) {
            if ($ret) {
                return $ret;
            } else {
                echo '<pre>', print_r($this, 1), '</pre>';
            }
        }
    }


    /**
     * 记录调试信息
     *
     * @param string $message 信息内容
     * @return void
     *
     */
    protected function logDebug($message)
    {
        if ($message) {
            if ($this->external_logger) {
                $this->external_logger->debug($message);
            } else {
                $this->logs[] = $message;
            }
        }
    }

    /**
     * 记录错误日志
     *
     * @param string $message 日志信息
     * @return void
     *
     */
    protected function logError($message)
    {
        if ($message) {
            if ($this->external_logger) {
                $this->external_logger->error($message);
            } else {
                $this->logs[] = $message;
            }
        }
    }

    /**
     * 设置错误信息
     *
     * @param $message $msg 错误信息
     * @param int $code
     * @throws DBException
     */
    protected function setLastError($message, $code = 99)
    {
        $this->last_error = $message;
        $this->logError($message);
        throw new DBException($message, (int)$code);
    }

    /**
     * @param $table
     * @param null $hash
     * @return bool|DB
     * @throws \ReflectionException
     */
    protected static function table($table, $hash = null)
    {
        return DB::getInstance("SDK\\Libraries\\ConfigLoader::db")->load($table, $hash);
    }

    /**
     * @param $table
     * @return Builder
     */
    public function from($table=null) {
        if (!$table) {
            $table = $this->_config['table'];
        }
        return (new Builder($this))->from($table);
    }

    /**
     * @param $table
     * @param null $hash
     * @return Builder
     * @throws DBException
     * @throws \ReflectionException
     */
    public static function builder($table, $hash = null)
    {
        if (!$table) {
            throw new DBException('builder table 不能为空');
        }
        $config = ConfigLoader::parseTable($table);
        return (new Builder(self::table($table, $hash)))->from($config['table']);
    }
}

class DBException extends \Exception
{
}
