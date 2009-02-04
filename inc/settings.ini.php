;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

[feature]
is_commsuite=true
is_ldap=false
has_ssl=true
force_ssl=false
log_dir="/usr/commsuite/logs/"
log_db_errors=false
log_db_queries=false
log_dmapi=false
has_print=false
tmp_dir="/tmp"

;if not set, warn_earliest is set to "7:00 am"
;and warn_latest is set to "9:00 pm"
warn_earliest=
warn_latest=

[content]
tts="localhost,8080,/phone/Tts"
;tts="10.25.25.16,8080,/tts/ttsloquendo"

externalcontent=false
; format is "host,port,path;host2,port2,path2;..." reserved chars= ';' and ','
get="localhost,80,/foobar/xxx-get.php"
put="localhost,80,/foobar/xxx-put.php"

[translation]
apikey="ABQIAAAAsHD6V_IqbLuYzx5sdmQ-TxQg47NEwCDk0BAJwz_RiAOR27B3BhRP2lCFovHb2pUntvaPoLjZtfK4gQ"
referer="http://asp.schoolmessenger.com"
disableAutoTranslate=false

;*/?>
