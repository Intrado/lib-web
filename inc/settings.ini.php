;<?/* this prevents browsers from seeing anything

[db]
persistent=true
host="localhost"
user="sharpteeth"
pass="sharpteeth202"
db="dialerasp"


;optional backup server
[db2]
persistent=false
host="localhost:3307"
user="sharpteeth"
pass="sharpteeth202"
db="dialerasp"


;optional seperate server for outbound notifications
[dmapidb]
persistent=true
host="localhost"
user="sharpteeth"
pass="sharpteeth202"
db="dialerasp"


[feature]
is_commsuite=false
has_ssl=true
force_ssl=false
log_dir="/commsuite/logs/"
log_db_errors=true
log_db_queries=true

warn_earliest=
warn_latest=

[content]
; format is "host,port,path;host2,port2,path2;..." reserved chars= ';' and ','
get="localhost,80,/foobar/xxx-get.php"
put="localhost,80,/foobar/xxx-put.php"
tts="devbox2,9000,/tts.yaws"


[import]
;type=file|ftp
type=ftp
rootdir=/usr/commsuite/imports/

;below only used for ftp
ftphost=127.0.0.1
ftpport=21
ftpuser=anonymous
ftppass=anonymous

[ldap]
is_ldap=false
ldapconnect="192.168.174.2"
ldapextension="@adtest.net"




;*/?>
