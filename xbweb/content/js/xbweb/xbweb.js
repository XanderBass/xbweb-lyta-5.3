class XBWeb {
    constructor() {
        this._ENV = new XBWeb_Env();
        this._UI  = new XBWebUI();
    }

    get Env() { return this._ENV; }
    get UI()  { return this._UI; }

    intval(v) {
        try {
            return parseInt(v);
        } catch (e) {
            return 0;
        }
    }

    floatval(v) {
        try {
            return parseFloat(v);
        } catch (e) {
            return 0;
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    window.XBWeb = new XBWeb();
});