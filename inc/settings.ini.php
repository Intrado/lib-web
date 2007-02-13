;<?/* this prevents browsers from seeing anything

[db]
persistent=true
host="localhost:3306"
user="root"
pass=""
db="dialerasp"

;optional seperate server for outbound notifications
[dmapidb]
persistent=true
host="localhost:3306"
user="root"
pass=""
db="dialerasp"


[feature]
is_commsuite=false
; commsuite users may need to disable the inbound number verification
; if true, only validates user/pass and not inbound phone number
disable_inbound_number_verification=false
has_ssl=true
force_ssl=false
log_dir="/commsuite/logs/"
log_db_errors=true
log_db_queries=true
log_dmapi=true

;if not set, warn_earliest is set to "7:00 am"
;and warn_latest is set to "9:00 pm"
warn_earliest=
warn_latest=

[content]
tts="devbox4,8080,/phone/Tts"

externalcontent=false
; format is "host,port,path;host2,port2,path2;..." reserved chars= ';' and ','
get="localhost,80,/foobar/xxx-get.php"
put="localhost,80,/foobar/xxx-put.php"


[import]
;type=file|ftp
type=ftp

;used for file type imports
filedir=/usr/commsuite/imports/

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
