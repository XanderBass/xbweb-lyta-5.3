$(function(){
    $(window).on('resize', function(){
        $('body > aside:first').removeClass('toggled');
    });

    $('#menu-main > section.content:first').scrollBox();

    $('body').on('click', 'aside a.do-toggle', function(){
        $(this).closest('aside').toggleClass('toggled');
        $(window).trigger("resize.scrollBox");
        return false;
    }).on('click', 'aside nav > ul > li', function(){
        $(this).toggleClass('active').siblings('li.active').removeClass('active');
        $(this).closest('nav').siblings('nav').each(function(){
            $(this).find('li.active').removeClass('active');
        });
        $(window).trigger("resize.scrollBox");
    }).on('click', 'aside li[class^="menu-item"] a, aside li[class*=" menu-item"] a, .modal > a, a.modal, a.action', function(){
        if ($(this).attr('target')) if ($(this).attr('target') === '_blank') return true;
        var modal  = $(this).parent().hasClass('modal') || $(this).hasClass('modal');
        var loader = $('body').XBWebLoader();
        $.get($(this).attr('href'), {"is-ajax" : "true"}).success(function(ret){
            if (modal) {
                loader.html(ret).removeClass('loading').XBWebInit();
            } else {
                $('#content').html(ret).XBWebInit();
                loader.remove();
            }
        }).error(function(resp){
            var msg = $('<form>').html(resp.responseText);
            loader.html(msg).removeClass('loading');
        });
        return false;
    }).on('submit', 'form', function(){
        var loader = null;
        var data   = {"is-ajax": "true"};
        if ($(this).closest('.modal-wrapper')) {
            loader = $(this).closest('.modal-wrapper');
        } else {
            loader = $('body').XBWebLoader();
        }
        loader.addClass('loading');
        $(this).ajaxSubmit({
            "data"    : data,
            "success" : function(ret) {
                $('#content').html(ret).XBWebInit();
                loader.remove();
            }
        });
        return false;
    }).on('click', 'form button[type=submit]', function(){
        var form = $(this).closest('form');
        if ($(this)[0].hasAttribute('name') && $(this)[0].hasAttribute('value')) {
            var name  = $(this).attr('name');
            var value = $(this).attr('value');
            if (form.find('input[name=' + name + ']').length > 0) {
                form.find('input[name=' + name + ']').val(value);
            } else {
                form.append($('<input type="hidden" name="'+name+'">').val(value));
            }
        }
    });
});