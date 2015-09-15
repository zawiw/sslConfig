<?php
define("mysqlDB", "userDB");
define("mysqlTable", "users");
define("root", "root");
define("rootpw", hash('sha256', 'root'));

define("configPath", dirname( __FILE__ ) ."/apache-ssl");
define("configPathEnabled", "/etc/nginx/sites-available");
define("certDest", "/home/georg/zawiw/sslConfig/ssl/");
define("backupDest", "/home/georg/zawiw/sslConfig/ssl.backup/");
define("nginxCert", "nginx-cert.pem");
define("nginxKey", "nginx-key.pem");

#Edit restartNginx() in functions.php
# run init.php for database
?>
