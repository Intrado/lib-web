;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

[feature]
is_commsuite=false
has_ssl=true
force_ssl=true
log_dir="/commsuite/logs/"
log_db_errors=true
log_db_queries=true
tmp_dir="/tmp"


[content]
;tts="10.25.25.17,8080,/tts/ttsloquendo"
tts="localhost,8080,/phone/Tts"
;*/?>