;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

[appserver]
host="localhost:7911"
timeout=5500

[feature]
is_commsuite=false
has_ssl=true
force_ssl=true
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=false
log_dmapi=false
query_trace=true
has_print=false
tmp_dir="/tmp"

;if not set, warn_earliest is set to "7:00 am"
;and warn_latest is set to "9:00 pm"
warn_earliest=
warn_latest=

[content]
tts="10.25.25.66,8080,/tts/ttsserver"


[translation]
apikey="ABQIAAAAsHD6V_IqbLuYzx5sdmQ-TxQg47NEwCDk0BAJwz_RiAOR27B3BhRP2lCFovHb2pUntvaPoLjZtfK4gQ"
referer="http://asp.schoolmessenger.com"
disableAutoTranslate=false


[txtreply]
txt_datfile="/usr/commsuite/cache/txtreply.dat"

[facebook]
appid="170562026304359"
appsecret="3cf7cbf09b579450f5761e46c14a655c"


[twitter]
consumerkey="TrOii2FpUUlCoyvgnbohnQ"
consumersecret="L4RtNiOENkkbrsLdDOSAkE2O08aChqJ52e8McoFfJ4"

;*/?>
