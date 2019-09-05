<?php
/**
 * 这个类的作用是从数据库里读出表结构，然后生成一个bean类，并将其属性与类一一映射。
 * 具体生成的内容包括：
 * 1. 私有变量
 * 2. 表字段与属性的映射关系
 * 3. 表字段的信息，用于server的验证
 */


error_reporting(7);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'on');

class TableClassGenerator
{
    const MODULE_DIR = './Module';
    const MODEL_DIR = './Model';
    const DEFAULT_INDENT = 4;
    const DEFAULT_MIN = 1;
    private $excludedProperties;
    private $database;
    private $file;
    private $givenTables;
    private $parentModuleClass;
    private $parentModelClass;
    private $module;
    private $model;

    public function __construct($config)
    {
        if (!isset($config) || empty($config) || !is_array($config)) {
            die('Invalid config: ' . print_r($config, true));
        }

        $this->database = $config['database'];
        global $conn;
        $conn = isset($config['password'])
            ? @mysqli_connect($config['host'], $config['user'], $config['password'], $config['database'], $config['port'])
            : @mysqli_connect($config['host'], $config['user']);
        if (!isset($conn)) {
            die('Failed to connect.' . mysqli_error());
        }

        mysqli_query($conn, "set names 'utf8'");
        mb_internal_encoding('utf-8');

        $this->classMoule = $config['class_module'];
        $this->classModel = $config['class_model'];
        $this->module = $this->classMoule . ucfirst($this->database) . '_';
        $this->model = $this->classModel . ucfirst($this->database) . '_';

        if (isset($this->givenTables)
            && (!is_array($this->givenTables)
            )) {
            echo("Tables(" . json_encode($this->givenTables) . ") in config is not an array.");
        }

        $this->parentModuleClass = $config['parentModuleClass'];
        $this->parentModelClass = $config['parentModelClass'];

        if ($config['excludedProperties']) {
            $this->excludedProperties = $config['excludedProperties'];
            if (!is_array($this->excludedProperties)
                || empty($this->excludedProperties)) {
                die('excludedProperties should be an array and shoudnot be empty.');
            }
        }
        if (!file_exists(self::MODULE_DIR)) {
            mkdir(self::MODULE_DIR);
        }
        if (!file_exists(self::MODEL_DIR)) {
            mkdir(self::MODEL_DIR);
        }
    }

    public function __destroy()
    {
        mysqli_close();
    }

    public function generateClasses()
    {
        $allTables = $this->getTables();

        $tables = $this->givenTables
            ? $this->givenTables
            : $allTables;

        if (empty($tables)) {
            die("Empty given tables");
        }

        foreach ($tables as $table) {
            $index = array_search($table, $allTables);
            if (!is_int($index)) {
                echo "Table($table) not found in database({$this->database}).\n";
                continue;
            }

            $this->generateModuleClassForTable($table);
            $this->generateModelClassForTable($table);
        }
    }

    private function generateModuleClassForTable($table)
    {
        //表的名字
        $table = ucfirst($this->transform($table));

        //文件名
        $fileName = self::MODULE_DIR . "/$table.php";
        $this->file = fopen($fileName, 'w');

        if (!isset($this->file)) {
            die("Failed to open file: $fileName");
        }

        echo "Generating class for table: $table.\n";
        $this->writeToFile("<?php");

        if ($this->parentModuleClass) {
            $this->writeToFile("class {$this->module}{$table} extends {$this->parentModuleClass}\n{");
        } else {
            $this->writeToFile("class {$this->module}{$table} \n{");
        }
        $this->writeToFile('private static $obj  = null;' . "\n", 1);
        $this->writeToFile('private static $model = null;' . "\n", 1);
        $this->generateModuleConstruct($table);
        $this->generateModuleGetInstance($table);
        $this->generateModuleInsert();
        $this->generateModuleUpdate();
        $this->generateModuleDelete();
        $this->writeToFile("}");
        $this->writeNewLine();

        fclose($this->file);
        echo "Class($table) was created in the file($fileName).\n\n";
    }
    private function generateModelClassForTable($table)
    {
        //表的名字
        $table = ucfirst($this->transform($table));

        //文件名
        $fileName = self::MODEL_DIR . "/$table.php";
        $this->file = fopen($fileName, 'w');

        if (!isset($this->file)) {
            die("Failed to open file: $fileName");
        }

        echo "Generating class for table: $table.\n";
        $this->writeToFile("<?php");

        if ($this->parentModelClass) {
            $this->writeToFile("class {$this->model}{$table} extends {$this->parentModelClass}\n{");
        } else {
            $this->writeToFile("class {$this->model}{$table} \n{");
        }

        $this->writeToFile('private static $cache_prefix = \''. $this->model.$table.'\';', 1);
        $this->generateModelConstruct($table);


        $this->generateModelInsert($table);
        $this->generateModelUpdate($table);
        $this->generateModelDelete($table);



        $this->writeToFile("}");
        $this->writeNewLine();

        fclose($this->file);
        echo "Class($table) was created in the file($fileName).\n\n";
    }

