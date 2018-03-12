<?php  
/**
 * 生成棋牌游戏规则的 类 _open_room_sub 的内容  
 */  


error_reporting(7);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'on');

class RuleGenerator
{
	const DEFAULT_DIR = './rule';
	const DEFAULT_INDENT = 4;
	const DEFAULT_MIN = 1;
	private $file;
	private $class_name;
	private $parent_class;
	private $content_explode = array();
	private $content_arr = array();

	public function __construct($config) 
	{
		if (!isset($config) || empty($config) || !is_array($config))
		{
			die('Invalid config: '. print_r(__LINE__, true));
		}

		$this->class_name = trim($config['class_name']);
		$this->parent_class = trim($config['parent_class']);
		$this->content_explode = explode("\n", trim($config['content']));

		if(!is_array($this->content_explode))
		{
			die('Invalid config content: '. print_r(__LINE__, true));
		}

		foreach ($this->content_explode as $value)
		{
			$tmp_arr = explode('|', trim($value));
			if(empty($tmp_arr) || !is_array($tmp_arr) || empty($tmp_arr[1]) || '---' == $tmp_arr[1])
			{
				continue;
			}

			$tmp_arr[1] = trim($tmp_arr[1]);
			$tmp_arr[2] = trim($tmp_arr[2]);
			$tmp_arr[3] = trim($tmp_arr[3]);

			if(strpos($tmp_arr[1], '*') === 0)
			{
				$tmp_arr[1] = substr($tmp_arr[1], 1);
				$tmp_arr[4] = 1;
			}

			$this->content_arr[] = $tmp_arr;
		}


		if (! file_exists(self::DEFAULT_DIR)) 
		{
			mkdir(self::DEFAULT_DIR, 0777, true);
		}
	}

	public function __destroy() 
	{
	}

	public function do() 
	{
		if (empty($this->content_arr)) 
		{
			die('Empty content_arr: '. print_r(__LINE__, true));
		}

		$this->_do_rule_class();
		$this->_do_open_room_func();
	}

