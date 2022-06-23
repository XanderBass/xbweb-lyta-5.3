class XBWebTable {
    constructor(element) {
        let table = this;

        this._element = element;
        this._action  = false;

        if (this._element.hasAttribute('data-action')) this._action = this._element.getAttribute('data-action');

        this._element.addEventListener('click', function(event){
            let me = event.target;
            if (me.matches('thead th') || me.matches('thead th > span')) {
                if (me.matches('thead th > span')) me = me.parentElement;
                if (me.classList.contains('sortable')) table.sort(me);
                return false;
            }
            if (me.matches('tbody td')) {
                if (!me.classList.contains('actions')) {
                    let row = me.parentElement;
                    let ctd = row.querySelector('.checker input[type="hidden"]');
                    if (ctd.value === '1') {
                        ctd.value = 0;
                    } else {
                        ctd.value = 1;
                    }
                }
            }
        });
    }

    get element() {
        return this._element;
    }

    sort(th) {
        let all   = Array.prototype.slice.call(th.parentElement.children);
        let index = all.indexOf(th);
        let dir   = th.classList.contains('sort-asc') ? 'desc' : 'asc';
        let mult  = (dir === 'asc') ? 1 : -1;
        let body  = this._element.querySelector('tbody');
        let rows  = body.querySelectorAll('tr');
        let nrows = Array.from(rows);
        all.forEach(function(cell){
            cell.classList.remove('sort-asc', 'sort-desc');
        });
        nrows.sort(function(RA, RB){
            const CA = RA.querySelectorAll('td')[index].innerHTML;
            const CB = RB.querySelectorAll('td')[index].innerHTML;
            switch (true) {
                case CA > CB  : return mult;
                case CA < CB  : return -1 * mult;
                case CA === CB: return 0;
            }
        });
        body.innerHTML = '';
        nrows.forEach(function(row){
            body.appendChild(row);
        });
        th.classList.add('sort-' + dir);
        return this;
    }
}