    /**
     * @desc 构造Construct
     * @param $table
     */
    private function generateModuleConstruct($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc 封闭构造', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile("public function __construct() ", 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('$this->model = new ' . $this->model . $table . '();', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }

    /**
     * @desc 构造Construct
     * @param $table
     */
    private function generateModelConstruct($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc 封闭构造', 1);
        $this->writeToFile('*/', 1);
        $this->writeNewLine();
        $this->writeToFile("public function __construct() ", 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('// 选择连接的数据库', 2);
        $this->writeToFile("parent::_init('{$table}');", 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();

    }
    /**
     * @desc 构造getInstance
     * @param $table
     */
    private function generateModuleGetInstance($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* 单例获取', 1);
        $this->writeToFile('* 保证一条进程只产生一个Module对象', 1);
        $this->writeToFile('* @return '.$this->module . $table, 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile("public static function getInstance() ", 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('if (empty (self::$obj)) {', 2);
        $this->writeToFile('self::$obj = new ' . $this->module . $table . '();', 3);
        $this->writeToFile("}", 2);
        $this->writeToFile('return self::$obj;', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }


    /**
     * @desc insert 新增
     */
    private function generateModuleInsert()
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc insert 新增', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function insert($insertData) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('return $this->model->insert($insertData);', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }

    /**
     * @desc insert 新增
     */
    private function generateModelInsert($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc insert 新增', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function insert($insertdata) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('$data = array();', 2);
        $this->writeToFile('$sql = \'INSERT INTO `'.$table.'`(\';', 2);
        $this->writeToFile(' $sql .= \'`\' . implode(\'`,`\', array_keys($insertdata)) . \'`\';', 2);
        $this->writeToFile('$sql .= \')VALUES(:\' . implode(\',:\', array_keys($insertdata)) . \')\';', 2);
        $this->writeToFile('foreach ($insertdata as $key => $value) {', 2);
        $this->writeToFile('$data[\':\' . $key] = $value;', 2);
        $this->writeToFile('$data[\':\' . $key] = $value;', 2);
        $this->writeToFile("}", 2);
        $this->writeToFile('return $this->dao->conn(false)->noCache()->preparedSql($sql, $data)->affectedCount();', 2);

        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }

    /**
     * @desc 更新操作
     */
    private function generateModelUpdate($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc 更新操作', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function update($update, $id) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('$data = [];', 2);
        $this->writeToFile(' $sql = "UPDATE ' .$table . ' SET ";', 2);
        $this->writeToFile('$sqlarr = array();', 2);
        $this->writeToFile('foreach ($update as $key => $value) {', 2);
        $this->writeToFile('array_push($sqlarr, "`{$key}`=:$key");', 2);
        $this->writeToFile('$data [":$key"] = $value;', 2);
        $this->writeToFile("}", 2);
        $this->writeToFile('$sql .= implode(\',\', $sqlarr);', 2);
        $this->writeToFile('$sql .= " WHERE uid=:id";', 2);
        $this->writeToFile('$data[\':id\'] = $id;', 2);
        $this->writeToFile('$res = $this->dao->conn(false)->noCache()->preparedSql($sql, $data)->affectedCount();', 2);
        $this->writeToFile('$this->dao->clearTag(self::$cache_prefix);', 2);
        $this->writeToFile('return $res;', 2);

        $this->writeToFile("}", 1);
        $this->writeNewLine();

    }


    /**
     * @desc 更新操作
     */
    private function generateModuleUpdate()
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc 更新操作', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function update($update, $id) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('return $this->model->insert($update, $id);', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }

    /**
     * @del delete删除
     */
    private function generateModuleDelete()
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* delete删除', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function del($id) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('return $this->model->del($id);', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }   /**
     * @del delete删除
     */
    private function generateModelDelete($table)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* delete删除', 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('public function delete($id) ', 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('$sql = \'DELETE FROM `'.$table.'` WHERE `id`=:id\';', 2);
        $this->writeToFile('$data = array(', 2);
        $this->writeToFile('\':id\'=> $id', 2);
        $this->writeToFile(');', 2);
        $this->writeToFile(' return $this->dao->conn(false)->noCache()->preparedSql($sql, $data)->affectedCount();', 2);
        $this->writeToFile("}", 1);
        $this->writeNewLine();
    }


    private function getTables()
    {
        global $conn;
        $sql = "SHOW TABLES FROM {$this->database}";
        $result = mysqli_query($conn, $sql);
        $tables = array();
        for ($i = 0; $i < mysqli_num_rows($result); $i++) {
            $tables[] = mysqli_fetch_array($result)[0];
        }
        return $tables;
    }


    private function transform($name)
    {
        $words = explode('_', $name);
        $newName = null;
        foreach ($words as $word) {
            if ($newName == null) {
                $newName = $word;
            } else {
                $newName .= ucfirst($word);
            }
        }

        return $newName;
    }

    private function writeNewLine()
    {
        $this->writeToFile('');
    }

    private function writeToFile($str, $count = 0)
    {
        $space = null;
        $count *= self::DEFAULT_INDENT;
        while ($count) {
            if ($space == null) {
                $space = ' ';
            } else {
                $space .= ' ';
            }
            $count--;
        }
        fwrite($this->file, $space);
        fwrite($this->file, "$str\n");
    }
}


$gen = new TableClassGenerator(array(
    'excludedProperties' => array(),
    'database' => 'test',
    'host' => '192.168.9.102',
    'port' => '3357',
    'parentModuleClass' => 'TyModule_BaseModule',
    'parentModelClass' => 'TyModule_BaseModel',
    'password' => '123456',
    'tables' => array(),
    'user' => 'wdty',
    'class_module' => 'TyModule_',
    'class_model' => 'TyModel_',
));


$gen->generateClasses();