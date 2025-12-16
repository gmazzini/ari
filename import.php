<?php
include "/home/www/info/ari_local.php";

$con=mysqli_connect($dbhost,$dbuser,$dbpassword,$dbname);

function norm($input) {
    // Dividi in parti usando separatori comuni tra numeri
    $parts = preg_split('/\s*(?:\/+|\|+|\bor\b|\boppure\b)\s*/i', $input);

    $nums = [];
    foreach ($parts as $p) {
        // Tieni solo le cifre dentro la parte (conserva eventuali zeri iniziali)
        $n = preg_replace('/\D+/', '', $p);
        // Considera numeri “veri” (>=6 cifre; regola adattabile)
        if ($n !== '' && strlen($n) >= 6) {
            $nums[] = $n;
        }
    }

    // Rendi nel formato richiesto
    if (count($nums) >= 2) return $nums[0] . '-' . $nums[1];
    if (count($nums) === 1) return $nums[0];
    return null;
}
// goto pippo;
mysqli_query($con,"delete from soci");

echo "--- Tutti\n";
$fp=fopen("q5.csv","r");
$line=fgets($fp);
for(;;){
  $line=fgets($fp);
  if(feof($fp))break;
  $oo=str_getcsv($line,",");
  $zz=explode("-",$oo[16]);
  $id=sprintf("%02d%04d",$zz[0],$zz[1]);
  $hh=norm($oo[7]);
  $q0=$oo[19]; $q1=$oo[18]; $q2=$oo[17]; $email=mysqli_real_escape_string($con,$oo[6]); $numeri=mysqli_real_escape_string($con,$hh); $nascita=(int)$oo[8];
  $flag=$oo[12]; $callsign=$oo[10]; $nome=mysqli_real_escape_string($con,$oo[1]);
  $indirizzo=mysqli_real_escape_string($con,$oo[2]."-".$oo[3]."-".$oo[4]."-".$oo[5]);
  $vv=str_getcsv($oo[9],","); $cf=str_replace(" ","",$vv[0]);
  $voto=0; if($oo[15]=="Si")$voto=1;
  if($numeri==" /")$numeri="";
  $zz=explode("-",$oo[14]);
  $sezione=sprintf("%02d%02d",$zz[0],$zz[1]);
  mysqli_query($con,"insert into soci (id,nome,cf,nascita,callsign,flag,sezione,q0,q1,q2,voto,email,numeri,indirizzo) values ('$id','$nome','$cf',$nascita,'$callsign','$flag','$sezione','$q0','$q1','$q2',$voto,'$email','$numeri','$indirizzo')");
}
fclose($fp);

// Elenco,Nominativo,Indirizzo,CAP,Citta,Provincia,Email,Telefono,Nato,Codice Fiscale,Sigla1,Sigla2,Varie,QSL,
// Sezione,Voto_,Matricola,Q2,Q1,Q0

echo "--- THR\n";
$fp=fopen("q3.csv","r");
$line=fgets($fp);
for(;;){
  $line=fgets($fp);
  if(feof($fp))break;
  $oo=str_getcsv($line,",");
  $zz=explode("-",$oo[0]);
  $id=sprintf("%02d%04d",$zz[0],$zz[1]);
  mysqli_query($con,"update soci set thr=1 where id='$id'");
}
fclose($fp);

echo "--- FAMILY\n";
$fp=fopen("q4.txt","r");
$line=fgets($fp);
for(;;){
  $line=fgets($fp);
  if(feof($fp))break;
  $vv=substr($line,0,20);
  preg_match_all("/\d/",$vv,$m1);
  preg_match_all("/\s/",$vv,$m2);
  if(count($m1[0])==12 && count($m2[0])==8){
    $p1=substr($vv,0,6);
    preg_match('/\s+(\d)/',$vv,$matches,PREG_OFFSET_CAPTURE);
    $pos=$matches[1][1];
    $p2=substr($vv,$pos,6);
    mysqli_query($con,"update soci set family=CONCAT_WS('+',family,'$p1') where id='$p2'");
    mysqli_query($con,"update soci set family=CONCAT_WS('+',family,'$p2') where id='$p1'");
  }
}
fclose($fp);

mysqli_close($con);

?>

