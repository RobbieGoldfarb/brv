var poorResponse = false;
var dataLocation = 0;

function insertRating(val, id) {
	if(!$('#imdone').is(':visible')){
		$('#imdone').slideDown(125);
	}
	
	if(val <= 40){
		poorResponse = true;
	}
	
	$.post("/overall/insert/insert_rating.php", { value : val, post_id : id });
    return false;
}
function disappearRating(post_id) {
	$("#aspect_"+post_id).addClass('rated').slideUp(250);
	if($('#aspects > div.aspect:not(.rated)').length == 0){
		thanks();
	}
}

$(document).ready(function(){
	$("#imdone").click(function() { 
		thanks();
	});

	$(window).bind('touchmove scroll scrollstart', function() {
		if ($(window).scrollTop() >= 20) { 
			$('.topbar .full-message').hide();
			$('.topbar .shortened-message').show();
			$('.topbar, .top-spacer').addClass('fixed');
		} else {
			$('.topbar .full-message').show();
			$('.topbar .shortened-message').hide();
			$('.topbar, .top-spacer').removeClass('fixed');
		}
	});
	$('.topbar i').click(function () {
		$('html,body').animate({
        	scrollTop: $(window).scrollTop() + 100
    	});
	});
	
	$(window).resize(function(){
		$('div.star').each(function(){
			$(this).css('height', $(this).outerWidth()+'px')
		});
	});
	$(window).resize();
	
	if ($('#btn-submit-comment').length > 0){
		$('#btn-submit-comment').click(showCommentForm);
	}
	
	initPostPreData();
});

function thanks(){
	if (dataLocation == 0 || dataLocation == 3){
		$('#aspects, .fixed-toolbar').fadeOut(300, function () {
			$('#email_connect').show();
		});
	} else {
		if ((dataLocation == 1 && poorResponse) || dataLocation == 2){
			showPostPre({ thanks : function(){
				$('#aspects, .fixed-toolbar').fadeOut(300, function () {
					$('#email_connect').show();
				});
			} });
		} else {
			$('#aspects, .fixed-toolbar').fadeOut(300, function () {
				$('#email_connect').show();
			});
		}
	}
}

function initPostPreData(){
	var $dataCollect = $('#data-collect');
	
	if ($dataCollect.length == 0){ return; }
	
	dataLocation = parseInt($dataCollect.attr('data-location'));
	
	if($('.pp-email-options', $dataCollect).length > 0){
		var $target = $($('.pp-email-options', $dataCollect).attr('data-for'), $dataCollect);
		
		$target.find('.data-email input').hide();
		try {
			$target.find('.data-email > span').text(
				$('.pp-email-options .pp-email-option.selected', $dataCollect).attr('data-domain')
			);
		} catch (err){	}
		
		$target.children('input[type=text]').change(function(){
			$target.children('input[type=hidden]').val($target.children('input[type=text]').val() + '@' + (
				$target.find('.data-email > span').is(':visible') ?
				$target.find('.data-email > span').text() :
				$target.find('.data-email > input').val()
			));
		});
		
		$target.find('.data-email > input').change(function(){
			$target.children('input[type=text]').change();
		});
		
		$('.pp-email-options .pp-email-option', $dataCollect).click(function(){
			if ($('.pp-email-options .pp-email-option.selected', $dataCollect).length > 0 &&
				$(this).is($('.pp-email-options .pp-email-option.selected', $dataCollect))){
				return false;
			}
			
			$('.pp-email-options .pp-email-option', $dataCollect).removeClass('selected');
			$(this).addClass('selected');
			
			var val = $(this).attr('data-domain');
			if(val == 'other'){
				$target.find('.data-email > span').hide();
				$target.find('.data-email > input').val('').show().focus();
			} else {
				$target.find('.data-email > span').text(val);
				$target.find('.data-email > input').hide();
				$target.find('.data-email > span').show();
			}
			
			$target.children('input[type=text]').change();
		});
	}
	
	if (dataLocation == 3){
		// Before survey.
		$('.pp-dismiss-circle', $dataCollect).hide();
		showPostPre();
	}
}

function showPostPre(e){
	var $dataCollect = $('#data-collect');

	if(!$dataCollect || $dataCollect.length === 0){
		return false;
	}
	
	$('html, body').addClass('locked');

	$('.pp-email-options .pp-email-option', $dataCollect).first().click();
	
	$('#data-collect-overlay').stop().fadeIn(200, function(){
		$dataCollect.stop().fadeIn(350, function(){
			$('.content .auto-focus', $dataCollect).length > 0 &&
				$('.content .auto-focus', $dataCollect).focus();
		});
	});
	
	initSessionDataForm($dataCollect, '#data-collect-overlay', e);
}

function showCommentForm(e){
	var $comments = $('#comment-form');

	if(!$comments || $comments.length === 0){
		return false;
	}
	
	$('html, body').addClass('locked');
	
	$('#comment-form-overlay').stop().fadeIn(200, function(){
		$comments.stop().fadeIn(350, function(){
			$('.content .auto-focus', $comments).length > 0 &&
				$('.content .auto-focus', $comments).focus();
		});
	});
	
	initSessionDataForm($comments, '#comment-form-overlay', e);
}

function initSessionDataForm($container, overlay, e) {
	if ($container.hasClass('pp-configured')){
		return;
	} else {
		$container.addClass('pp-configured');
	}
	
	if($('[data-type=submit]', $container).length > 0){
		$('[data-type=submit]', $container).on('click', function(){
			
			if($('.pp-require', $container).length > 0){
				var failed = false;
				$('.pp-require', $container).each(function(){
					if($(this).val().length == 0){
						failed = true;
					}
				});
				
				if(failed){
					return false;
				}
			}
			
			var fields = {};
			$('[data-pp-key]', $container).each(function(){
				fields[$(this).attr('data-pp-key')] = {
					value: $(this).val(),
					label: $(this).attr('data-pp-label') || $(this).attr('data-pp-key')
				};
			});
			
			$.post("/overall/insert/insert_session_data.php", { fields : JSON.stringify(fields) });
			
			$container.stop().fadeOut(200, function(){
				$(overlay).stop().fadeOut(100, function(){
					$('input, textarea', $container).val('');
					$('html, body').removeClass('locked');
				});
			});
			
			if(e && e.thanks){
				e.thanks();
			}
		});
	}

	if($('[data-type=dismiss]', $container).length > 0){
		$('[data-type=dismiss]', $container).on('click', function(){
			$container.stop().fadeOut(200, function(){
				$(overlay).stop().fadeOut(100, function(){
					$('input, textarea', $container).val('');
					$('html, body').removeClass('locked');
				});
			});
		});
	}
}