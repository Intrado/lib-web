;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

[feature]
is_commsuite=false
has_ssl=true
force_ssl=false
log_dir="/commsuite/logs/"
log_db_errors=true
log_db_queries=true
log_dmapi=true
has_print=true
tmp_dir="/tmp"
has_sms=true

;if not set, warn_earliest is set to "7:00 am"
;and warn_latest is set to "9:00 pm"
warn_earliest=
warn_latest=

[content]
;tts="devbox4,8080,/phone/Tts"
tts="10.25.25.15,8080,/tts/ttsloquendo"

externalcontent=false
; format is "host,port,path;host2,port2,path2;..." reserved chars= ';' and ','
get="localhost,80,/foobar/xxx-get.php"
put="localhost,80,/foobar/xxx-put.php"


;*/?>
