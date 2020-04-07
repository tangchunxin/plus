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

            $this->generateModelClassForTable($table);
        }
    }


    private function generateModelClassForTable($table)
    {
        $tableinfo = $this->getTableColumns($table);
        //file_put_contents('./log.txt',var_export($aa,1),FILE_APPEND);

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

        $this->writeToFile("class {$this->model}{$table} \n{");
        foreach ($tableinfo as $k => $v) {
            $this->makePrivate($k, $v);
        }
        foreach ($tableinfo as $k => $v) {
            $this->makeSet($k, $v);
            $this->makeGet($k, $v);
        }


        $this->writeToFile("}");
        $this->writeNewLine();

        fclose($this->file);
        echo "Class($table) was created in the file($fileName).\n\n";
    }

    public function makePrivate($k, $v)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile("*{$v['comment']}", 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile('private $_' . $k . ';', 1);
        $this->writeToFile('');
    }

    public function makeSet($k, $v)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile("* @param {$v['comment']} {$k}", 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile("public function set" . ucfirst(str_replace('_', '', $k)) . '($' . $k . ')', 1);
        $this->writeToFile('{', 1);
        $this->writeToFile('$this->_' . $k . " = $" . $k . ";", 2);
        $this->writeToFile('}', 1);
        $this->writeToFile('');
    }

    public function makeGet($k, $v)
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile("* @param {$v['comment']} {$k}", 1);
        $this->writeToFile('*/', 1);
        $this->writeToFile("public function get" . ucfirst(str_replace('_', '', $k)) . '()', 1);
        $this->writeToFile('{', 1);
        $this->writeToFile('$this->_' . $k . ";", 2);
        $this->writeToFile('}', 1);
        $this->writeToFile('');
    }


    public function getTableColumns($table)
    {
        global $conn;
        $fields = $this->_mysql_list_fields($table);
        //$count = mysql_num_fields($fields);
        $count = count($fields);
        if (!isset($fields)) {
            die("Failed to get fields" . mysqli_error());
        }

        $comment = array();
        $result = mysqli_query($conn, "show full fields from $table");
        while (($row = mysqli_fetch_assoc($result)) != false) {
            $comment[$row['Field']] = $row['Comment'];
        }

        $columns = array();
        for ($i = 0; $i < $count; $i++) {
            $flags = $this->_mysql_field_flags($fields, $i);
            $isRequired = preg_match('/NO/', $flags) ? 'not_null' : '';
            $is_primary_key = preg_match('/PRI/', $flags) ? 'primary_key' : '';
            $is_auto_increment = preg_match('/auto_increment/', $flags) ? 'auto_increment' : '';

            $col = $this->_mysql_field_name($fields, $i);
            $max = $this->_mysql_field_len($fields, $i);
            $type = $this->_mysql_field_type($fields, $i);
            $min = $this->getMin($max, $type);

            $columns[$col] = array(
                'isRequired' => $isRequired,
                'max' => $max,
                'min' => $min,
                'type' => $type,
                'comment' => $comment[$col],
                'is_primary_key' => $is_primary_key,
                'is_auto_increment' => $is_auto_increment,
            );

        }

        $sortedColumns = array();
        $keys = array_keys($columns);
        //		sort($keys);
        foreach ($keys as $key) {
            $sortedColumns[$key] = $columns[$key];
        }
        return $sortedColumns;
    }

    private function getMin($max, $type)
    {
        if (!isset($max)) {
            return null;
        }

        $min = self::DEFAULT_MIN;
        if ($type == 'date' || $min > $max) {
            $min = $max;
        }
        return $min;
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

    private function _mysql_list_fields($table)
    {
        global $conn;
        mysqli_query($conn, 'use ' . $this->database);
        $result = mysqli_query($conn, "SHOW COLUMNS FROM " . $table);
        if (!$result) {
            die("Failed to get fields" . mysqli_error());
        }
        $fieldnames = array();
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $fieldnames[] = $row;
            }
        }
        return $fieldnames;
    }

    private function _mysql_field_flags($fields, $i)
    {
        if (isset($fields) && $i >= 0) {
            return $fields[$i]['Null'] . ' ' . $fields[$i]['Key'] . ' ' . $fields[$i]['Extra'];
        } else {
            return null;
        }

    }

    private function _mysql_field_name($fields, $i)
    {
        if (isset($fields) && $i >= 0) {
            return $fields[$i]['Field'];
        } else {
            return null;
        }
    }

    private function _mysql_field_len($fields, $i)
    {
        if (isset($fields) && $i >= 0) {
            return substr($fields[$i]['Type'], strpos($fields[$i]['Type'], '(') + 1, (strpos($fields[$i]['Type'], ')') - strpos($fields[$i]['Type'], '(') - 1));;
        } else {
            return null;
        }
    }

    private function _mysql_field_type($fields, $i)
    {
        if (isset($fields) && $i >= 0) {
            return preg_match('/int/', $fields[$i]['Type']) ? 'int' : (preg_match('/char/', $fields[$i]['Type']) ? 'string' : (preg_match('/text/', $fields[$i]['Type']) ? 'text' : 'real'));
        } else {
            return null;
        }
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
    'database' => 'p2peye_financial',
    'host' => '192.168.2.10',
    'port' => '3357',
    'parentModelClass' => 'TyLib_BankFinanc_Entities_',
    'password' => '123456',
    'tables' => array(),
    'user' => 'wdty',
    'class_module' => 'TyModule_',
    'class_model' => 'TyModel_',
));


$gen->generateClasses();
