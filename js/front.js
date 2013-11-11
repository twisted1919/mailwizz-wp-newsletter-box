jQuery(document).ready(function($){
    
    $(document).on('submit', '.mwznb-widget form', function(){
        var $this = $(this), 
            $parent = $this.closest('.mwznb-widget');
        
        if (!$this.data('uid')) {
            return false;
        }
        
        var ajaxurl = $parent.data('ajaxurl');
        var data = $this.serialize() + '&action=mwznb_subscribe&uid='+$this.data('uid');
        
        $('.message', $parent).text('Please wait...');
        $.post(ajaxurl, data, function(json) {
            $('.message', $parent).text(json.message);
            if (json.result == 'success') {
                $('input[type=text], textarea', $parent).val('').blur();
            }
        }, 'json');
        
        return false;
    });
    
});