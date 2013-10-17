/* Open popup links for a[rel="tweet"] using href */
$('body').delegate('a[rel="tweet"]', 'click', function(e){
	window.open($(this).attr('href'),'1365383779469','width=700,height=250,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');
	e.preventDefault();
	return false;
});