<?php
// Numbers Roman example

require_once("Numbers/Roman.php");
$Num = rand(1,3999); 
$NumHtml = rand(500,5999999);

//converting a random number between 1 and 3999 from Arabic to the Roman Numeral
//uppercase true, html false, then back to Arabic
$key =  Numbers_Roman::toNumeral($Num,true,false);
$NumKey = Numbers_Roman::toNumber($key);


// converting a random number between 500 and 5 999 999 to the Roman Numeral
$RomeHtml     =  Numbers_Roman::toNumeral($NumHtml,true,true);       //uppercase true html true
$RomenoHtml   =  Numbers_Roman::toNumeral($NumHtml,true,false);    //uppercase true html false
$RomeHtmlnoLC =  Numbers_Roman::toNumeral($NumHtml,false,true);  //uppercase false html true
$RomenoHtmlLC =  Numbers_Roman::toNumeral($NumHtml,false,false);  //uppercase false html false

$toArabic = Numbers_Roman::toNumber($RomenoHtml); //back to the Arabic number 

echo "Random: ".$Num." (To roman) -> ".$key." (Back to arabic) -> ".$NumKey." <BR> <BR>";

echo "Random with HTML enabled converting ".$NumHtml." into a Roman Numeral ".$RomeHtml."<BR>";
echo "Lowercase html $RomeHtmlnoLC.. <BR> ".$RomenoHtml." uppercase no html <BR> 
$RomenoHtmlLC lowercase no html <BR>and back to Arabic ".$toArabic."";
?>
