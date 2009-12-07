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

; server to redirect customer link to
customer_url_prefix=https://localhost

[content]
tts="localhost,8080,/phone/Tts"

;*/?>
