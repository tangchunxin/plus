<?php
/**
 * @abstract creat mahjong HU data
 * @author xuqiang76@163.com
 * @final 20161113
 */

function logger($file, $file_feng, $file_feng_shun)
{
	$type = [
	[]
	,[1,2,3],[2,3,4],[3,4,5],[4,5,6],[5,6,7],[6,7,8],[7,8,9]
	,[1,1,1],[2,2,2],[3,3,3],[4,4,4],[5,5,5],[6,6,6],[7,7,7],[8,8,8],[9,9,9]
	];

	$type_feng = [
	[]
	,[1,1,1],[2,2,2],[3,3,3],[4,4,4],[5,5,5],[6,6,6],[7,7,7]
	];

	//中发白算顺子
	$type_feng_shun = [
	[]
	,[1,1,1],[2,2,2],[3,3,3],[4,4,4],[5,5,5],[6,6,6],[7,7,7]
	,[5,6,7]
	];	

	$hu_arr = [];
	for($i=0; $i<=16; $i++)
	{
		for($j=0; $j<=16; $j++)
		{
			for($k=0; $k<=16; $k++)
			{
				for($m=0; $m<=16; $m++)
				{
					for($n=0; $n<=9; $n++)
					{
						$type_3_arr = null;
						$item = null;
						$type_3_arr = [0,0,0,0,0,0,0,0,0];
						foreach ($type[$i] as $item)
						{
							$type_3_arr[$item-1] += 1;
						}
						foreach ($type[$j] as $item)
						{
							$type_3_arr[$item-1] += 1;
						}
						foreach ($type[$k] as $item)
						{
							$type_3_arr[$item-1] += 1;
						}
						foreach ($type[$m] as $item)
						{
							$type_3_arr[$item-1] += 1;
						}
						if($n)
						{	//将牌
							$type_3_arr[$n-1] += 2;
						}

						//去除非法的
						if(max($type_3_arr) > 5)
						{
							continue;
						}

						$key = intval((implode('', $type_3_arr)));
						$val = 1;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
						if(max($type_3_arr) >= 4)
						{
							$gen = count(array_keys( $type_3_arr , 4 ));
							$val = $val | (4096*$gen);
						}

						if($n)
						{
							$val = $val | 32;
						}
						
						if(in_array($n, [2, 5, 8]))
						{
							$val = $val | 512;
						}

						if( (in_array(1, [$i,$j,$k,$m]))
						&& (in_array(4, [$i,$j,$k,$m]))
						&& (in_array(7, [$i,$j,$k,$m]))
						)
						{
							$val = $val | 256;
						}

						if( (!in_array($i, [1,7,8,16]))
						&& (!in_array($j, [1,7,8,16]))
						&& (!in_array($k, [1,7,8,16]))
						&& (!in_array($m, [1,7,8,16]))
						&& (!in_array($n, [1,9]))
						)
						{
							$val = $val | 16;
						}

						if( ($i>=8)
						|| ($j>=8)
						|| ($k>=8)
						|| ($m>=8)
						)
						{
							$val = $val | 1024;
						}

						if( ($i < 8 && $i > 0)
						|| ($j < 8 && $j > 0)
						|| ($k < 8 && $k > 0)
						|| ($m < 8 && $m > 0)
						)
						{
							$val = $val | 2048;
						}					

						if( ($i==0 || $i>=8)
						&& ($j==0 || $j>=8)
						&& ($k==0 || $k>=8)
						&& ($m==0 || $m>=8)
						)
						{
							$val = $val | 8;
						}
						if( (in_array($i, [0,9,12,15]))
						&& (in_array($j, [0,9,12,15]))
						&& (in_array($k, [0,9,12,15]))
						&& (in_array($m, [0,9,12,15]))
						&& (in_array($n, [0,2,5,8]))
						)
						{
							$val = $val | 4;
						}
						if( (in_array($i, [0,1,7,8,16]))
						&& (in_array($j, [0,1,7,8,16]))
						&& (in_array($k, [0,1,7,8,16]))
						&& (in_array($m, [0,1,7,8,16]))
						&& (in_array($n, [0,1,9]))
						)
						{
							$val = $val | 2;
						}

						if(empty($hu_arr[$key]))
						{
							$hu_arr[$key] = $val;
						}
						else
						{
							$hu_arr[$key] = $hu_arr[$key] | $val;
						}
					}
				}
			}
		}
	}

	for($i=0; $i<=9; $i++)
	{
		for($j=0; $j<=9; $j++)
		{
			for($k=0; $k<=9; $k++)
			{
				for($m=0; $m<=9; $m++)
				{
					for($n=0; $n<=9; $n++)
					{
						for($o=0; $o<=9; $o++)
						{
							for($p=0; $p<=9; $p++)
							{
								$type_3_arr = null;
								$item = null;
								$type_3_arr = [0,0,0,0,0,0,0,0,0];

								if($i)
								$type_3_arr[$i-1] += 2;
								if($j)
								$type_3_arr[$j-1] += 2;
								if($k)
								$type_3_arr[$k-1] += 2;
								if($m)
								$type_3_arr[$m-1] += 2;
								if($n)
								$type_3_arr[$n-1] += 2;
								if($o)
								$type_3_arr[$o-1] += 2;
								if($p)
								$type_3_arr[$p-1] += 2;

								//去除非法的
								if(max($type_3_arr) > 5)
								{
									continue;
								}

								$key = intval((implode('', $type_3_arr)));
								$val = 0;
								$val = $val | 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
								$val = $val | 64;

								if(max($type_3_arr) >= 4)
								{
									$gen = count(array_keys( $type_3_arr , 4 ));
									$val = $val | (4096*$gen);
								}

								if( (!in_array($i, [1,9]))
								&& (!in_array($j, [1,9]))
								&& (!in_array($k, [1,9]))
								&& (!in_array($m, [1,9]))
								&& (!in_array($n, [1,9]))
								&& (!in_array($o, [1,9]))
								&& (!in_array($p, [1,9]))
								)
								{
									$val = $val | 16;
								}

								if((in_array($i, [0,2,5,8]))
								&& (in_array($j, [0,2,5,8]))
								&& (in_array($k, [0,2,5,8]))
								&& (in_array($m, [0,2,5,8]))
								&& (in_array($n, [0,2,5,8]))
								&& (in_array($o, [0,2,5,8]))
								&& (in_array($p, [0,2,5,8]))
								)
								{//全258
									$val = $val | 4;
								}
								if( (in_array($i, [0,1,9]))
								&& (in_array($j, [0,1,9]))
								&& (in_array($k, [0,1,9]))
								&& (in_array($m, [0,1,9]))
								&& (in_array($n, [0,1,9]))
								&& (in_array($o, [0,1,9]))
								&& (in_array($p, [0,1,9]))
								)
								{//幺九
									$val = $val | 2;
								}

								if(empty($hu_arr[$key]))
								{
									$hu_arr[$key] = $val;
								}
								else
								{
									$hu_arr[$key] = $hu_arr[$key] | $val;
								}
							}
						}
					}
				}
			}
		}
	}

	for($i=1; $i<=2; $i++)
	{
		for($j=1; $j<=2; $j++)
		{
			$type_3_arr = null;
			$item = null;
			$type_3_arr = [0,0,0,0,0,0,0,0,0];
			if($i == 2 && $j ==2)
			{
				continue;
			}
			$type_3_arr[0] += $i;
			$type_3_arr[8] += $j;

			$key = intval((implode('', $type_3_arr)));
			$val = 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
			$val = $val | 128;

			if(empty($hu_arr[$key]))
			{
				$hu_arr[$key] = $val;
			}
			else
			{
				$hu_arr[$key] = $hu_arr[$key] | $val;
			}
		}
	}

	$hu_feng_arr = [];
	for($i=0; $i<=7; $i++)
	{
		for($j=0; $j<=7; $j++)
		{
			for($k=0; $k<=7; $k++)
			{
				for($m=0; $m<=7; $m++)
				{
					for($n=0; $n<=7; $n++)
					{
						$type_feng_arr = null;
						$item = null;
						$type_feng_arr = [0,0,0,0,0,0,0,0,0];

						foreach ($type_feng[$i] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng[$j] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng[$k] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng[$m] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						if($n)
						{	//将牌
							$type_feng_arr[$n-1] += 2;
						}

						//去除非法的
						if(max($type_feng_arr) > 5)
						{
							continue;
						}

						$key = intval((implode('', $type_feng_arr)));
						$val = 1;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
						if(max($type_feng_arr) >= 4)
						{
							$gen = count(array_keys( $type_feng_arr , 4 ));
							$val = $val | (4096*$gen);
						}

						if($n)
						{
							$val = $val | 32;
						}

						//$val = $val | 16;

						$val = $val | 8;

						if( ($i>0)
						|| ($j>0)
						|| ($k>0)
						|| ($m>0)
						)
						{
							$val = $val | 1024;
						}

						//$val = $val | 4;

						$val = $val | 2;	//混幺九

						if(empty($hu_feng_arr[$key]))
						{
							$hu_feng_arr[$key] = $val;
						}
						else
						{
							$hu_feng_arr[$key] = $hu_feng_arr[$key] | $val;
						}
					}
				}
			}
		}
	}

	for($i=0; $i<=7; $i++)
	{
		for($j=0; $j<=7; $j++)
		{
			for($k=0; $k<=7; $k++)
			{
				for($m=0; $m<=7; $m++)
				{
					for($n=0; $n<=7; $n++)
					{
						for($o=0; $o<=7; $o++)
						{
							for($p=0; $p<=7; $p++)
							{
								$type_feng_arr = null;
								$item = null;
								$type_feng_arr = [0,0,0,0,0,0,0,0,0];

								if($i)
								$type_feng_arr[$i-1] += 2;
								if($j)
								$type_feng_arr[$j-1] += 2;
								if($k)
								$type_feng_arr[$k-1] += 2;
								if($m)
								$type_feng_arr[$m-1] += 2;
								if($n)
								$type_feng_arr[$n-1] += 2;
								if($o)
								$type_feng_arr[$o-1] += 2;
								if($p)
								$type_feng_arr[$p-1] += 2;

								//去除非法的
								if(max($type_feng_arr) > 5)
								{
									continue;
								}

								$key = intval((implode('', $type_feng_arr)));
								$val = 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
								$val = $val | 64;

								if(max($type_feng_arr) >= 4)
								{
									$gen = count(array_keys( $type_feng_arr , 4 ));
									$val = $val | (4096*$gen);
								}

								//$val = $val | 16;

								//$val = $val | 4;

								$val = $val | 2;

								if(empty($hu_feng_arr[$key]))
								{
									$hu_feng_arr[$key] = $val;
								}
								else
								{
									$hu_feng_arr[$key] = $hu_feng_arr[$key] | $val;
								}
							}
						}
					}
				}
			}
		}
	}

	for($i=0; $i<=7; $i++)
	{
		$type_feng_arr = null;
		$item = null;
		$type_feng_arr = [1,1,1,1,1,1,1,0,0];
		if($i)
		{
			$type_feng_arr[$i - 1] += 1;
		}

		$key = intval((implode('', $type_feng_arr)));
		$val = 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
		$val = $val | 128;

		if(empty($hu_feng_arr[$key]))
		{
			$hu_feng_arr[$key] = $val;
		}
		else
		{
			$hu_feng_arr[$key] = $hu_feng_arr[$key] | $val;
		}
	}

	$hu_feng_shun_arr = [];
	for($i=0; $i<=8; $i++)
	{
		for($j=0; $j<=8; $j++)
		{
			for($k=0; $k<=8; $k++)
			{
				for($m=0; $m<=8; $m++)
				{
					for($n=0; $n<=7; $n++)
					{
						$type_feng_arr = null;
						$item = null;
						$type_feng_arr = [0,0,0,0,0,0,0,0,0];

						foreach ($type_feng_shun[$i] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng_shun[$j] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng_shun[$k] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						foreach ($type_feng_shun[$m] as $item)
						{
							$type_feng_arr[$item-1] += 1;
						}
						if($n)
						{	//将牌
							$type_feng_arr[$n-1] += 2;
						}

						//去除非法的
						if(max($type_feng_arr) > 5)
						{
							continue;
						}

						$key = intval((implode('', $type_feng_arr)));
						$val = 1;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
						if(max($type_feng_arr) >= 4)
						{
							$gen = count(array_keys( $type_feng_arr , 4 ));
							$val = $val | (4096*$gen);
						}

						if($n)
						{
							$val = $val | 32;
						}

						//$val = $val | 16;
						
						if( ($i==0 || $i<8)
						&& ($j==0 || $j<8)
						&& ($k==0 || $k<8)
						&& ($m==0 || $m<8)
						)
						{
							$val = $val | 8;
						}

						if( ($i>0 && $i<8)
						|| ($j>0 && $j<8)
						|| ($k>0 && $k<8)
						|| ($m>0 && $m<8)
						)
						{
							$val = $val | 1024;
						}

						if( ($i >= 8)
						|| ($j >= 8)
						|| ($k >= 8)
						|| ($m >= 8)
						)
						{
							$val = $val | 2048;
						}

						//$val = $val | 4;

						$val = $val | 2;	//混幺九

						if(empty($hu_feng_shun_arr[$key]))
						{
							$hu_feng_shun_arr[$key] = $val;
						}
						else
						{
							$hu_feng_shun_arr[$key] = $hu_feng_shun_arr[$key] | $val;
						}
					}
				}
			}
		}
	}

	for($i=0; $i<=7; $i++)
	{
		for($j=0; $j<=7; $j++)
		{
			for($k=0; $k<=7; $k++)
			{
				for($m=0; $m<=7; $m++)
				{
					for($n=0; $n<=7; $n++)
					{
						for($o=0; $o<=7; $o++)
						{
							for($p=0; $p<=7; $p++)
							{
								$type_feng_arr = null;
								$item = null;
								$type_feng_arr = [0,0,0,0,0,0,0,0,0];

								if($i)
								$type_feng_arr[$i-1] += 2;
								if($j)
								$type_feng_arr[$j-1] += 2;
								if($k)
								$type_feng_arr[$k-1] += 2;
								if($m)
								$type_feng_arr[$m-1] += 2;
								if($n)
								$type_feng_arr[$n-1] += 2;
								if($o)
								$type_feng_arr[$o-1] += 2;
								if($p)
								$type_feng_arr[$p-1] += 2;

								//去除非法的
								if(max($type_feng_arr) > 5)
								{
									continue;
								}

								$key = intval((implode('', $type_feng_arr)));
								$val = 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
								$val = $val | 64;

								if(max($type_feng_arr) >= 4)
								{
									$gen = count(array_keys( $type_feng_arr , 4 ));
									$val = $val | (4096*$gen);
								}

								//$val = $val | 16;

								//$val = $val | 4;

								$val = $val | 2;

								if(empty($hu_feng_shun_arr[$key]))
								{
									$hu_feng_shun_arr[$key] = $val;
								}
								else
								{
									$hu_feng_shun_arr[$key] = $hu_feng_shun_arr[$key] | $val;
								}
							}
						}
					}
				}
			}
		}
	}

	for($i=0; $i<=7; $i++)
	{
		$type_feng_arr = null;
		$item = null;
		$type_feng_arr = [1,1,1,1,1,1,1,0,0];
		if($i)
		{
			$type_feng_arr[$i - 1] += 1;
		}

		$key = intval((implode('', $type_feng_arr)));
		$val = 0;	//1.牌型32胡 2.幺九 4.是258 8.碰碰胡牌型 16.中张 32.有将牌 64.可做七对 128.十三幺 256.一条龙 512.硬将258 1024.有砍子  2048.有顺子 4096*$gen
		$val = $val | 128;

		if(empty($hu_feng_shun_arr[$key]))
		{
			$hu_feng_shun_arr[$key] = $val;
		}
		else
		{
			$hu_feng_shun_arr[$key] = $hu_feng_shun_arr[$key] | $val;
		}
	}

	$fp = fopen($file,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,var_export(($hu_arr), true));
	flock($fp, LOCK_UN);
	fclose($fp);

	$file_json = $file.'.json';
	$fp = fopen($file_json,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,json_encode($hu_arr));
	flock($fp, LOCK_UN);
	fclose($fp);

	$fp = fopen($file_feng,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,var_export(($hu_feng_arr), true));
	flock($fp, LOCK_UN);
	fclose($fp);

	$file_feng_json = $file_feng.'.json';
	$fp = fopen($file_feng_json,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,json_encode($hu_feng_arr));
	flock($fp, LOCK_UN);
	fclose($fp);

	$fp = fopen($file_feng_shun,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,var_export(($hu_feng_shun_arr), true));
	flock($fp, LOCK_UN);
	fclose($fp);

	$file_feng_shun_json = $file_feng_shun.'.json';
	$fp = fopen($file_feng_shun_json,"w");
	flock($fp, LOCK_EX) ;
	fwrite($fp,json_encode($hu_feng_shun_arr));
	flock($fp, LOCK_UN);
	fclose($fp);

}

logger('./mahjong_data.new', './mahjong_data_feng.new', './mahjong_data_feng_shun.new');