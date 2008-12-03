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
tmp_dir="/tmp"

[content]
;tts="localhost,8080,/phone/Tts"
tts="localhost,8080,/tts/ttsloquendo"

externalcontent=false

;*/?>