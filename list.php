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

.riquadro {
  position: fixed;
  top: 10px;
  right: 10px;
  width: 300px;
  height: 120px;
  padding: 10px;
  background: #f9f9f9;
  border: 2px solid #333;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  border-radius: 8px;
  z-index: 1000;
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

<div class="riquadro">
  <textarea id="riepilogo" readonly></textarea>
</div>

<div class="contenuto">
<?php
include "ari_local.php";
include "auth.php";
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
if(myauth($sez,$cell)==0){echo "OTP scaduto\n"; exit(0);}
$epoch=time();
$cr=0; if(substr($sez,0,2)=="**")$cr=1;
echo "<script>document.getElementById('yyy').textContent = '';</script>";
mysqli_query($con,"UPDATE autentica SET a_list=a_list+1,e_list=$epoch WHERE cellulare='$cell' and sezione='$backsez'");

$name=rand().rand().rand().rand().".csv";
if($cr)$query=mysqli_query($con,"select sezione,id,nome,cf,nascita,callsign,flag,thr,q0,q1,q2,voto,family from soci where sezione in (select id from cr where cr='$sez') order by sezione,id");
else $query=mysqli_query($con,"select sezione,id,nome,cf,nascita,callsign,flag,thr,q0,q1,q2,voto,family,email,numeri from soci where sezione='$sez' order by id");
$fp=fopen("/home/www/info/files/$name","w");
fprintf($fp,"sezione,matricola,nome,cf,nascita,callsign,flag,thr,q2,q1,q0,voto,family");
if($cr==0)fprintf($fp,",email,numeri");
fprintf($fp,"\n");
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  fprintf($fp,"%s,%s,%s,%s,%d,%s,%s,%d,%s,%s,%s,%d,%s",$row["sezione"],$row["id"],$row["nome"],$row["cf"],$row["nascita"],$row["callsign"],$row["flag"],$row["thr"],$row["q2"],$row["q1"],$row["q0"],$row["voto"],$row["family"]);
  if($cr==0)fprintf($fp,",%s,%s",$row["email"],$row["numeri"]);
  fprintf($fp,"\n");
}
mysqli_free_result($query);
fclose($fp);
echo "<a href='https://info.ari.it/files/$name'>Download CSV</a><br>";

if($cr){
  echo "<h1>Riassunto</h1>";
  echo "Sezione Soci Votanti\n";
  $query=mysqli_query($con,"select sezione,count(*) as tot_soci,sum(case when voto=1 then 1 else 0 end) as tot_con_voto from soci where sezione in (select id from cr where cr='$sez') and flag<>'M' and (q0<>'' or thr=1) group by sezione order by sezione;");
  for(;;){
    $row=mysqli_fetch_assoc($query);
    if($row==null)break;
    echo $row["sezione"]." ".$row["tot_soci"]." ".$row["tot_con_voto"]."\n";
  }
  mysqli_free_result($query);
}

echo "<h1>Soci attivi</h1>";
$sociattivi=0;
$thr=0;
$voti=0;
if($cr)$query=mysqli_query($con,"select sezione,id,callsign,nome,thr,family,email,numeri,voto from soci where sezione in (select id from cr where cr='$sez') and flag<>'M' and (q0<>'' or thr=1) order by sezione,nome");
else $query=mysqli_query($con,"select id,callsign,nome,thr,family,email,numeri,voto from soci where sezione='$sez' and flag<>'M' and (q0<>'' or thr=1) order by nome");
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  $sociattivi++;
  $callsign=$row["callsign"];
  if($cr)echo $row["sezione"]." ";
  echo $row["id"]." ";
  preg_match_all('/[0-9]/',$callsign,$mm); $zz=count($mm[0]); if($zz==1)$hh="HAM"; else $hh="RCL";
  if($row["thr"]==1){$hh="<b>THR</b>"; $thr++; }
  echo "$hh ";
  echo " $callsign ".$row["nome"];
  if($row["family"]!=NULL)echo " [".$row["family"]."]";
  if($cr==0)echo " ".$row["email"]." ".$row["numeri"];
  if($row["voto"]==1)$voti++;
  echo "\n";
}
mysqli_free_result($query);

echo "<h1>Ex Soci</h1>";
$exsoci=0;
if($cr)$query=mysqli_query($con,"select sezione,id,callsign,nome from soci where sezione in (select id from cr where cr='$sez') and thr=0 and q0='' order by sezione,nome");
else $query=mysqli_query($con,"select id,callsign,nome from soci where sezione='$sez' and thr=0 and q0='' order by nome");
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  $exsoci++;
  if($cr)echo $row["sezione"]." ";
  echo $row["id"]." ";
  echo $row["callsign"]." ".$row["nome"];
  echo "\n";
}
mysqli_free_result($query);
mysqli_close($con);

?>

</div>

<script>
let randomString = '';
for (let i = 0; i < 8; i++) {
  randomString += Math.floor(Math.random() * 10);
}

document.getElementById("riepilogo").value =
  "Sezione <?php echo $sez; ?>, Utente <?php echo $cell; ?> \n" +
  "Soci attivi : <?php echo $sociattivi; ?> \n" +
  "Soci votanti : <?php echo $voti; ?> \n" +
  "Top Honour Roll : <?php echo $thr; ?> \n" +
  "Ex Soci : <?php echo $exsoci; ?>";

</script>
</body>
