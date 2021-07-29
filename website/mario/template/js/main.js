$(document).ready(function(){
	
	// News article
	$('.newsArticle a.moreLink').click(function(){
		if($(this).is('.open')){
			$(this).removeClass('open').find('span').text('Подробнее');
			$(this).prev('.hidden').slideUp(300);
		}else{
			$(this).addClass('open').find('span').text('Скрыть');
			$(this).prev('.hidden').slideDown(300);
		}
		return false;
	});
	
});