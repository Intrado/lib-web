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
		$('#msgsndr_tab_phone').hide();
		$('#msgsndr_tab_email').hide();
		$('#msgsndr_tab_sms').hide();
		$('#msgsndr_tab_social').hide();
		
		// Add phone tab
		$('#msgsndr_ctrl_phone').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#msgsndr_ctrl_phone').parent().addClass("active");
			$('#msgsndr_tab_email').hide();
			$('#msgsndr_tab_sms').hide();
			$('#msgsndr_tab_social').hide();
			$('#msgsndr_tab_phone').show();
			return false;
		});
		
		// Add email tab
		$('#msgsndr_ctrl_email').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#msgsndr_ctrl_email').parent().addClass("active");
			$('#msgsndr_tab_phone').hide();
			$('#msgsndr_tab_sms').hide();
			$('#msgsndr_tab_social').hide();
			$('#msgsndr_tab_email').show();
			return false;
		});
		
		// Add sms tab
		$('#msgsndr_ctrl_sms').live("click", function(){
			$('ul.msg_content_nav').children().removeClass();
			$('#msgsndr_ctrl_sms').parent().addClass("active");
			$('#msgsndr_tab_phone').hide();
			$('#msgsndr_tab_email').hide();
			$('#msgsndr_tab_social').hide();
			$('#msgsndr_tab_sms').show();
			return false;
		});
		
		// Add social tab
		$('#msgsndr_ctrl_social').live("click", function() {
			$('ul.msg_content_nav').children().removeClass();
			$('#msgsndr_ctrl_social').parent().addClass("active");
			$('#msgsndr_tab_phone').hide();
			$('#msgsndr_tab_email').hide();
			$('#msgsndr_tab_sms').hide();
			$('#msgsndr_tab_social').show();
			return false;
		});
	
		// modal windows -- script/bootstrap-modal.js
		$('#msgsndr_choose_list').modal({
			show: false
			})		
		$('#msgsndr_build_list').modal({
			show: false
			})	
		$('#msgsndr_saved_message').modal({
			show: false
			})	
		});
  });
}) (jQuery);