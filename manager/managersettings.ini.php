;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

;authdb for manager use


;local machine
[db]
persistent=true
host="127.0.0.1"
user="authserver"
pass="aQ9V6THETJWAQpzX"
db="authserver"

;ASP MANAGER
;[db]
;persistent=true
;host="10.80.0.55"
;user="relcom"
;pass="UsO3rq9gva"
;db="authserver"

;override's the information in the shard table in order to read from slaves
;remember shard2 is swapped IDs with shard1 (shard2 is index1, and shard1 is index2)
;readonly[]=10.80.0.56
;readonly[]=10.80.0.51
;readonly[]=10.80.0.61

[feature]
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=false
tmp_dir="/tmp"
has_ssl=false
force_ssl=false

; server to redirect customer link to
customer_url_prefix=https://10.25.25.195

[content]
tts="devbox4,8080,/phone/Tts"




;authdb for manager use
;[db]
;persistent=true
;host="10.80.0.55"
;user="relcom"
;pass="UsO3rq9gva"
;db="authserver"





externalcontent=false;
;*/?>
