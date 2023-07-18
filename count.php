<?php
$n=$_GET['num'];
$m=$_GET['num1'];
$dd=$_GET['num2'];
$sit=$_GET['num3'];
$bh=$_GET['num4'];
$sum=$n+$m+$dd+$sit+$bh;
if($sum==0)
{
	echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Sorry! You didn't play.</p>";
	echo "<a href='index.html'><button type='button' class='button'>Restart</button></a>";
}
else if($sum<3)
{
	if($n==1&&$m==1)
	{
echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Nice! $n truth spoken , $m dare completed , $dd double dares done , $sit situations imagined and $bh burning house completed</p>";
echo "<a href='index.html'><button type='button' class='button'>Restart</button></a>";
}
else{
	echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Nice! $n truths spoken , $m dare completed , $dd double dares done , $sit situations imagined and $bh burning house completed</p>";
    echo "<a href='index.html'><button type='button' class='button'>Restart</button></a>";
}
}
else if($sum<5)
{
echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Nice! $n truths spoken , $m dares completed , $dd double dares done , $sit situations imagined and $bh burning house completed</h1>";
echo "<a href='index.html'><button type='button' class='button button3'>Restart</button></a>";
}

else if($sum<10)
{
echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Great! $n truths spoken , $m dares completed , $dd double dares done , $sit situations imagined and $bh burning house completed</p>";
echo "<a href='index.html'><button type='button' class='button button3'>Restart</button></a>";
}

else if($sum<15)
{
echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Awesome! $n truths spoken , $m dares completed , $dd double dares done , $sit situations imagined and $bh burning house completed</p>";
echo "<a href='index.html'><button type='button' class='button button3'>Restart</button></a>";
}

else
{
echo "<p style='color: #fff; font-weight:bold; font-family:Montserrat;'>Wow! $n truths spoken , $m dares completed , $dd double dares done , $sit situations imagined and $bh burning house completed</p>";
echo "<a href='index.html'><button type='button' class='button button3'>Restart</button></a>";
}
?>