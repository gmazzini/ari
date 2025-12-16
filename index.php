<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accesso</title>
</head>
<body>
<style>
  button {
    display: block;
    width: 200px;
    margin: 10px 0;
    padding: 8px 16px;
    font-size: 16px;
    background-color: #ddd;
    border: 1px solid #999;
    cursor: pointer;
  }
  button:hover {
    background-color: #999;
  }
</style>

<?php
$dir="files";
$scadenza=time()-86400;
foreach(scandir($dir) as $f){
  $p="$dir/$f";
  if(is_file($p) && filemtime($p)<$scadenza) unlink($p);
}
?>

<h1>Funzioni Soci v1.6</h1>
<form method="post">
<input name="sez"
  placeholder="Sezione (4 cifre oppure *XXX)"
  maxlength="4"
  minlength="4"
  pattern="(\d{4}|\*\*\d{2}|\*\*\*\*)"
  required
  oninput="this.value=this.value.replace(/[^0-9*]/g,'').slice(0,4)"
>
<input name="cell"
  placeholder="Cellulare (9 o 10 cifre)"
  inputmode="numeric"
  maxlength="10"
  minlength="9"
  pattern="\d{9,10}"
  required oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
>
<br>
<button type="submit" formaction="https://info.ari.it/list.php">LISTA</button>
<button type="submit" formaction="https://info.ari.it/renew.php">RINNOVI</button>
<button type="submit" formaction="https://info.ari.it/show.php">SHOW</button>

</form>
</body>
