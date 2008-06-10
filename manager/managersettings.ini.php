;<?/* this prevents browsers from seeing anything

;authdb for manager use
[db]
persistent=true
host="localhost"
user="root"
pass=""
db="authserver"


[feature]
log_dir="/commsuite/logs/"
log_db_errors=true
log_db_queries=true
tmp_dir="/tmp"
has_ssl=false
force_ssl=false

; server to redirect customer link to
customer_url_prefix=http://localhost

[content]
tts="devbox4,8080,/phone/Tts"

externalcontent=false;
;*/?>
