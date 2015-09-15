<?php
require_once dirname( __FILE__ ) .'/config.php';
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function userLogin()
{
   $db = new SQLite3(mysqlDB.".db");
   $stmt = "SELECT * FROM " . mysqlTable ." WHERE loginname='" . (get_magic_quotes_gpc() ? $_POST['user'] : addslashes($_POST['user'])) . "' LIMIT 1";
   $result = $db->query($stmt);
   $row = $result->fetchArray();
   if($row['pwhash'] == $_POST['pwhash'])
   {
      $ts = time();
      $_SESSION['user'] = $_POST['user'];
      $_SESSION['sessionhash'] = generateSessionHash($_POST['user'], $_POST['pwhash'], $_POST['rand']);
      $stmt2 = "UPDATE " . mysqlTable . " SET session='" . $_SESSION['sessionhash'] . "',lastlogin='" . $ts . "' WHERE loginname='" . $_POST['user'] . "'";
      $result = $db->exec($stmt2);
      if($result)
         return TRUE;
      return FALSE;
   }
   else
      return FALSE;
}
function generateSessionHash($user, $pwhash, $rand)
{
   return $rand . hash('sha256', $user . $pwhash  . $rand);
}
function userLoggedIn($user)
{
   $db = new SQLite3(mysqlDB.".db");
   $stmt = "SELECT * FROM " . mysqlTable . " WHERE loginname='" . (get_magic_quotes_gpc() ? $_SESSION['user'] : addslashes($_SESSION['user'])) . "' LIMIT 1";
   $result = $db->query($stmt);
   $row = $result->fetchArray();
   $db = NULL;
   $session = substr($_SESSION['sessionhash'],0,16);
   $session = $session . hash('sha256', $user . $row['pwhash'] . $session);
   if($session == $_SESSION['sessionhash'] &&  $row['lastlogin'] > (time() - 60*30))
      return TRUE;
   else if($session == $_SESSION['sessionhash'] &&  $row['lastlogin'] <= (time() - 60*30))
      return -1;
   else
      return FALSE;
}
function restartNginx(){
   #@symlink(configPath, configPathEnabled);
   #shell_exec("service nginx restart");
}
function readConfigFile()
{
   $configContent = file_get_contents(configPath);
   preg_match_all("/server_name (.*?);/", $configContent, $serverNames);
   preg_match_all("/\bssl_certificate\b (.*?);/", $configContent, $sslCertPath);
   preg_match_all("/\bssl_certificate_key\b (.*?);/", $configContent, $sslKeyPath);
   preg_match_all("/\bproxy_pass https:\/\/127.0.0.1:\b(.*?);/", $configContent, $port);

   $trash = array_shift($serverNames[1]);
   for($i=0; $i<sizeof($serverNames[1]); $i++)
   {
      $sslCert = "";
      $sslKey = "";
      if(file_exists($sslCertPath[1][$i]))
         $sslCert = file_get_contents($sslCertPath[1][$i]);
      if(file_exists($sslKeyPath[1][$i]))
         $sslKey = file_get_contents($sslKeyPath[1][$i]);
      $results = "<div class=\"alterDomainElem\">
            <div><label for=\"alterDomain\">Domain:</label><input type=\"text\" name=\"alterDomain[]\", id=\"aalterDomain\" value=\"".$serverNames[1][$i]."\"/></div>
            <div><label for=\"alterPort\">Port:</label><input type=\"text\" name=\"alterPort[]\", id=\"alterPort\" value=\"".$port[1][$i]."\"/></div>
            <div><label for=\"alterCertPath\">SSL-Zertifikat:</label><input type=\"text\" name=\"alterCertPath[]\", id=\"alterCertPath\" value=\"".$sslCertPath[1][$i]."\"/>
            <div><textarea rows=\"10\" cols=\"50\" name=\"altersslCert[]\", id=\"altersslCert\" >".$sslCert."</textarea></div>
            </div>
            <div><label for=\"alterKeyPath\">SSL-Key:</label><input type=\"text\" name=\"alterKeyPath[]\", id=\"alterKeyPath\" value=\"".$sslKeyPath[1][$i]."\"/>
            <div><textarea rows=\"10\" cols=\"50\" name=\"altersslKey[]\", id=\"altersslKey\" >".$sslKey."</textarea></div>
            </div>
            <button class=\"removeBtn\" type=\"button\" onClick=\"removeParent(this)\" >Löschen</button>
            <hr>
      </div>";
      echo $results;
   }
}
function writeConfigFile($domain, $port, $sslCert, $sslCertKey)
{
   #TODO better find solution
   $file = configPath;
   $newFolder = certDest . $domain;
   if(!file_exists($newFolder))
      @mkdir($newFolder);
   else {
      return FALSE;
   }
   $certPath = $newFolder . "/" . nginxCert;
   $keyPath = $newFolder . "/" . nginxKey;
   writeCertFile($certPath, $keyPath, $sslCert, $sslCertKey);
   if(checkCert($certPath) && checkCert($keyPath))
   {
      inputSSLRoute($domain, $file);
      $content = buildEntry($domain, $port, $certPath, $keyPath);
      file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
      return TRUE;
   } else {
      return FALSE;
   }
}
function writeCertFile($certPath, $keyPath, $sslCert, $sslCertKey)
{
   #if file exists it will be overritten!!!
   file_put_contents($certPath, $sslCert, LOCK_EX);
   file_put_contents($keyPath, $sslCertKey, LOCK_EX);
}
function checkCert($filePath)
{
   if(file_exists($filePath)){
      return TRUE;
   } else {
      return FALSE;
   }
}
function inputSSLRoute($domain, $file)
{
   $fileContent = file($file);
   $fp = fopen($file, "w+");
   $newLine = substr_replace($fileContent[5], "", -2);
   $fileContent[5] = "".$newLine . " " . $domain . ";\n";
   fwrite($fp, implode($fileContent, ''));
   fclose($fp);
}
function buildEntry($domain, $port, $certPath, $keyPath)
{
   $content ="server {
    listen 443 ssl;
    server_name ". $domain .";
    ssl_certificate ". $certPath .";
    ssl_certificate_key ". $keyPath .";
    location / {
        proxy_pass https://127.0.0.1:". $port .";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}\n";
   return $content;
}
function alterInput($altertmp, $i)
{
   switch($i) {
      case 0;
         rewriteConfigFile();
         if(!writeConfigFile($altertmp[0], $altertmp[1], $altertmp[3], $altertmp[5])) {
            echo "<div class='error'>Fehler!!!!!!!111111elf</div>";
         }
         break;
      default;
         if(!writeConfigFile($altertmp[0], $altertmp[1], $altertmp[3], $altertmp[5])) {
            echo "<div class='success'>Fehler!!!!!!!111111elf</div>";
         }
         break;
   }
}
function rmFolder($dir)
{
   $files = array_diff(scandir($dir), array('.','..'));
   foreach($files as $file)
      (is_dir("$dir/$file")) ? rmFolder("$dir/$file") : unlink("$dir/$file");
   return rmdir($dir);
}
function rewriteConfigFile()
{
   $content = "#Disables all weak ciphers
ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
ssl_ciphers \"ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA256:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES128-SHA256:DHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA256:AES128-SHA256:AES256-SHA:AES128-SHA:DES-CBC3-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!MD5:!PSK:!RC4\";
server {
    listen 80;
    server_name ;
    return 301 https://\$server_name\$request_uri;
}\n";
   file_put_contents(configPath, $content, LOCK_EX);
}
function createBackup()
{
   if(!copy(configPath, configPath.".bak")) {
      unlink(configPath.".bak");
      return FALSE;
   }
   copyBackup(certDest, backupDest);
   return TRUE;
}
function copyBackup($source, $target)
{
   rmFolder($target);
   if(!file_exists($target))
      @mkdir($target);
   if (!is_dir($source)) {//it is a file, do a normal copy
      copy($source, $target);
      return;
   }
   //it is a folder, copy its files & sub-folders
   @mkdir($target);
   $d = dir($source);
   $navFolders = array('.', '..');
   while (false !== ($fileEntry=$d->read() )) {//copy one by one
      //skip if it is navigation folder . or ..
      if (in_array($fileEntry, $navFolders) )
          continue;
      //do copy
      $s = "$source/$fileEntry";
      $t = "$target/$fileEntry";
      copyBackup($s, $t);
   }
   $d->close();
}
?>
