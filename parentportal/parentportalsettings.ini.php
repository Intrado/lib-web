;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

; commsuite appserver service
[appserver_commsuite]
host="localhost:7912"
timeout=5500

[feature]
has_ssl=true
force_ssl=false
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=true
tmp_dir="/tmp"
query_trace=true

[content]
tts="10.25.25.66,8080,/tts/ttsserver"


externalcontent=false

;*/?>