require(["jquery", "jquery-ui.min", "dropdown"], function($) {
    $(function() {
		var madeChanges = false;

        // submit button outside of <form>
        $('.btn--submit').click(function(e){
            madeChanges = false; // turn this off for the submission
            $('form.form--labels-dobis').submit();
            e.preventDefault();
            return false;
        });

    	function unloadPage(){
    		if (madeChanges){
    			return "Leaving the page now will discard your changes."
    		}
    	}

    	window.onbeforeunload = unloadPage;

    $( ".js-labels-sort" ).sortable({
      items: "li:not(.ui-state-disabled)",
      handle: ".drag-handle",
      update: function( event, ui ) {
        var stack_array = $( ".js-labels-sort" ).sortable( "toArray", { attribute: 'data-slug' });
        stack_array.forEach(updateStackOrder);
      }
    });

    function updateStackOrder(element, index, array){ $('#stack_'+(index+1)).val(element); updateForm(); }

    function updateForm()
    {
        if (!$('.btn--big-save').hasClass('on'))
        {
            $('.btn--big-save').addClass('on');
        }

        madeChanges = true;
    }

    });
});