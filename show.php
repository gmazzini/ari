<head>
<meta charset="UTF-8">
<title>Soci ARI by IK4LZH</title>
<style>
body {
  font-family: Arial, sans-serif;
  line-height: 1.6;
  margin: 0;
  padding: 0;
}

textarea {
  width: 100%;
  height: 100%;
  resize: none;
  border: none;
  outline: none;
  font-size: 14px;
}

.contenuto {
  padding: 20px;
}
</style>
</head>
<body>

<div class="contenuto">
<?php
include "ari_local.php";
$con=mysqli_connect($dbhost,$dbuser,$dbpassword,$dbname);
mysqli_set_charset($con,'utf8mb4');
$sez=trim($_POST['sez']); $cell=trim($_POST['cell']);
if(!preg_match('/^(?:\d{4}|\*\*\d{2}|\*\*\*\*)$/',$sez))exit(0);
if(!preg_match('/^\d{9,10}$/', $cell))exit(0);
echo "<pre>";
$query=mysqli_query($con,"select sezione from autentica where (sezione='$sez' or sezione='****') and cellulare='$cell' limit 1");
$row=mysqli_fetch_assoc($query);
$backsez=$row["sezione"];
mysqli_free_result($query);
if($row["sezione"]==NULL){echo "Utente non esistente\n"; exit(0);}
$otp=sprintf("%05d",rand(0,99999));
echo "<span id='yyy'>Sezione $sez, Utente $cell<br>Invia via whatapp il codice $otp al numero di autenticazione 3770867586 entro 90 secondi\n</span>";
ob_flush();
flush();
$epoch=time();
$look=0;
for($i=0;$i<90 && $look==0;$i++){
  $fp=fopen("/home/www/data/auth/39".$cell,"r");
  if($fp!=NULL){
    $line=fgets($fp);
    $vv=explode(",",$line);
    if($vv[0]==$otp && $epoch-$vv[1]<90){$look=1; break;}
    fclose($fp);
  }
  sleep(1);
}
if($look==0){echo "OTP scaduto\n"; exit(0);}

echo "<script>document.getElementById('yyy').textContent = '';</script>";
mysqli_query($con,"UPDATE autentica SET a_show=a_show+1,e_show=$epoch WHERE cellulare='$cell' and sezione='$backsez'");

echo "<h1>Rinnovi</h1>";

$query=mysqli_query($con,"SELECT idlista,sezione,epoch FROM rinnovi WHERE attivo=1 AND ('$sez'='****' OR sezione='$sez') ORDER BY idlista");
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  $idlista=$row["idlista"]; $sezione=$row["sezione"]; $data=date("Y-m-d H:i:s",$row["epoch"]);
  echo "<h2><span id='{$idlista}'></span></h2>";
  $tot=0;
  $query1=mysqli_query($con,"SELECT listarinnovi.id, listarinnovi.codice, soci.nome FROM listarinnovi JOIN soci ON listarinnovi.id = soci.id WHERE listarinnovi.idlista = '{$idlista}' ORDER BY listarinnovi.id");
  for(;;){
    $row1=mysqli_fetch_assoc($query1);
    if($row1==null)break;
    $id=$row1["id"]; $codice=$row1["codice"]; $nome=$row1["nome"];
    $v=0; $sv="";
    if($codice&1){$v+=54; $sv.="Socio";}
    if($codice&2){$v+=7.5; $sv.=",RRcartaceq";}
    if(($codice&32)||($codice&64))$v/=2;
    if($codice&32)$sv.=",Familiare";
    if($codice&64)$sv.=",Junion";
    if($codice&16){$v+=27.75; $sv="Radioclub";}
    if($codice&4){$v+=80; $sv.=",Bcasa";}
    if($codice&8){$v+=25; $sv.=",Baltro";}
    if($codice&128){$v+=10; $sv.=",Bsede";}
    $ss=($codice>>17)/100; if($ss>0){$v+=$ss; $sv.=",Altro[".sprintf("%.2f",$ss)."]";}
    $tot+=$v;
    echo "$id ".sprintf("%6.2f",$v)." $codice $nome $sv\n";
  }
  echo "<script>document.getElementById('{$idlista}').textContent='Idlista:{$idlista} Sezione:{$sezione} Data:{$data} Totale:".sprintf("%.2f",$tot)."';</script>";
  mysqli_free_result($query1);
}
mysqli_free_result($query);
mysqli_close($con);

?>

</div>
</body>
