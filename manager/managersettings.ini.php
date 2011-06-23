;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

;authdb for manager use
[db]
persistent=true
host="localhost"
user="root"
pass=""
db="authserver"

;diskdb for manager use
[diskdb]
persistent=true
host="localhost"
user="root"
pass=""
db="disk"

;diskserver to query active agent status
[diskserver]
host="localhost:8082"
path="/diskinternalapi"

;override's the information in the shard table in order to read from slaves
;remember on asp shard2 is swapped IDs with shard1 (shard2 is index1, and shard1 is index2)
;readonly[]=localhost
;readonly[]=localhost
;readonly[]=localhost

[feature]
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=false
tmp_dir="/tmp"
has_ssl=true
force_ssl=true
should_grant_local=false
query_trace=true

; server to redirect customer link to
customer_url_prefix=https://localhost

; list of tiny domains to choose from in the manager
tinydomain[]="ALRT4.ME"
tinydomain[]="AM4.ME"
tinydomain[]="2INFOR.ME"
tinydomain[]="2411.ME"
tinydomain[]="ALERTFOR.ME"
tinydomain[]="MSNGR.ME"
tinydomain[]="ALERT-4.ME"
tinydomain[]="SMFOR.ME"
tinydomain[]="2TEL.ME"
tinydomain[]="4MES.GS"
tinydomain[]="4MSG.ES"

[content]
tts="10.25.25.232,8080,/ttsserver/tts"

;*/?>
