$('.dropdown-menu').on('click', '.dropdown-menu__btn', function(e){
	var menu = $(this).parent('.dropdown-menu');

	if ($(menu).hasClass('on')) {
		$(menu).removeClass('on');
	} else {
		$(menu).addClass('on');
	}
});