<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />

	<div class="wrapper">
	
	<!-- <div class="main_activity"> -->

	<div class="window newbroadcast">
		<div class="window_title_wrap">

			<h2><?
			echo (isset($_SESSION['message_sender']['template']['subject'])?("Broadcast Template: ". $_SESSION['message_sender']['template']['subject']):"New Broadcast")?>
			</h2>
			
			<ul class="msg_steps cf">
				<li class="active"><a id="tab_1" ><span class="icon">1</span> Subject &amp; Recipients</a></li>
				<li><a id="tab_2"><span class="icon">2</span> Message Content</a></li>
				<li><a id="tab_3"><span class="icon">3</span> Review &amp; Send</a></li>
			</ul>

		</div>
		
		<div class="window_body_wrap">

		<input type="hidden" name=msgsndr-formsnum value="<?=$form->serialnum?>" />

<? include("message_sender/section_one.inc.php"); ?>

<? include("message_sender/section_two.inc.php"); ?>

<? include("message_sender/section_three.inc.php"); ?>
		
		</div><!-- /window_body_wrap -->
		
	</div><!-- endwindow newbroadcast -->
	

	<div class="main_aside">
		<?= addHelpSection();?>
	</div><!-- end main_aside-->
	
</div><!-- end wrapper -->

<? include("message_sender/modals.inc.php"); ?>

<!-- get jQuery and jquery plugins -->
<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
<script type="text/javascript" src="script/jquery.json-2.3.min.js"></script>
<script type="text/javascript" src="script/jquery-datepicker.js"></script>
<script type="text/javascript" src="script/jquery.timer.js"></script>
<script type="text/javascript" src="script/jquery.moment.js"></script>
<script type="text/javascript" src="script/jquery.easycall.js"></script>
<script type="text/javascript" src="script/jquery.translate.js"></script>

<script type="text/javascript" src="script/bootstrap-modal.js"></script>
<script type="text/javascript" src="script/bootstrap-dropdown.js"></script>

<script type="text/javascript" src="script/message_sender_global.js"></script>
<script type="text/javascript" src="script/message_sender_permission.js"></script>
<script type="text/javascript" src="script/message_sender_content_saver.js"></script>
<script type="text/javascript" src="script/message_sender_content.js"></script>
<script type="text/javascript" src="script/message_sender_step.js"></script>
<script type="text/javascript" src="script/message_sender_validate.js"></script>
<script type="text/javascript" src="script/message_sender_submit.js"></script>
<script type="text/javascript" src="script/message_sender.loadmessage.js"></script>
<script type="text/javascript" src="script/message_sender_base.js"></script>
<script type="text/javascript" src="script/message_sender.previewmodal.js"></script>
<script type="text/javascript" src="script/message_sender.emailattach.js"></script>
<script type="text/javascript" src="script/message_sender.facebook.js"></script>
<script type="text/javascript" src="script/message_sender_listbuilder.js"></script>

<script type="text/javascript" src="script/ckeditor/ckeditor_basic.js"></script>
<script type="text/javascript" src="script/htmleditor.js"></script>
<script type="text/javascript" src="script/speller/spellChecker.js"></script>
<script type="text/javascript" src="script/niftyplayer.js.php"></script>
<script type="text/javascript" src="script/datepicker.js"></script>
