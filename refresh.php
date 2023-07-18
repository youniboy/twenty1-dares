<?php
$choice=$_GET['choice'];
if($choice=='truth')
{
	$arr=@file("questions/adultruth.txt");
	$i=@array_rand($arr);
	$dare = $arr[$i];
	echo "<p style='color: #fff; font-weight:bold'>$dare</p>";
}
elseif($choice=='dare')
{
	$arr=@file("questions/adultdare.txt");
	$i=@array_rand($arr);
	$dare = $arr[$i];
	echo "<p style='color: #fff; font-weight:bold'>$dare</p>";
}
elseif($choice=='doubledare')
{
	$arr=@file("questions/doubledare.txt");
	$i=@array_rand($arr);
	$dare = $arr[$i];
	echo "<p style='color: #fff; font-weight:bold'>$dare</p>";
}
elseif($choice=='situation')
{
	$arr=@file("questions/situation.txt");
	$i=@array_rand($arr);
	$dare = $arr[$i];
	echo "<p style='color: #fff; font-weight:bold'>$dare</p>";
}
elseif($choice=='burninghouse')
{
	$arr=@file("questions/burninghouse.txt");
	$i=@array_rand($arr);
	$dare = $arr[$i];
	echo "<p style='color: #fff; font-weight:bold'>$dare</p>";
}

?>