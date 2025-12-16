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
  <button id="sendQuery">Invia Query</button>
</div>

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
mysqli_query($con,"UPDATE autentica SET a_renew=a_renew+1,e_renew=$epoch WHERE cellulare='$cell' and sezione='$backsez'");

echo "<h1>Soci rinnovabili</h1>";
$query=mysqli_query($con,"select id,family from soci where sezione='$sez' and flag='P' and family<>''");
$dfam=[];
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  $xx=$row["id"]."+".$row["family"];
  foreach(explode("+",$xx) as $id)$dfam[$id]=$xx;
}
mysqli_free_result($query);

$yy=(int)date("Y");
$query=mysqli_query($con,"select id,callsign,nome,thr,family,nascita from soci where sezione='$sez' and flag<>'M' and (q0<>'' or q1<>'' or thr=1) order by nome");
for(;;){
  $row=mysqli_fetch_assoc($query);
  if($row==null)break;
  $iq=(substr($row["callsign"],0,2)=="IQ")?1:0;
  $fam=($row["family"]!=NULL)?1:0;
  $junior=($yy-(int)$row["nascita"]<=25)?1:0;
  $out=$id=$row["id"];
  preg_match_all('/[0-9]/',$row["callsign"],$mm);
  $zz=count($mm[0]);
  if($zz==1){
    if($iq)$hh="SEZ";
    else if($fam && !$junior)$hh="FAM";
    else if(!$fam && $junior)$hh="JUN";
    else if($fam && $junior)$hh="FJU";
    else $hh="ORD";
  }
  else $hh="RCL";
  if($row["thr"]==1)$hh="THR";
  $out.=" ".$hh." ".$row["callsign"];
  $out=substr($out,0,19); $out.=str_repeat(" ",20-strlen($out));
  if($fam)$out.="[".$row["family"]."]";
  $out.=$row["nome"];
  $out=substr($out,0,49); $out.=str_repeat(" ",50-strlen($out));

  if(isset($dfam[$id]))$xx=$dfam[$id]; else $xx=$id;
  $cmd2=$cmd1="";
  $cmd3="if(!document.getElementById('{$id}_digitale').checked && !document.getElementById('{$id}_cartaceo').checked){";
  foreach(explode("+",$xx) as $rr){
    if($rr!=$id){
      $cmd1.="document.getElementById('{$rr}_digitale').checked=true;";
      $cmd2.="document.getElementById('{$rr}_cartaceo').checked=true;";
    }
    $cmd1.="document.getElementById('{$rr}_cartaceo').checked=false;";
    $cmd2.="document.getElementById('{$rr}_digitale').checked=false;";
    $cmd3.="document.getElementById('{$rr}_digitale').checked=true;";
    $cmd3.="document.getElementById('{$rr}_cartaceo').checked=false;";
  }
  $cmd3.="}";

  if($hh=="ORD" || $hh=="FAM" || $hh=="JUN" || $hh=="FJU"){
    $out.="<label><input type='checkbox' name='{$id}' id='{$id}_digitale' value=".sprintf("%d",1+32*$fam+64*$junior)." onchange=\"if(event.isTrusted && this.checked){{$cmd1}}\">Digitale </label>";
    $out.="<label><input type='checkbox' name='{$id}' id='{$id}_cartaceo' value=".sprintf("%d",1+2+32*$fam+64*$junior)." onchange=\"if(this.checked){{$cmd2}}\">Cartaceo </label>";
  }
  else if($hh=="SEZ"){
    $out.="<label><input type='checkbox' name='{$id}' id='{$id}_cartaceo' value=".sprintf("%d",1+256)." onchange=\"if(this.checked){{$cmd2}}\">Cartaceo </label>";
  }
  if($hh=="RCL"){
    $out.="<label><input type='checkbox' name='{$id}' value=16>RadioClub </label>";
  }
  else {
    $out.="<label><input type='checkbox' id='{$id}_altro' name='{$id}' value=8 onchange=\"if(event.isTrusted && this.checked){{$cmd3}}\">Baltro </label>";
    $out.="<label><input type='checkbox' id='{$id}_casa' name='{$id}' value=4 onchange=\"if(event.isTrusted && this.checked){{$cmd3}}\">Bcasa </label>";
    $out.="<label><input type='checkbox' id='{$id}_sede' name='{$id}' value=128 onchange=\"if(event.isTrusted && this.checked){{$cmd3}}\">Bsede </label>";
  }

  $out.="<label><input name='{$id}_num' size='6' required oninput=\"let v=this.value; v=v.replace(/[^0-9.]/g,''); let parts=v.split('.'); if(parts[0].length>3){parts[0]=parts[0].slice(0,3);}if(parts[1]&&parts[1].length>2){parts[1] = parts[1].slice(0,2);} this.value=parts.length>1?parts[0]+'.'+(parts[1]||''):parts[0];\">Altro</label>";

  echo $out."\n";
}
mysqli_free_result($query);
mysqli_close($con);

?>

</div>

<script>
let randomString='';
for(let i=0;i<8;i++){
  randomString+=Math.floor(Math.random()*10);
}

function aggiornaRiquadro(){
  const valori={0:0,1:72,3:82,4:80,8:25,16:37,33:36,35:41,65:36,67:41,97:36,99:41,128:10,256:41};
  const sede={0:0,1:54,3:61.5,4:80,8:25,16:27.75,33:27,35:30.75,65:27,67:30.75,97:27,99:30.75,128:10,256:41};
  const checked=document.querySelectorAll("input[type=checkbox]:checked");
  const altro=document.querySelectorAll("input[name$='_num']");
  let totale=0;
  let alnazionale=0;
  const soci=new Set();
  checked.forEach(cb => {
    soci.add(cb.name);
    const idx=Number(cb.value) || 0;
    totale+=(valori[idx]??0);
    alnazionale+=(sede[idx]??0);
  });

  altro.forEach(inp => {
    const val=Number(inp.value);
    if(!isNaN(val)&&val!==0){
      totale+=val;
      const baseName=inp.name.replace(/_num$/,"");
      soci.add(baseName);
    }
  });

  const sociSelezionati=soci.size;
  document.getElementById("riepilogo").value =
    "Sezione <?php echo $sez; ?>, Utente <?php echo $cell; ?>\n" +
    "Somma totale Euro: " + totale + "\n" +
    "Somma al Nazionale Euro: " + alnazionale + "\n" +
    "Numero soci selezionati: " + sociSelezionati + "\n" +
    "Codice da riportare: " + randomString;
}

document.querySelectorAll("input[type=checkbox]").forEach(radio => {
  radio.addEventListener("change", aggiornaRiquadro);
});
document.querySelectorAll("input[name$='_num']").forEach(inp => {
  inp.addEventListener("input", aggiornaRiquadro);
});

aggiornaRiquadro();
document.getElementById("sendQuery").addEventListener("click", () => {
  const checked=document.querySelectorAll("input[type=checkbox]:checked");
  const altro=document.querySelectorAll("input[name$='_num']");
  let dati={};
  dati[randomString]="<?php echo $sez; ?>";
  checked.forEach(box => {
    if(box.name && parseInt(box.value,10)>0){
      const val=parseInt(box.value,10);
      dati[box.name]=(dati[box.name] || 0) + val;
    }
  });
  altro.forEach(inp => {
    const val=Number(inp.value);
    if(!isNaN(val)&&val!==0){
      const baseName=inp.name.replace(/_num$/,"");
      dati[baseName]=(dati[baseName] || 0) + 131072*Math.round(val*100);
    }
  });

  fetch("processa.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(dati)
  });
});

</script>
</body>
