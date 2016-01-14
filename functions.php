<?php
require_once dirname( __FILE__ ) .'/config.php';
/**
* generates a random string
* @param int length
* @return string
*/
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
   shell_exec("service nginx reload");
}
/**
* reads apache-ssl file and prints content to page
* seeds DomainFile for easier Cronjob creation
*/
function readConfigFile($boolPost)
{
   $configContent = file_get_contents(apacheSSL);
   preg_match_all("/server_name (.*?);/", $configContent, $serverNames);
   preg_match_all("/\bproxy_pass http:\/\/127.0.0.1:\b(.*?);/", $configContent, $port);

  # first entry is default and not needed any more
  $trash = array_shift($serverNames[1]);
  $trash = array_shift($port[1]);
  #file with domains for cronjob
  if($boolPost) {
    file_put_contents(domainFile, "", LOCK_EX);

  for($i=0; $i<sizeof($serverNames[1]); $i++)
  {
    #write domains to DomainFile for creating cronjob later
    if($boolPost)
      file_put_contents(domainFile, $serverNames[1][$i] . "\n", FILE_APPEND | LOCK_EX);
    $results = "<div class=\"alterDomainElem\" id=\"alterDomainElem\">
          <a href=\"javascript: expand('#expandDomainElem".$i."')\" class=\"fa fa-chevron-down\"> Details zu: ".$serverNames[1][$i]."</a>
          <div id=\"expandDomainElem".$i."\" class=\"expandDomainElem\">
            <div><label for=\"alterDomain\">Domain:</label><input type=\"text\" name=\"alterDomain[]\", id=\"alterDomain\" value=\"".$serverNames[1][$i]."\" readonly/></div>
            <div><label for=\"alterPort\">Port:</label><input type=\"text\" name=\"alterPort[]\", id=\"alterPort\" value=\"".$port[1][$i]."\"/></div>
            <button class=\"removeBtn\" type=\"button\" onClick=\"removeParent(this)\" >Löschen</button>
          </div>
        </div>";
    echo $results;
   }
}
/**
* creates new Certificate, if successful apache file and apache-ssl file
* will be seeded with new entry
* @param $domain
* @param $port
*/
function writeConfigFile($domain, $port)
{
  #start letsencrypt Docker
  $output = shell_exec(preg_replace('/\[DOMAIN\]/', $domain, dockerCmd));
  #check letsencrypt output and write apacheconf and apache-ssl
  if(preg_match('/Congratulations/', $output)) {

    $certPath = certPath . $domain . "/fullchain.pem";
    $keyPath = certPath . $domain . "/privkey.pem";

      #write apache onf
      writeRedirect($domain);

      #write apache-ssl
      writeApacheSSL($domain, $port, $certPath, $keyPath);

      return TRUE;
    } else {
      return FALSE;
  }
}
/**
* appends new entry into apache file
* @param $domain
*/
function writeRedirect($domain)
{
  $content = "server {
    listen 80;
    server_name ".$domain .";
    return 301 https://\$server_name\$request_uri;
}\n";

  file_put_contents(apacheConf, $content, FILE_APPEND | LOCK_EX);
}
/**
* appends new entry into apache-ssl file
* @param $domain
* @param $port
* @param $certPath
* @param $keyPath
*/
function writeApacheSSL($domain, $port, $certPath, $keyPath)
{
   $content ="server {
    listen 443 ssl;
    server_name ". $domain .";
    ssl_certificate ". $certPath .";
    ssl_certificate_key ". $keyPath .";
    location / {
        proxy_pass http://127.0.0.1:". $port .";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}\n";

   file_put_contents(apacheSSL, $content, FILE_APPEND | LOCK_EX);
}

function alterInput($altertmp, $i)
{
  switch($i) {
    case 0:
       rewriteApacheSSL();
       rewriteApacheConf();
       #TODO check inhalt von altertemp wegen config file und so !writeConfigFile($altertmp[0], $altertmp[1], $altertmp[3], $altertmp[5])
       if(!writeConfigFile($altertmp[0], $altertmp[1])) {
          echo "<div class='error'>Fehler beim schreiben der Datei</div>";
       }
       break;
    default:
       if(!writeConfigFile($altertmp[0], $altertmp[1])) {
          echo "<div class='success'>Fehler beim schreiben der Datei</div>";
       }
       break;
   }
}
/**
* rewrites apache-ssl file if there are deleted any entries
*/
function rewriteApacheSSL()
{
  $content = "#Disables all weak ciphers
ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
ssl_ciphers \"ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA256:ECDHE-RSA-AES256-SHA:ECDHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES128-SHA256:DHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES256-GCM-SHA384:AES128-GCM-SHA256:AES256-SHA256:AES128-SHA256:AES256-SHA:AES128-SHA:DES-CBC3-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!MD5:!PSK:!RC4\";
server {
    listen 443 ssl;
    server_name ~^(?<domain>.*)\$;
    ssl_certificate /export/prod/www/ssl/~^(?<domain>.*)\$/nginx-cert.pem;
    ssl_certificate_key /export/prod/www/ssl/~^(?<domain>.*)\$/key.pem;
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}\n";
   file_put_contents(apacheSSL, $content, LOCK_EX);
}
/**
* rewrites apache conf file if there are deleted any entries
*/
function rewriteApacheConf()
{
  $content ="server {
    listen 80;
    server_name ~^(?<domain>.+)\$;
    location /
    {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }
    location /.well-known/acme-challenge {
        root /export;
    }
}\n";
  file_put_contents(apacheConf, $content, LOCK_EX);
}
/**
* removes folder and its content from given directory
* @param $dir
*/
function rmFolder($dir)
{
  $files = array_diff(scandir($dir), array('.','..'));
  foreach($files as $file){
    (is_dir("$dir$file")) ? rmFolder("$dir$file") : unlink("$dir$file");
  }
  rmdir($dir);
}
?>
