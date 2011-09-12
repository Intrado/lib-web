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
tinydomain[]="alrt4.me"
tinydomain[]="am4.me"
tinydomain[]="2infor.me"
tinydomain[]="2411.me"
tinydomain[]="alertfor.me"
tinydomain[]="msngr.me"
tinydomain[]="alert-4.me"
tinydomain[]="smfor.me"
tinydomain[]="2tel.me"
tinydomain[]="4mes.gs"
tinydomain[]="4msg.es"

[content]
tts="10.25.25.232,8080,/ttsserver/tts"

[servermanagement]
defaultjmxproxy="http://localhost:8085/jolokia"
cvsurl=":pserver:cvs@10.25.25.181:/usr/local/cvsroot/";

;*/?>
