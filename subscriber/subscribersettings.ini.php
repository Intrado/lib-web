;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

[feature]
is_commsuite=false
has_ssl=true
force_ssl=false
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=true
tmp_dir="/usr/commsuite/tmp"

[content]
tts="jammer,8080,/tts/ttsserver"

externalcontent=false

;*/?>