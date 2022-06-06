XBWebUI = {
    "env" : {
        "isIE" : false
    },
    "selector" : function(s) {
        var a = s.replace(/\[\+ui\+]/g, '.xbweb-ui');
        var b = '.xbweb-ui ' + s.replace(/\[\+ui\+]/g, '');
        return b + ', ' + a;
    },
    "intval" : function(v) {
        try { return parseInt(v); } catch (e) { return 0; }
    },
    "init" : function() {
        this.env.isIE = /*@cc_on!@*/false;
    }
};

(function($){
    $.fn.XBWebLoader = function(){
        var loader = $('<div>').addClass('modal-wrapper loading active');
        $(this).append(loader);
        return loader;
    };

    $.fn.XBWebUIIntAttr = function(k, d){
        d = d || 0;
        if ($(this).attr('data-' + k)) return XBWebUI.intval($(this).attr('data-' + k));
        return d;
    };

    $.fn.XBWebUIIntData = function(k, d){
        d = d || 0;
        if ($(this).data(k)) return XBWebUI.intval($(this).data(k));
        return d;
    };

    $.fn.XBWebUIWYSIWYG = function() {
        $(this).removeAttr('placeholder').wrap('<div class="xbweb-ui-wysiwyg"></div>');
        var INPUT   = $(this)[0];
        var iFrame  = $('<iframe/>').attr('src', 'about:blank');
        var ToolBar = $('<div/>').addClass('tool-bar wysiwyg');
        var CodeBar = $('<div/>').addClass('tool-bar code');
        var CmdList = [
            'bold', 'italic', 'strikethrough', 'underline', '',
            'subscript', 'superscript', '',
            'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'
        ];
        if ($(this).hasClass('allowed-links') || $(this).hasClass('allowed-images')) CmdList.push('');
        if ($(this).hasClass('allowed-links')) CmdList.push('createLink');
        if ($(this).hasClass('allowed-images')) CmdList.push('insertImage');
        $.each(CmdList, function(index, value){
            var btn = $('<button>');
            if (value === '') {
                btn.addClass('splitter');
            } else {
                btn.attr('data-command', value);
            }
            ToolBar.append(btn);
        });
        ToolBar.append($('<button/>').addClass('cmd-code'));
        CodeBar.append($('<button/>').addClass('cmd-wysiwyg'));
        $(this).parent().append(iFrame).prepend(ToolBar).prepend(CodeBar);
        var iDoc = XBWebUI.isIE ? iFrame[0].contentWindow.Document : iFrame[0].contentDocument;
        iDoc.open();
        iDoc.write('<html><head></head><body>'+ $(this).val() +'</body></html>');
        iDoc.close();
        iDoc.designMode = "on";

        iDoc.onblur = function(){
            INPUT.value = iDoc.body.innerHTML;
        };
        /*
            iDoc.onkeydown = function(e){
              if (e.keyCode === 13) {
                iDoc.execCommand('insertParagraph');
                return false;
              }
            }; */
    };

    $.fn.XBWebUIWYSIWYG_DOC = function(){
        var F = $(this).closest('.xbweb-ui-wysiwyg').find('iframe')[0];
        return XBWebUI.isIE ? F.contentWindow.Document : F.contentDocument;
    };

    $.fn.XBWebInit = function(){
        $(this).find('textarea.wysiwyg').each(function(){
            $(this).XBWebUIWYSIWYG();
        })
    };
})(jQuery);

