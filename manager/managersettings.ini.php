;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

; commsuite appserver service
[appserver_commsuite]
host="localhost:7912"
timeout=5500

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

;db for aspcalls
[aspcalls]
host="localhost"
user=rppt
pass=
db=aspcalls
callstable=aspcalls2

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
autologoutminutes=60

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
tts="10.25.25.232,8080,/tts/ttsserver"

[servermanagement]
manageservers=true
cvsurl=":pserver:cvs@10.25.25.181:/usr/local/cvsroot/"

[memcache]
use_memcache_sessions=false
memcache_session_expire_mins=30
memcache_session_lock_seconds=60
memcache_session_lock_retry_us=50000
; configure one or more memcached_url entries. Rememebr to use square brackets to denote an array!
; ie tcp://host1:11211?persistent=1&weight=1&timeoutms=1000&retry_interval=15
; This is the same format as documented for memcache session support, and is also parsed and used for
; other memcache calls.
; if no urls are defined, memache support is disabled
memcached_url[]="tcp://127.0.0.1:11211?persistent=1&timeoutms=1000"

;*/?>
