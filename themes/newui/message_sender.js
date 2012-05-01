/*
 * jQuery for newui message sender
 ***********************************
 */

jQuery.noConflict();
(function($) { 
  $(function() {
		$(document).ready(function() {
		
		// hide tabs two and three by default and make step one the active tab
		$('#msg_section_2').hide();
		$('#msg_section_3').hide();
		$('#tab1').parent().addClass("active");
		
		// subject and recipients tab
		$('#tab1').live("click", function(){
			$('ul.msg_steps').children().removeClass();
			$('#tab1').parent().addClass("active");
			
			$('#msg_section_1').show();
			$('#msg_section_2','#msg_section_3').hide();
			return false;
			});
		
		// message content tab
		$('#tab2').live("click", function(){
			$('ul.msg_steps').children().removeClass();
			$('#tab2').parent().addClass("active");
			$('#msg_section_1').hide();
			$('#msg_section_3').hide();
			$('#msg_section_2').show();
			return false;
			});
		
		// review and send tab
		$('#tab3').live("click", function(){
			$('ul.msg_steps').children().removeClass();
			$('#tab3').parent().addClass("active");
			$('#msg_section_1').hide();
			$('#msg_section_2').hide();
			$('#msg_section_3').show();
			return false;
			});
		
		// hide the +Add ... content for the message content section 
		$('#tab_phone').hide();
		$('#tab_email').hide();
		$('#tab_sms').hide();
		$('#tab_social').hide();
		
		// Add phone tab
		$('#ctrl_phone').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#ctrl_phone').parent().addClass("active");
			$('#tab_email').hide();
			$('#tab_sms').hide();
			$('#tab_social').hide();
			$('#tab_phone').show();
			return false;
		});
		
		// Add email tab
		$('#ctrl_email').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#ctrl_email').parent().addClass("active");
			$('#tab_phone').hide();
			$('#tab_sms').hide();
			$('#tab_social').hide();
			$('#tab_email').show();
			return false;
		});
		
		// Add sms tab
		$('#ctrl_sms').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#ctrl_sms').parent().addClass("active");
			$('#tab_phone').hide();
			$('#tab_email').hide();
			$('#tab_social').hide();
			$('#tab_sms').show();
			return false;
		});
		
		// Add social tab
		$('#ctrl_social').live("click", function() {
			$('ul.msg_content_nav').children().removeClass();
			$('#ctrl_social').parent().addClass("active");
			$('#tab_phone').hide();
			$('#tab_email').hide();
			$('#tab_sms').hide();
			$('#tab_social').show();
			return false;
		});
	
		// modal windows -- script/bootstrap-modal.js
		$('#choose_list').modal({
			show: false
			})		
		$('#build_list').modal({
			show: false
			})	
		$('#saved_message').modal({
			show: false
			})	
		});
  });
}) (jQuery);