$(function(){
    /** INIT **/
    XBWebUI.init();
    var HTML = $('html');

    /** COMMON UI **/
    HTML.on('click', XBWebUI.selector('.tabs[+ui+] a'), function(){
        if ($(this).hasClass('disabled')) return;
        var taba = $(this);
        var tabt = $(taba.attr('href'));
        taba.addClass('active').siblings('a').removeClass('active');
        tabt.addClass('active').siblings('.tab').removeClass('active');
        taba.trigger('xbweb.selectTab');
        tabt.trigger('xbweb.showTab');
        return false;
    }).on('click', XBWebUI.selector('.data-table[+ui+] tr td'), function(){
        if ($(this).hasClass('actions')) return;
        var row = $(this).closest('tr');
        var chk = false;
        row.find('.checker input[type="hidden"]').each(function(){
            chk = $(this).val() === '1';
        });
        row.find('.checker input[type="hidden"]').val(chk ? 0 : 1);
    }).on('click', XBWebUI.selector('.data-table[+ui+] tr th'), function(){
        if (!$(this).hasClass('sortable')) return;

        var rows, i, x, y, ss;
        var table = $(this).closest('table')[0];
        var n     = $(this).index();
        var dir   = "asc";
        var sc    = 0;
        var sw    = true;

        $(this).siblings().removeClass('sort-asc sort-desc');
        $(this).removeClass('sort-asc sort-desc').addClass('sort-asc');

        while (sw) {
            sw = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                ss = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                if (dir === "asc") {
                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                        ss = true;
                        break;
                    }
                } else if (dir === "desc") {
                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                        ss = true;
                        break;
                    }
                }
            }
            if (ss) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                sw = true;
                sc ++;
            } else {
                if ((sc === 0) && (dir === "asc")) {
                    dir = "desc";
                    $(this).removeClass('sort-asc sort-desc').addClass('sort-desc');
                    sw = true;
                }
            }
        }
    });

    /** WYSIWYG **/
    HTML.on('blur', '.xbweb-ui-wysiwyg textarea', function(){
        $(this).XBWebUIWYSIWYG_DOC().body.innerHTML = $(this)[0].value;
    }).on('click', '.xbweb-ui-wysiwyg .tool-bar button.cmd-code', function(){
        $(this).closest('.xbweb-ui-wysiwyg').addClass('code').find('textarea:first').focus();
    }).on('click', '.xbweb-ui-wysiwyg .tool-bar button.cmd-wysiwyg', function(){
        $(this).closest('.xbweb-ui-wysiwyg').removeClass('code').find('textarea:first').blur();
        $(this).closest('.xbweb-ui-wysiwyg').find('iframe:first').focus();
    }).on('click', '.xbweb-ui-wysiwyg .tool-bar button[data-command]', function(){
        var CMD = $(this).attr('data-command');
        var DOC = $(this).XBWebUIWYSIWYG_DOC();
        if (CMD === 'createLink') {

        } else if (CMD === 'insertImage') {

        } else { DOC.execCommand(CMD); }
        $(this).closest('.xbweb-ui-wysiwyg').find('iframe:first').focus();
    });

    /** REPEATERS **/
    HTML.on('click', XBWebUI.selector('.repeater[+ui+] button.add'), function(){
        if ($(this).hasClass('disabled')) return;
        var repeater = $(this).closest('.repeater');
        var sample   = repeater.find('div.sample:first');
        var item     = sample.clone().removeClass('sample').addClass('item');
        var itemMax  = repeater.XBWebUIIntAttr('max-items');
        var items    = repeater.find('.item').length;
        var itemLast = items;
        if (itemMax !== 0) if (items >= itemMax) return;
        if (repeater.data('last-item')) itemLast = XBWebUI.intval(repeater.data('last-item')) + 1;
        item.find('input[date-name], select[data-name], textarea[data-name]').each(function(){
            var name = $(this).attr('data-name').replace(/\[\+id\+]/g, '[' + itemLast + ']');
            $(this).attr('name', name).removeAttr('data-name');
        });
        item.appendTo(repeater);
        item.trigger('xbweb.addRepeaterItem');
        repeater.data('last-item', itemLast);
        if (itemMax !== 0) if ((items + 1) >= itemMax) {
            $(this).addClass('disabled');
            repeater.trigger('xbweb.repeaterReachBound');
        }
    }).on('click', XBWebUI.selector('.repeater[+ui+] button.delete'), function(){
        if ($(this).hasClass('disabled')) return;
        var repeater = $(this).closest('.repeater');
        var itemMax  = repeater.XBWebUIIntAttr('max-items');
        var items    = repeater.find('.item').length;
        $(this).closest('.item').trigger('xbweb.deleteRepeaterItem').remove();
        if (itemMax !== 0) if ((items - 1) < itemMax) {
            repeater.find('button.add').removeClass('disabled');
            repeater.trigger('xbweb.repeaterUnreachBound');
        }
    });

    /** BROWSERS **/
    HTML.on('click', XBWebUI.selector('.file-browser[+ui+] li a'), function(){
        $(this).parent().addClass('active').siblings().removeClass('active');
        return false;
    });

    /** MODAL WRAPPERS **/
    HTML.on('click', XBWebUI.selector('.modal-wrapper[+ui+] .close'), function(){
        $(this).closest('.modal-wrapper').find('form').each(function(){
            $(this).trigger('xbweb.formClose');
        });
        $(this).closest('.modal-wrapper').removeClass('active').html('').remove();
    }).on('click', XBWebUI.selector('.modal-wrapper[+ui+]'), function(){
        $(this).find('form').each(function(){
            $(this).trigger('xbweb.formClose');
        });
        $(this).removeClass('active').html('').remove();
    }).on('click', XBWebUI.selector('.modal-wrapper[+ui+] > *'), function(e){
        e.stopPropagation();
    });

    /** FINAL **/
    HTML.trigger('xbweb.loaded');
    $('body').XBWebInit();
});