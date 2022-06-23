class XBWebRepeater {
    constructor(element) {
        let repeater = this;

        this._element  = element;
        this._maxItems = 0;
        this._samples  = [];

        if (element.hasAttribute('data-max-items')) {
            try {
                this._maxItems = parseInt(element.getAttribute('data-max-items'));
            } catch (e) {
                this._maxItems = 0;
            }
        }

        this._element.addEventListener('click', function(event){
            let me = event.target;
            if (me.matches('button.add') || me.matches('a.add')) {
                event.preventDefault();
                let name = me.hasAttribute('data-name') ? me.getAttribute('data-name') : false;
                repeater.add(name);
                return false;
            }
            if (me.matches('.item button.delete') || me.matches('.item a.delete')) {
                event.preventDefault();
                let item = me.closest('.item');
                repeater.remove(item);
                return false;
            }
        });

        this._reindex();
    }

    _reindex() {
        this._items        = [];
        this._samples      = [];
        this._samplesNames = [];
        let _doc = this._element;
        let _ic  = _doc.children.length;
        for (let i = 0; i < _ic; i++) {
            let item = _doc.children[i];
            if (item.classList.contains('item')) {
                let index = this._items.length;
                item.querySelectorAll('input[data-name], select[data-name], textearea[data-name]').forEach(function(input){
                    let name = input.getAttribute('data-name').replace(/\+id\+/g, index.toString());
                    input.setAttribute('name', name);
                });
                this._items.push(item);
            }
            if (item.classList.contains('sample')) {
                let index = this._samples.length;
                if (item.hasAttribute('data-name')) {
                    let name = item.getAttribute('data-name');
                    this._samplesNames[name] = index;
                }
                this._samples[index] = item;
            }
        }
        return this;
    }

    get element() {
        return this._element;
    }

    get maxItems() {
        return this._maxItems;
    }

    get items() {
        return this._items;
    }

    get samples() {
        return this._samples;
    }

    get samplesNames() {
        return this._samplesNames;
    }

    add(name) {
        name = name || false;
        let omi = this._items.length;
        let si  = 0;
        if ((this._maxItems !== 0) && (omi >= this._maxItems)) return this;
        if (name !== false) if (name in this._samplesNames) si = this._samplesNames[name];
        let item = this._samples[si].cloneNode(true);
        item.classList.remove('sample');
        item.classList.add('item');
        if (item.dispatchEvent(new Event('xbweb-repeater-addItem'))) {
            this._element.appendChild(item);
            this._reindex();
            if ((this._maxItems !== 0) && (this._items.length >= this._maxItems) && (omi < this._maxItems)) {
                this._element.querySelectorAll('button.add').forEach(function(button_add){
                    button_add.classList.add('disabled');
                });
                this._element.dispatchEvent(new Event('xbweb-repeater-reachBound'));
            }
        }
        return this;
    }

    remove(item) {
        let omi = this._items.length;
        if (item.dispatchEvent(new Event('xbweb-repeater-removeItem'))) {
            item.remove();
            this._reindex();
            if ((this._maxItems !== 0) && (this._items.length < this._maxItems) && (omi === this._maxItems)) {
                this._element.querySelectorAll('button.add').forEach(function(button_add){
                    button_add.classList.remove('disabled');
                });
                this._element.dispatchEvent(new Event('xbweb-repeater-unreachBound'));
            }
        }
        return this;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let head  = document.head || document.getElementsByTagName('head')[0];
    let style = document.createElement('style');
    let css   = `
.xbweb-ui .repeater > .sample, .xbweb-ui.repeater > .sample {
    display: none;
}
`.trim();
    head.appendChild(style);
    style.setAttribute('title', 'XBWeb: repeater');
    style.setAttribute('type', 'text/css');
    style.appendChild(document.createTextNode(css));
});