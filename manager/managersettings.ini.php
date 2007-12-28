;<?/* this prevents browsers from seeing anything

;authdb for manager use
[db]
persistent=true
host="localhost:3306"
user="root"
pass=""
db="authserver"


[feature]
log_dir="/usr/commsuite/logs/"
log_db_errors=true
log_db_queries=true
tmp_dir="/tmp"
has_ssl=false
force_ssl=false

; server to redirect customer link to
customer_url_prefix=https://asp.schoolmessenger.com

[content]
tts="localhost,8080,/phone/Tts"

externalcontent=false;
;*/?>
