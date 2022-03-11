
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
    const MODULE_DIR = '../Module/';
    const MODEL_DIR = '../Model/';
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
        $this->MODULE_DIR = self::MODULE_DIR.ucfirst($config['database_sort']);
        $this->MODEL_DIR = self::MODEL_DIR.ucfirst($config['database_sort']);
        $this->database = $config['database'];
        $this->database_sort = $config['database_sort'];

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
        $this->givenTables = $config['givenTables'];
        $this->module = $this->classMoule . ucfirst($this->database_sort) . '_';
        $this->model = $this->classModel . ucfirst($this->database_sort) . '_';

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
        if (!file_exists($this->MODULE_DIR)) {
            mkdir($this->MODULE_DIR);
        }
        if (!file_exists($this->MODEL_DIR)) {
            mkdir($this->MODEL_DIR);
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
        $fileName = $this->MODULE_DIR . "/$table.php";
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
        $this->writeToFile('use Lib_BaseTraitsModule;' . "\n", 1);
        $this->generateModuleConstruct($table);
        $this->generateModuleGetInstance($table);

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
        $fileName = $this->MODEL_DIR . "/$table.php";
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
        $this->writeToFile('use Lib_BaseTraitsModel;' . "\n", 1);
        $this->writeToFile('private  $table=\''.$table .'\';'. "\n", 1);
        $this->writeToFile('private static $tag = \''. $this->model.$table.'\';', 1);
        $this->generateModelConstruct();




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
     */
    private function generateModelConstruct()
    {
        $this->writeToFile('/**', 1);
        $this->writeToFile('* @desc 封闭构造', 1);
        $this->writeToFile('*/', 1);
        $this->writeNewLine();
        $this->writeToFile("public function __construct() ", 1);
        $this->writeToFile("{", 1);
        $this->writeToFile('// 选择连接的数据库', 2);
        $this->writeToFile("parent::_init('{$this->database}');", 2);
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
        $this->writeToFile('self::$obj = new self();', 3);
        $this->writeToFile("}", 2);
        $this->writeToFile('return self::$obj;', 2);
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

$environment = getopt('d:s:t:');
$database = (string)$environment['d'];
$database_sort = (string)$environment['s'];
$givenTables = (string)$environment['t'];

if (empty($database)) {
    echo '缺少参数-d' . PHP_EOL;
    exit;
}

if (empty($givenTables)) {
    echo '缺少参数-d' . PHP_EOL;
    exit;
}
if (empty($database_sort)) {
    $database_sort = $database;
}


if ($givenTables == 'all') {
    $givenTables = '';
} else {
    $givenTables = (array)$givenTables;
}
$gen = new TableClassGenerator(array(
    'excludedProperties' => array(),
    'database' => $database,
    'database_sort' => $database_sort,
    'host' => '172.16.3.10',
    'port' => '3357',
    'parentModuleClass' => 'Module_BaseModule',
    'parentModelClass' => 'Module_BaseModel',
    'password' => '123456',
    'tables' => array(),
    'user' => 'wdty',
    'class_module' => 'Module_',
    'class_model' => 'Model_',
    'givenTables' => $givenTables,
));


$gen->generateClasses();

/**
 * -d 数据库名
 * -s module,model 下的 文件路径, 默认是数据库名
 * -t all 该数据库下全部表  table 具体表名(例如:admin 表)
 * php72 artisan.php -d yx_court_web -s web -t all
 * php72 artisan.php -d yx_court_web -s web -t admin
 */
