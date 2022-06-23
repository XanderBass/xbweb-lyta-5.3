class XBWebWYSIWYG {
    constructor(element) {
        let editor = this;

        this._isIE     = /*@cc_on!@*/false;
        this._element  = element;
        this._wrapper  = document.createElement('div');
        this._frame    = document.createElement('iframe');
        this._toolBars = {
            "format": this._tbFormat(),
            "code"  : this._tbCode()
        };

        this._element.classList.remove('wysiwyg');
        this._wrapper.classList.add('wysiwyg');
        this._element.parentNode.insertBefore(this._wrapper, this._element);

        this._frame.setAttribute('src', 'about:blank');
        this._frame.style.border = this._element.style.border;

        this._wrapper.appendChild(this._toolBars.code);
        this._wrapper.appendChild(this._element);
        this._wrapper.appendChild(this._toolBars.format);
        this._wrapper.appendChild(this._frame);

        let doc = this.doc;
        doc.open();
        doc.write('<html><head></head><body>'+ element.value +'</body></html>');
        doc.close();
        doc.designMode = 'on';

        doc.addEventListener('blur', function(){
            editor._element.value = doc.body.innerHTML;
        });

        this._element.addEventListener('blur', function(){
            doc.body.innerHTML = editor._element.value;
        });
    }

    _tbFormat() {
        let editor  = this;
        let toolBar = document.createElement('div');
        let classes = this._element.classList;
        let CmdList = [
            'bold', 'italic', 'strikethrough', 'underline', '',
            'subscript', 'superscript', '',
            'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'
        ];

        if (classes.contains('allowed-images') || classes.contains('allowed-links')) CmdList.push('');
        if (classes.contains('allowed-links'))  CmdList.push('createLink');
        if (classes.contains('allowed-images')) CmdList.push('insertImage');

        CmdList.forEach(function(item){
            let btn = document.createElement('button');
            if (item === '') {
                btn.classList.add('splitter');
            } else {
                btn.classList.add('cmd-' + item.toLowerCase());
                if (item === 'createLink') {

                } else if (item === 'createImage') {

                } else {
                    btn.addEventListener('click', function(){
                        editor.doc.execCommand(item);
                    });
                }
            }
            toolBar.appendChild(btn);
        });

        let btnCode = document.createElement('button');
        btnCode.classList.add('cmd-code');
        toolBar.appendChild(btnCode);
        toolBar.classList.add('tool-bar', 'format');

        btnCode.addEventListener('click', function(){
            editor._frame.blur();
            editor._element.focus();
            editor._wrapper.classList.add('mode-code');
        });

        return toolBar;
    }

    _tbCode() {
        let editor  = this;
        let toolBar = document.createElement('div');

        let btnCode = document.createElement('button');
        btnCode.classList.add('cmd-format');
        toolBar.appendChild(btnCode);
        toolBar.classList.add('tool-bar', 'code');

        btnCode.addEventListener('click', function(){
            editor._element.blur();
            editor._frame.focus();
            editor._wrapper.classList.remove('mode-code');
        });

        return toolBar;
    }

    get element() {
        return this._element;
    }

    get wrapper() {
        return this._wrapper;
    }

    get frame() {
        return this._frame;
    }

    get tooBars() {
        return this._toolBars;
    }

    get doc() {
        return this._isIE ? this._frame.contentWindow.Document : this._frame.contentDocument;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let head  = document.head || document.getElementsByTagName('head')[0];
    let style = document.createElement('style');
    let css   = `
.xbweb-ui div.wysiwyg:not(.mode-code) > textarea, div.xbweb-ui.wysiwyg:not(.mode-code) > textarea,
.xbweb-ui div.wysiwyg.mode-code > iframe, div.xbweb-ui.wysiwyg.mode-code > iframe,
.xbweb-ui div.wysiwyg:not(.mode-code) > div.tool-bar.code, div.xbweb-ui.wysiwyg:not(.mode-code) > div.tool-bar.code,
.xbweb-ui div.wysiwyg.mode-code > div.tool-bar.format, div.xbweb-ui.wysiwyg.mode-code > div.tool-bar.format {
    display: none;
}

.xbweb-ui div.wysiwyg > div.tool-bar, div.xbweb-ui.wysiwyg > div.tool-bar {
    background: ButtonFace;
}

.xbweb-ui div.wysiwyg > textarea, div.xbweb-ui.wysiwyg > textarea,
.xbweb-ui div.wysiwyg > iframe, div.xbweb-ui.wysiwyg > iframe {
    box-sizing: border-box; min-height: 240px; margin: 0;
    width: 100%;
    border: 1px solid ButtonFace;
}

.xbweb-ui div.wysiwyg.mode-code > textarea, div.xbweb-ui.wysiwyg.mode-code > textarea {
    resize: none;
    display: block;
}

.xbweb-ui div.wysiwyg > div.tool-bar button, div.xbweb-ui.wysiwyg > div.tool-bar button {
    height: 2em; width: 2em; min-width: 0; padding: 0; margin: .3em;
    line-height: 2em; text-align: center; vertical-align: middle;
}

.xbweb-ui div.wysiwyg > div.tool-bar button.splitter, div.xbweb-ui.wysiwyg > div.tool-bar button.splitter {
    width: 0;
    border-right: 1px solid rgba(0,0,0,.1);
}

.xbweb-ui div.wysiwyg .tool-bar button, div.xbweb-ui.wysiwyg .tool-bar button { position: relative; }
.xbweb-ui div.wysiwyg .tool-bar button:before, div.xbweb-ui.wysiwyg .tool-bar button:before {
    display: block; width: 100%; height: 100%;
    position: absolute; left: 0; top: 0;
    line-height: 2em; text-align: center;
}

.xbweb-ui div.wysiwyg button.cmd-bold:before, div.xbweb-ui.wysiwyg button.cmd-bold:before { content: 'B'; }
.xbweb-ui div.wysiwyg button.cmd-italic:before, div.xbweb-ui.wysiwyg button.cmd-italic:before { content: 'I'; }
.xbweb-ui div.wysiwyg button.cmd-strikethrough:before, div.xbweb-ui.wysiwyg button.cmd-strikethrough:before { content: 'S'; }
.xbweb-ui div.wysiwyg button.cmd-underline:before, div.xbweb-ui.wysiwyg button.cmd-underline:before { content: 'U'; }

`.trim();
    head.appendChild(style);
    style.setAttribute('title', 'XBWeb: repeater');
    style.setAttribute('type', 'text/css');
    style.appendChild(document.createTextNode(css));
});