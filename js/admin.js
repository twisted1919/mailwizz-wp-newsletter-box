jQuery(document).ready(function($){
    
    $(document).on('click', '.mwz-fetch-available-lists', function(){
        var $parent = $(this).closest('form');

        var data = {
            action: 'mwznb_fetch_lists',
            api_url: $('.mwz-api-url', $parent).val(),
            public_key: $('.mwz-public-key', $parent).val(),
            private_key: $('.mwz-private-key', $parent).val()
        };
        
        $('.mwz-spinner', $parent).show();
        
        $.post(ajaxurl, data, function(json) {
            $('.spinner', $parent).hide();
            
            if (json.result == 'error' && json.errors) {
                var error = '';
                for (i in json.errors) {
                    error += json.errors[i] + "\n";
                }
                $('.lists-container', $parent).hide();
                alert(error);
            } else if (json.result == 'success' && json.lists) {
                var $select = $('.mwz-mail-lists-dropdown', $parent);
                $select.empty();
                for (i in json.lists) {
                    var opt = new Option();
                    opt.value = json.lists[i].list_uid;
                    opt.text = json.lists[i].name;
                    opt.selected = json.lists[i].list_uid == $select.data('listuid');
                    $select.append(opt);
                }
                $('.lists-container', $parent).show();
            }
        }, 'json');
        
        return false;
    
    }).on('change', '.mwz-mail-lists-dropdown', function(){
        
        var $this = $(this), $parent = $this.closest('form');
        
        var data = {
            action: 'mwznb_fetch_list_fields',
            api_url: $('.mwz-api-url', $parent).val(),
            public_key: $('.mwz-public-key', $parent).val(),
            private_key: $('.mwz-private-key', $parent).val(),
            field_name: $this.data('fieldname'),
            list_uid: $this.val()
        };
        
        $('.mwz-spinner', $parent).show();
        $('.fields-container, .generated-form-container', $parent).hide();
        $('.generated-form-container textarea', $parent).val('');
        
        $.post(ajaxurl, data, function(html) {
            $('.spinner', $parent).hide();
            $('.table-container', $parent).html(html);
            
            if ($this.val() != '') {
                $('.fields-container, .generated-form-container', $parent).show();
            }
        });
        
    });
    
});