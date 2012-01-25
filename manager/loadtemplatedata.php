<?
// NOTE: You must escape all single quotes

function loadTemplateData($useSmsMessagelinkInboundnumber = false) {

// array of all email templates
$templates = array();

////////////////////////////
// NOTIFICATION
////////////////////////////

// notification english html
$templates['notification']['en']['html']['body'] = '
${body}<br><br><hr />

<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: <a href= "${unsubscribelink}">Unsubscribe</a>
</p>
<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students and staff through voice, SMS text, email, and social media.
</p>
';

// notification english plain
$templates['notification']['en']['plain']['body'] = '
${body}



-----
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: ${unsubscribelink}

${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students and staff through voice, SMS text, email, and social media.
';

// notification spanish html
$templates['notification']['es']['html']['body'] = '
${body}<br><br><hr/>

<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace: <a href= "${unsubscribelink}">Unsubscribe</a>
				</p>
				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
				</p>
';

// notification spanish plain
$templates['notification']['es']['plain']['body'] = '
${body}


------
${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace:${unsubscribelink}
 
${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
';

////////////////////////////
// EMERGENCY
////////////////////////////

// emergency english html
$templates['emergency']['en']['html']['body'] = '
${body}<br><br><hr />

<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: <a href= "${unsubscribelink}">Unsubscribe</a>
</p>
<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students, and staff through voice, SMS text, email, and social media.
</p>
';

// emergency english plain
$templates['emergency']['en']['plain']['body'] = '
${body}



-----
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: ${unsubscribelink}

${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students, and staff through voice, SMS text, email, and social media.
';

// emergency spanish html
$templates['emergency']['es']['html']['body'] = '
${body}<br><br><hr/>

<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace: <a href= "${unsubscribelink}">Unsubscribe</a>
				</p>
				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
				</p>
';

// emergency spanish plain
$templates['emergency']['es']['plain']['body'] = '
${body}


------
${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace:${unsubscribelink}
 
${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
';

////////////////////////////
// MESSAGELINK
////////////////////////////

// messagelink english sms
if ($useSmsMessagelinkInboundnumber) {
	$appendinbound = ' or ${inboundnumber}.';
} else {
	$appendinbound = '';
}
$templates['messagelink']['en']['sms']['body'] = '${displayname} sent a msg. To listen ${messagelink}' . $appendinbound . '\nFor info txt HELP';

// messagelink english html
$templates['messagelink']['en']['html']['subject'] = '${displayname} sent a new message';
$templates['messagelink']['en']['html']['fromname'] = '${productname}';
$templates['messagelink']['en']['html']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['messagelink']['en']['html']['body'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
		${displayname} sent a new message!
	</title>
	<meta http-equiv="content-type"
		content="text/html;charset=utf-8" />

</head>
<body style="background: #FFFFFF; font-family:verdana; color:#666666;">
	<table width="100%" cellpadding="10" cellspacing="0" class="backgroundTable" style="background-color: #FFFFFF; border:1px solid #A5A5A5;">
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #A5A5A5; padding:0px; background-color:#FFFFFF;">
				<p style="font-size:26px; color:#669900;">${displayname}</p>
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" >
				<p>
				You are receiving this message because your contact information is associated with <b>${f01} ${f02}</b>.
				</p><p>
				A new message from ${displayname} was sent to you using the ${productname} notification service.
				</p><p>
				Please click below to listen to your message:
				</p><p style="text-align: center;">
				<br/><a href="${messagelink}">
				<img src="http://asp.schoolmessenger.com/i/img/largeicons/phonetalking.jpg" alt="Click here to listen to your message." style="border:none;"/><br/>
				Listen to your message</a>
				</p><p>
				Thank you,<br />${displayname}
				</p>
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #9A9A9A; padding:0px; background-color:#FFFFFF;">
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="padding:5px; background-color:#FFFFFF;">

				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: <a href= "${unsubscribelink}">Unsubscribe</a>
				</p>
				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students and staff through voice, SMS text, email, and social media.
				</p>
			</td>
		</tr>
	</table>

</body>
</html>
';

// messagelink english plain
$templates['messagelink']['en']['plain']['subject'] = '${displayname} sent a new message';
$templates['messagelink']['en']['plain']['fromname'] = '${productname}';
$templates['messagelink']['en']['plain']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['messagelink']['en']['plain']['body'] = '
You are receiving this message because your contact information is associated with <b>${f01} ${f02}</b>.

A new message from ${displayname} was sent to you using the ${productname} notification service.

Follow the link below to play the message.

${messagelink}

Thank you,
${displayname}


-------
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link: ${unsubscribelink}

${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students, and staff through voice, SMS text, email, and social media.
${logoclickurl}


';

// messagelink spanish html
$templates['messagelink']['es']['html']['subject'] = '${displayname} le envió un mensaje nuevo';
$templates['messagelink']['es']['html']['fromname'] = '${productname}';
$templates['messagelink']['es']['html']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['messagelink']['es']['html']['body'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
		¡${displayname} le envió un mensaje nuevo!
	</title>
	<meta http-equiv="content-type"
		content="text/html;charset=utf-8" />

</head>
<body style="background: #FFFFFF; font-family:verdana; color:#666666;">
	<table width="100%" cellpadding="10" cellspacing="0" class="backgroundTable" style="background-color: #FFFFFF; border:1px solid #A5A5A5;">
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #A5A5A5; padding:0px; background-color:#FFFFFF;">
				<p style="font-size:26px; color:#669900;">${displayname}</p>
			</td>
		</tr>

		<tr>
			<td valign="top" align="left" >
				<p>
				Usted esta recibiendo este mensaje porque su información de contacto esta asociada con <b>${f01} ${f02}</b>.
				</p><p>
				Un nuevo mensaje ${displayname} de le fue enviado usando el ${productname} servício de notificación.
				</p><p>
				Escuche este mensaje navegando al siguiente enlace:
				</p><p style="text-align: center;">
				<br/><a href="${messagelink}">
				<img src="http://asp.schoolmessenger.com/i/img/largeicons/phonetalking.jpg" alt="Escuche este mensaje." style="border:none;"/><br/>
				Escuche este mensaje</a>
				</p><p>
				Gracias,<br />${displayname}
				</p>
			</td>
		</tr>
		<tr>

		</tr>
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #9A9A9A; padding:0px; background-color:#FFFFFF;">
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="padding:5px; background-color:#FFFFFF;">

				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace: <a href= "${unsubscribelink}">Unsubscribe</a>
				</p>
				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
				</p>
			</td>
		</tr>
	</table>

</body>
</html>
';

// messagelink spanish plain
$templates['messagelink']['es']['plain']['subject'] = '${displayname} le envió un mensaje nuevo';
$templates['messagelink']['es']['plain']['fromname'] = '${productname}';
$templates['messagelink']['es']['plain']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['messagelink']['es']['plain']['body'] = '
Usted esta recibiendo este mensaje porque su información de contacto esta asociada con ${f01} ${f02}.
 
Un nuevo mensaje ${displayname} de le fue enviado usando el ${productname} servício de notificación.
 
Escuche este mensaje navegando al siguiente enlace:
 
${messagelink}
 
Gracias,
${displayname}


------
${displayname} le gustaría continuar comunicándose con usted por medio de correo electrónico.  Si usted prefiere ser borrado de nuestra lista, por favor contacte ${displayname} directamente.  Para dejar de recibir todos los mensajes de correo electrónico distribuidos por nuestro servicio de ${productname}, siga este enlace:${unsubscribelink}
 
${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
';


////////////////////////////
// SUBSCRIBER
////////////////////////////

// subscriber english html
$templates['subscriber']['en']['html']['subject'] = '${displayname} ${productname} Account Termination Warning';
$templates['subscriber']['en']['html']['fromname'] = '${productname}';
$templates['subscriber']['en']['html']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['subscriber']['en']['html']['body'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
		Reminder: ${displayname} ${productname} Account Termination Warning
	</title>
	<meta http-equiv="content-type"
		content="text/html;charset=utf-8" />

</head>
<body style="background: #FFFFFF; font-family:verdana; color:#666666;">
	<table width="100%" cellpadding="10" cellspacing="0" class="backgroundTable" style="background-color: #FFFFFF; border:1px solid #A5A5A5;">
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #A5A5A5; padding:0px; background-color:#FFFFFF;">
				<p style="font-size:26px; color:#669900;">${displayname}</p>
			</td>
		</tr>

		<tr>
			<td valign="top" align="left"">
				<p>
				The ${productname} account you created to manage your contact preferences for ${displayname} has not been logged into recently. Your account will be automatically disabled in ${daystotermination} days if you do not log into it.
				</p><p>
				To keep your account active please login.
				</p><p>
				Click the link below to sign in, or simply enter the address below into your browser.
				</p><p>
				<a href=${loginurl}>${loginurl}</a>
				<p>
				This is an automatically generated email. Please <b>DO NOT</b> reply.
				</p>
				</p><p>
				Thank you,<br />
				${displayname}
				</p>
			</td>
		</tr>

		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #9A9A9A; padding:0px; background-color:#FFFFFF;">
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="padding:5px; background-color:#FFFFFF;">

				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${displayname} would like to continue connecting with you through ${productname}.  If you prefer to no longer receive messages, please log in and close your account.
				</p>
				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
				${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students and staff through voice, SMS text, email, and social media.
				</p>
			</td>
		</tr>
	</table>

</body>
</html>
';

// subscriber english plain
$templates['subscriber']['en']['plain']['subject'] = '${displayname} ${productname} Account Termination Warning';
$templates['subscriber']['en']['plain']['fromname'] = '${productname}';
$templates['subscriber']['en']['plain']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['subscriber']['en']['plain']['body'] = '
The ${productname} account you created to manage your contact preferences for ${displayname} has not been logged into recently. Your account will be automatically disabled in ${daystotermination} days if you do not log into it.

To keep your account active please login.

Click the link below to sign in, or simply enter the address below into your browser.

${loginurl}

This is an automatically generated email. Please DO NOT reply.

Thank you,
${displayname}


------
${displayname} would like to continue connecting with you through ${productname}.  If you prefer to no longer receive messages, please log in and close your account.

${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students and staff through voice, SMS text, email, and social media.
';

// subscriber spanish html
$templates['subscriber']['es']['html']['subject'] = '${displayname} ${productname} Advertencia Cancelación de cuenta';
$templates['subscriber']['es']['html']['fromname'] = '${productname}';
$templates['subscriber']['es']['html']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['subscriber']['es']['html']['body'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>
		Recordatorio: ${displayname} ${productname} Advertencia Cancelación de cuenta
	</title>
	<meta http-equiv="content-type"
		content="text/html;charset=utf-8" />

</head>
<body style="background: #FFFFFF; font-family:verdana; color:#666666;">
	<table width="100%" cellpadding="10" cellspacing="0" class="backgroundTable" style="background-color: #FFFFFF; border:1px solid #A5A5A5;">
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #A5A5A5; padding:0px; background-color:#FFFFFF;">
				<p style="font-size:26px; color:#669900;">${displayname}</p>
			</td>
		</tr>
		<tr>
			<td valign="top" align="left"">
				<p>
				La cuenta de ${productname} que usted creó para administrar sus preferencias de contacto para ${displayname} no ha sido ingresada recientemente.  Su cuenta sera inabilitada automaticamente en ${daystotermination} si usted no ingresa.
				</p><p>
				Para mantener su cuenta activa por favor entre a su cuenta.
				</p><p>
				Haga clic en el enlace para entrar, o simplemente escriba la dirección abajo en su navegador.
				</p><p>
				<a href=${loginurl}>${loginurl}</a>
				<p>
				Este es un mensaje generado automáticamente. Por favor, <b>NO</b> responde.
				</p>
				</p><p>
				Gracias,<br />
				${displayname}
				</p>
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="border-bottom: 1px solid #9A9A9A; padding:0px; background-color:#FFFFFF;">
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="padding:5px; background-color:#FFFFFF;">

				<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
					${displayname} le gustaría continuar comunicándose con usted por medio de ${productname}. Si usted prefiere no recibir mensajes, por favor entre al sitio y cierre su cuenta.
					</p>
					<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
					${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
				</p>
			</td>
		</tr>
	</table>

</body>
</html>
';

// subscriber spanish plain
$templates['subscriber']['es']['plain']['subject'] = '${displayname} ${productname} Advertencia Cancelación de cuenta';
$templates['subscriber']['es']['plain']['fromname'] = '${productname}';
$templates['subscriber']['es']['plain']['fromaddr'] = 'contactme@schoolmessenger.com';
$templates['subscriber']['es']['plain']['body'] = '
La cuenta de ${productname} que usted creó para administrar sus preferencias de contacto para ${displayname} no ha sido ingresada recientemente. Su cuenta sera inabilitada automaticamente en ${daystotermination} si usted no ingresa.

Para mantener su cuenta activa por favor entre a su cuenta.

Haga clic en el enlace para entrar, o simplemente escriba la dirección abajo en su navegador.

${loginurl}

Este es un mensaje generado automáticamente. Por favor, NO responde.

Gracias,
${displayname}


------
${displayname} le gustaría continuar comunicándose con usted por medio de ${productname}. Si usted prefiere no recibir mensajes, por favor entre al sitio y cierre su cuenta.
 
${productname} es un servicio de notificación usado por los sistemas escolares más importantes de la nación para comunicarse con padres, estudiantes, y personal por medio de voz, texto, correo electrónico y medios de comunicación social.
';

////////////////////////////
// SURVEY
////////////////////////////

// survey english html
$templates['survey']['en']['html']['body'] = '
${body}<br><br>${surveylink}
<br><br><hr />
<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: <a href= "${unsubscribelink}">Unsubscribe</a>
</p>
<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students, and staff through voice, SMS text, email, and social media.
</p>
';

// survey english plain
$templates['survey']['en']['plain']['body'] = '
${body}

${surveylink}


------
${displayname} would like to continue connecting with you via email.  If you prefer to be removed from our list, please contact ${displayname} directly.  To stop receiving all email messages distributed through our ${productname} service, follow this link and confirm: ${unsubscribelink}

${productname} is a notification service used by the nation\'s leading school systems to connect with parents, students, and staff through voice, SMS text, email, and social media.
';


////////////////////////////
// MONITOR
////////////////////////////

// monitor english html
$templates['monitor']['en']['html']['subject'] = 'Monitor Alert: ${monitoralert}';
$templates['monitor']['en']['html']['fromname'] = '${productname}';
$templates['monitor']['en']['html']['fromaddr'] = 'noreply@schoolmessenger.com';
$templates['monitor']['en']['html']['body'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Monitor Alert - ${monitoralert}</title>
<meta http-equiv="content-type"content="text/html;charset=utf-8" />
</head>
<body style="background: #FFFFFF; font-family:verdana; color:#666666;">
<div style="padding: 20px 20px 40px 20px;">
<table width="500px" cellpadding="10" cellspacing="0" class="backgroundTable" style="background-color: #FFFFFF; border:1px solid #A5A5A5;" align="center">
<tr>
<td valign="top" align="left" style="border-bottom: 1px solid #A5A5A5; padding:0px;padding-left:10px; background-color:#FFFFFF;">
<p style="margin:0px;margin-top:10px;font-size:26px; color:#669900;">
<img src="https://asp.schoolmessenger.com/i/img/largeicons/bell.jpg" alt="" style="vertical-align:top;border:none;"/> Monitor Alert  </p>
<p style="margin:10px;margin-top:-10px;padding-left:50px;font-size:20px; color:#6B6B6B">${monitoralert}</p></td></tr><tr><td valign="top" align="left" >

<!-- $beginBlock job-active -->
<p>
Job from ${displayname}<br />
</p>
<table style="font-size:12px;border-collapse: collapse;margin-left:20px;">
<tr><td align="right" style="font-weight:bold;">Submitted by:</td><td style="font-style:italic;">${firstname} ${lastname}</td></tr>
<tr><td align="right" style="font-weight:bold;">Name:</td><td style="font-style:italic;">${name}</td></tr>
<tr><td align="right" style="font-weight:bold;">Description:</td><td style="font-style:italic;">${description}</td></tr>
<tr><td align="right" style="font-weight:bold;">Type:</td><td style="font-style:italic;">${type}</td></tr>
<tr><td align="right" style="font-weight:bold;">Startdate:</td><td style="font-style:italic;">${startdate}</td></tr>
<tr><td align="right" style="font-weight:bold;">Days to run:</td><td style="font-style:italic;">${daystorun}</td></tr>
</table>
<p>Follow link for additional details:</p><p style="text-align: center;"><br/>
<a href="${monitorlink}">
<img src="https://asp.schoolmessenger.com/i/img/largeicons/letter.jpg" alt="Click here to" style="border:none;"/><br/>View Details</a>
</p>
<!-- $endBlock job-active -->
<!-- $beginBlock job-complete -->
<p>
Job from ${displayname}<br />
</p>
<table style="font-size:12px;border-collapse: collapse;margin-left:20px;">
<tr><td align="right" style="font-weight:bold;">Submitted by:</td><td style="font-style:italic;">${firstname} ${lastname}</td></tr>
<tr><td align="right" style="font-weight:bold;">Name:</td><td style="font-style:italic;">${name}</td></tr>
<tr><td align="right" style="font-weight:bold;">Description:</td><td style="font-style:italic;">${description}</td></tr>
<tr><td align="right" style="font-weight:bold;">Type:</td><td style="font-style:italic;">${type}</td></tr>
<tr><td align="right" style="font-weight:bold;">Startdate:</td><td style="font-style:italic;">${startdate}</td></tr>
<tr><td align="right" style="font-weight:bold;">Days to run:</td><td style="font-style:italic;">${daystorun}</td></tr>
</table>
<p>Follow link for additional details:</p><p style="text-align: center;"><br/>
<a href="${monitorlink}">
<img src="https://asp.schoolmessenger.com/i/img/largeicons/letter.jpg" alt="Click here to" style="border:none;"/><br/>View Details</a>
</p>
<!-- $endBlock job-complete -->
<!-- $beginBlock job-firstpass -->
<p>
Job from ${displayname}<br />
</p>
<table style="font-size:12px;border-collapse: collapse;margin-left:20px;">
<tr><td align="right" style="font-weight:bold;">Submitted by:</td><td style="font-style:italic;">${firstname} ${lastname}</td></tr>
<tr><td align="right" style="font-weight:bold;">Name:</td><td style="font-style:italic;">${name}</td></tr>
<tr><td align="right" style="font-weight:bold;">Description:</td><td style="font-style:italic;">${description}</td></tr>
<tr><td align="right" style="font-weight:bold;">Type:</td><td style="font-style:italic;">${type}</td></tr>
<tr><td align="right" style="font-weight:bold;">Startdate:</td><td style="font-style:italic;">${startdate}</td></tr>
<tr><td align="right" style="font-weight:bold;">Days to run:</td><td style="font-style:italic;">${daystorun}</td></tr>
</table>
<p>Follow link for additional details:</p><p style="text-align: center;"><br/>
<a href="${monitorlink}">
<img src="https://asp.schoolmessenger.com/i/img/largeicons/letter.jpg" alt="Click here to" style="border:none;"/><br/>View Details</a>
</p>
<!-- $endBlock job-firstpass -->
</td>
</tr><tr>
<td valign="top" align="left" style="padding:5px;border-top: 1px solid #A5A5A5; background-color:#FFFFFF;">
<!-- $beginBlock monitor -->
<p style="font-family:verdana; color:#6B6B6B; font-size:75%">
You are receiving this email because the monitor feature is configured for your account. To disable or change the alerts, please log into your account and navigate to the System->Monitor tab.
<br/>
<br/>
DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.
</p>
<!-- $endBlock monitor -->
</td></tr>
</table>
</div>
</body>
</html>
';

// monitor english plain
$templates['monitor']['en']['plain']['subject'] = 'Monitor Alert: ${monitoralert}';
$templates['monitor']['en']['plain']['fromname'] = '${productname}';
$templates['monitor']['en']['plain']['fromaddr'] = 'noreply@schoolmessenger.com';
$templates['monitor']['en']['plain']['body'] = '
<!-- $beginBlock job-active -->
Job from ${displayname}

Submitted by: ${firstname} ${lastname}
Name: ${name}
Description: ${description}
Type: ${type}
Startdate: ${startdate}
Days to run: ${daystorun}

Follow link for additional details:
${monitorlink}
<!-- $endBlock job-active -->
<!-- $beginBlock job-complete -->
Job from ${displayname}

Submitted by: ${firstname} ${lastname}
Name: ${name}
Description: ${description}
Type: ${type}
Startdate: ${startdate}
Days to run: ${daystorun}

Follow link for additional details:
${monitorlink}
<!-- $endBlock job-complete -->
<!-- $beginBlock job-firstpass -->
Job from ${displayname}

Submitted by: ${firstname} ${lastname}
Name: ${name}
Description: ${description}
Type: ${type}
Startdate: ${startdate}
Days to run: ${daystorun}

Follow link for additional details:
${monitorlink}
<!-- $endBlock job-firstpass -->
<!-- $beginBlock monitor -->
-----

You are receiving this email because the monitor feature is configured for your account. To disable or change the alerts, please log into your account and navigate to the System->Monitor tab.

DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.
<!-- $endBlock monitor -->
';


return $templates;
}

?>