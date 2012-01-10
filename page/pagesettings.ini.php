;<?/* this prevents browsers from seeing anything

; pagelink appserver service
[appserver_pagelink]
host="localhost:7913"
timeout=5500

; commsuite appserver service
[appserver_commsuite]
host="localhost:7912"
timeout=5500

[feature]
;url is where to redirect to for naked "/" access on the page app (and code is not specified in any arguments)
redirect_url=http://www.schoolmessenger.com

;*/?>