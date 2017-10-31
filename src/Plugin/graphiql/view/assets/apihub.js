function ApiHub(opt) {
    this.debug = !!(opt && opt.debug);
    this.token = (opt && opt.token) ? opt.token : '';
    this.hostname = (opt && opt.hostname) ? opt.hostname : location.hostname;
    this.http_scheme = 'https:' === document.location.protocol ? 'https' : 'http';
    this._log_func = (typeof console !== "undefined" && typeof console.info === "function" && typeof console.warn === "function") ? {
        INFO: console.info.bind(console),
        ERROR: console.warn.bind(console)
    } : {};
}
ApiHub.prototype.api_ajax = function (host, path, args, success, failure, logHandler, logLevelHandler, fixArgs) {
    var api_url = this.http_scheme + "://" + host + path,
        start_time = new Date().getTime();

    args = args || {};
    if( this.token ){
        args.token = this.token;
    }
    fixArgs = fixArgs || {};
    logHandler = logHandler || (function (logLevel, use_time, args, data) {
            (this.debug && logLevel in this._log_func) && (this._log_func[logLevel])(this.date('Y-m-d H:i:s'), '[' + logLevel + '] ' + path + '(' + use_time + 'ms)', 'args:', args, 'data:', data)
        }).bind(this);
    logLevelHandler = logLevelHandler || function (res) {
            return (res.code) ? ( res.code === 0 ? 'INFO' : 'ERROR') : (!res.error ? 'INFO' : 'ERROR');
        };

    return $.ajax($.extend({}, {
        type: host === location.hostname.toLowerCase() ? "POST" : "GET", url: api_url, data: args, cache: false,
        dataType: host === location.hostname.toLowerCase() ? "json" : "jsonp",
        success: function (res) {
            typeof logHandler === 'function' && logHandler(logLevelHandler(res), Math.round((new Date().getTime() - start_time)), args, res);
            if (typeof res.code !== 'undefined') {
                res.code = parseInt(res.code);
                if (res.code === 0) {
                    typeof success === 'function' && success(res);
                } else {
                    typeof failure === 'function' && failure(res);
                }
            } else {
                if (!res.error) {
                    typeof success === 'function' && success(res);
                } else {
                    typeof failure === 'function' && failure(res);
                }
            }
        }
    }, fixArgs));
};
