;<?/* this prevents browsers from seeing anything

;authentication server connection information
[authserver]
host="localhost:8088"
path="/xmlrpc"

; messagelink appserver service
[appserver]
host="localhost:7911"
timeout=5500

; commsuite appserver service
[appserver_commsuite]
host="localhost:7912"
timeout=5500

;report server connection information
[reportserver]
host="localhost:8089"
path="/xmlrpc"

[feature]
has_ssl=true
force_ssl=true
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=false
log_dmapi=false
query_trace=true
has_print=false
tmp_dir="/tmp"
log_mobile_api=false

;if not set, warn_earliest is set to "7:00 am"
;and warn_latest is set to "9:00 pm"
warn_earliest=
warn_latest=

[content]
tts="10.25.25.66,8080,/tts/ttsserver"


[translation]
apikey=""
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
