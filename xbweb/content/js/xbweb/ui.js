class XBWebUI {
    constructor(){
        const UI = this;
        document.body.addEventListener('click', function(e){
            let me = e.target;
            // Tabs
            if (UI.matches(me, UI.selector('.tabs[+ui+] a'))) {
                e.preventDefault();
                if (!me.classList.contains('disabled')) {
                    let href = me.getAttribute('href');
                    let tab  = document.querySelector(href);
                    UI.toggle(me, 'a');
                    UI.toggle(tab, '.tab');
                    tab.dispatchEvent(new Event('xbweb-showTab', {bubbles: true}));
                }
                return false;
            }
        });
    }

    intAttr(e, n, d) {
        d = d || 0;
        if (!e.hasAttribute('data-' + n)) return d;
        try {
            return parseInt(e.getAttribute('data-' + n));
        } catch (e) {
            return d;
        }
    }

    selector(s) {
        let a = s.replace(/\[\+ui\+]/g, '.xbweb-ui');
        let b = '.xbweb-ui ' + s.replace(/\[\+ui\+]/g, '');
        return b + ', ' + a;
    }

    loader() {
        let loader = document.createElement('div');
        loader.classList.add('modal-wrapper', 'loading', 'active');
        document.body.appendChild(loader);
        return loader;
    }

    matches(e, s) {
        let a = s.split(',');
        for (let m of a) if (e.matches(m.trim())) return true;
        return false;
    }

    toggle(e, sel) {
        sel = sel || false;
        this.siblings(e, sel).forEach(function(se){
            se.classList.remove('active');
        });
        e.classList.add('active');
        return e;
    }

    siblings(e, sel) {
        sel = sel || false;
        let siblings = [];
        if (!e.parentNode) return siblings;
        let sibling = e.parentNode.firstChild;
        while (sibling) {
            if (sibling.nodeType === 1 && sibling !== e) {
                if (sel !== false) {
                    if (sibling.matches(sel)) siblings.push(sibling);
                } else {
                    siblings.push(sibling);
                }
            }
            sibling = sibling.nextSibling;
        }
        return siblings;
    }
}