	private function _do_rule_class() 
	{
		$file_name = self::DEFAULT_DIR . "/{$this->class_name}.php";
		
		$this->file = fopen($file_name, 'w');

		if (!isset($this->file)) 
		{
			die("Failed to open file: $file_name");
		}

		$this->writeToFile("<?php");
		$this->writeToFile("namespace gf\inc;\n");

		if ($this->parent_class) 
		{
			$this->writeToFile("class {$this->class_name} extends {$this->parent_class}");
			$this->writeToFile("{");
		} 
		else 
		{
			$this->writeToFile("class {$this->class_name}");
			$this->writeToFile("{");
		}

		//类变量
		$j = 0;
		foreach ($this->content_arr as $val)
		{
			if($j%5 == 0 && $j != 0)
			{
				$this->writeNewLine();
			}
			$j++;
			
			$this->writeToFile("public $".$val[1].";	//".$val[2]."", 1);
		}
		$this->writeNewLine();

		//function clear
		$this->writeToFile("public function clear()", 1);
		$this->writeToFile("{", 1);
		$j = 0;
		foreach ($this->content_arr as $val)
		{
			if($j%5 == 0 && $j != 0)
			{
				$this->writeNewLine();
			}
			$j++;
			
			$this->writeToFile('$this->'.$val[1]." = ".$val[3].";", 2);
		}
		$this->writeToFile("}", 1);
		$this->writeNewLine();

		//function __construct 
		$this->writeToFile("public function __construct()", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile('$this->clear();', 2);
		$this->writeToFile("}", 1);

		$this->writeToFile("}");
		$this->writeNewLine();

		fclose($this->file);
		echo "Class {$this->class_name} was created in the file($file_name).\n\n";
	}

	private function _do_open_room_func()
	{
		$is_player_count = false;
		$is_is_circle = false;
		$is_is_score_field = false;

		$file_name = $this->class_name.'_GameXXXXXX';
		$file_name = self::DEFAULT_DIR . "/{$file_name}.php";
		
		$this->file = fopen($file_name, 'w');

		if (!isset($this->file)) 
		{
			die("Failed to open file: $file_name");
		}

		$this->writeToFile("<?php");
		$this->writeToFile("namespace gf\inc;\n");
		$this->writeNewLine();

		$this->writeToFile('class GameXXXXXX extends BaseGame');
		$this->writeToFile("{");
		$this->writeNewLine();

		$this->writeToFile('public function _open_room_sub($params)', 1);
		$this->writeToFile("{", 1);

		$this->writeToFile('$this->m_rule = new '.$this->class_name.'();', 2);
		$this->writeNewLine();

		$j = 0;
		foreach ($this->content_arr as $val)
		{
			if($j%5 == 0 && $j != 0)
			{
				$this->writeNewLine();
			}
			$j++;
			
			$this->writeToFile('$params[\'rule\'][\''.$val[1].'\'] = isset($params[\'rule\'][\''.$val[1].'\']) ? $params[\'rule\'][\''.$val[1].'\']: '.$val[3].';', 2);

			if($val[1] == 'player_count')
			{
				$is_player_count = true;
			}
			if($val[1] == 'is_circle')
			{
				$is_is_circle = true;
			}
			if($val[1] == 'is_score_field')
			{
				$is_is_score_field = true;
			}
		}

		if($is_player_count)
		{
			$this->writeNewLine();
			$this->writeToFile('if(empty($params[\'rule\'][\'player_count\']) || !in_array($params[\'rule\'][\'player_count\'], array(1, 2, 3, 4)))', 2);
			$this->writeToFile('{', 2);
			$this->writeToFile('$params[\'rule\'][\'player_count\'] = 4;', 3);
			$this->writeToFile('}', 2);
		}

		if($is_is_score_field && $is_is_circle)
		{
			$this->writeNewLine();
			$this->writeToFile('if(($params[\'rule\'][\'is_circle\']) && ($params[\'rule\'][\'is_score_field\'])) ', 2);
			$this->writeToFile('{', 2);
			$this->writeToFile('$params[\'rule\'][\'is_circle\'] = 0;', 3);
			$this->writeToFile('}', 2);
		}

		if($is_is_circle && $is_player_count)
		{
			$this->writeNewLine();
			$this->writeToFile('if(($params[\'rule\'][\'is_circle\']))', 2);
			$this->writeToFile('{', 2);
			$this->writeToFile('$params[\'rule\'][\'set_num\'] = $params[\'rule\'][\'is_circle\'] * $params[\'rule\'][\'player_count\'];', 3);
			$this->writeToFile('}', 2);
		}				

		$this->writeNewLine();
		$this->writeToFile("///////////////////////////////////////////////////", 2);
		$this->writeNewLine();

		$j = 0;
		foreach ($this->content_arr as $val)
		{
			if($j%5 == 0 && $j != 0)
			{
				$this->writeNewLine();
			}
			$j++;
			
			$this->writeToFile('$this->m_rule->'.$val[1].' = $params[\'rule\'][\''.$val[1].'\'];', 2);
		}	

		$this->writeToFile("}", 1);
		$this->writeToFile("}");
		fclose($this->file);
		echo "Class {$this->class_name} was created in the file($file_name).\n\n";
	}

	////////////////////////

	private function writeNewLine() 
	{
		$this->writeToFile('');
	}

	private function writeToFile($str, $count = 0)
	{
		$space = null;
		$count *= self::DEFAULT_INDENT;
		while ($count) 
		{
			if ($space == null) 
			{
				$space = ' ';
			} 
			else 
			{
				$space .= ' ';
			}
			$count--;
		}
		fwrite($this->file, $space);
		fwrite($this->file, "$str\n");
	}	
}

$gen = new RuleGenerator
	(
		array
		(
			'class_name' => 'RuleXueZhan'	//类名
			,'parent_class' => ''		//父类
			,'content' => 
				'|---|---|---
				|game_type|游戏类型|262
				|player_count|玩家人数: (2:2人, 3:3人, 4:4人) | 4
				|set_num|房间局数: (4:4局3钻, 8:8局4钻, 16:16局6钻) | 8
				|min_fan|胡牌最小番 | 0
				|top_fan|封顶番数: (0:不封顶, 1024:封顶1024番) | 1024
				|---|---|---
				|is_circle|按圈打: (0:不按圈打, 1:一圈, 2:两圈, 4:四圈) | 0
				|zimo_rule| 自摸规则:(0:自摸加底, 1:自摸加番)|1
				|dian_gang_hua|点杠花: (0:点炮, 1:自摸)|1
				|is_change_3|换三张:(0:否, 1:是)|1
				|is_yaojiu_jiangdui|幺九将对:(0:否, 1:是)|1
				|---|---|---
				|is_menqing_zhongzhang|门清中张:(0:否, 1:是)|1
				|is_tiandi_hu|天地胡:(0:否, 1: 是)|1
				|*is_feng| 带风牌: (0:否,1:是) | 0
				|*is_yipao_duoxiang | 一炮多响: (0:否,1:是) | 0
				|*is_chi| 带吃牌: (0:否,1:是) | 0
				|---|---|---
				|*is_genzhuang| 带跟庄: (0:否,1:是) | 0
				|*is_qingyise_fan | 清一色: (0:否,1:是) | 1
				|*is_ziyise_fan | 字一色: (0:否,1:是) | 0
				|*is_yitiaolong_fan | 一条龙: (0:否,1:是) | 0
				|*is_ganghua_fan | 杠上开花: (0:否,1:是) | 1
				|---|---|---
				|*is_qidui_fan | 七对: (0:否,1:是) | 1
				|*is_pengpenghu_fan | 碰碰胡: (0:否,1:是) | 1
				|pay_type | 付费方式: (0:房主付费, 1:AA付费, 2:大赢家付费) | 1
				|is_score_field | 是否积分场: (0:普通场,1:积分场) | 0
				|score | 积分场底分: (100, 500, 2500, 8000, 15000, 40000, 100000, 0) | 100
				|---|---|---'
		)
	);


$gen->do();



