define('develop/api/ThrottleApi', function(require, exports, module) {

    var global = typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {};


    (function (global, factory) {
        typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
            typeof define === 'function' && define.amd ? define(factory) :
                (global.ThrottleApi = factory());
    }(this, (function () { 'use strict';

        /*  */

        function ThrottleApiHelper(){
            var self = this;
            this.debug = true;

            var _h = location.hostname;
            var _s = 'https:' === document.location.protocol ? 'https' : 'http';
            var _l = (typeof console !== "undefined" && typeof console.log === "function") ? {
                DEBUG: typeof console.debug === "function" ? console.debug.bind(console) : console.log.bind(console),
                INFO: typeof console.info === "function" ? console.info.bind(console) : console.log.bind(console),
                WARN: typeof console.warn === "function" ? console.warn.bind(console) : console.log.bind(console),
                ERROR: typeof console.error === "function" ? console.error.bind(console) : console.log.bind(console),
            } : {};
            var _d = function(){
                var now = new Date(new Date().getTime());
                var year = now.getFullYear();
                var month = now.getMonth()+1;
                var date = now.getDate();
                var hour = now.getHours();
                var minute = now.getMinutes();
                if(minute < 10){ minute = '0' + minute.toString(); }
                var seconds = now.getSeconds();
                if(seconds < 10){ seconds = '0' + seconds.toString(); }
                return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+seconds;
            };

            this._ajax = function (host, path, args, success, failure, logHandler, logLevelHandler, fixArgs) {
                var self = this;
                args = args || {};
                fixArgs = fixArgs || {};
                logHandler = logHandler || function (logLevel, use_time, args, data) {
                    logLevel in _l && (_l[logLevel])(_d(), '[' + logLevel + '] ' + path + '(' + use_time + 'ms)', 'args:', args, 'data:', data)
                };
                logLevelHandler = logLevelHandler || function (res) {
                    return (res.code) ? ( res.code === 0 ? 'INFO' : 'ERROR') : (!res.error ? 'INFO' : 'ERROR');
                };

                var api_url = _s + "://" + host + path,
                    start_time = new Date().getTime();

                if( typeof CSRF_TOKEN !== "undefined" && CSRF_TOKEN ){
                    args.csrf = CSRF_TOKEN;
                }

                return $.ajax($.extend({}, {
                    type: host === location.hostname.toLowerCase() ? "POST" : "GET",
                    url: api_url,
                    data: args,
                    cache: false,
                    dataType: host === location.hostname.toLowerCase() ? "json" : "jsonp",
                    error: function(xhr, status, error){
                        typeof failure === 'function' && failure({
                            xhr: xhr, status: status, error: error
                        });
                    },
                    success: function (res) {
                        typeof logHandler === 'function' && logHandler(logLevelHandler(res), Math.round((new Date().getTime() - start_time)), args, res);
                        var code = typeof res.code !== 'undefined' ? parseInt(res.code) : -1
                        if (code === 0 || (code === -1 && !res.error)) {
                            typeof success === 'function' && success(res);
                        } else {
                            typeof failure === 'function' && failure(res);
                        }
                    }
                }, fixArgs));
            };

            this.apiIpList = function(args, success, failure, logHandler, logLevelHandler, fixArgs) {
                var _p = '/develop/throttle/apiIpList';args = args || {};
                logHandler = logHandler || function (t, u, a, d) {
                    self.debug && t in _l && (_l[t])(_d(),'['+t+'] '+_p+'('+u+'ms)','args:',a,'data:',d);
                };
                return !success && Promise ? new Promise(function(resolve, reject){
                    self._ajax(_h, _p, args, resolve, reject, logHandler, logLevelHandler, fixArgs);
                }) : self._ajax(_h, _p, args, success, failure, logHandler, logLevelHandler, fixArgs);
            };
            this.apiIpList_args = {pre_key: '', per_day: 0, ip: '', page: 1, num: 50};

            this.apiIpSetting = function(args, success, failure, logHandler, logLevelHandler, fixArgs) {
                var _p = '/develop/throttle/apiIpSetting';args = args || {};
                logHandler = logHandler || function (t, u, a, d) {
                    self.debug && t in _l && (_l[t])(_d(),'['+t+'] '+_p+'('+u+'ms)','args:',a,'data:',d);
                };
                return !success && Promise ? new Promise(function(resolve, reject){
                    self._ajax(_h, _p, args, resolve, reject, logHandler, logLevelHandler, fixArgs);
                }) : self._ajax(_h, _p, args, success, failure, logHandler, logLevelHandler, fixArgs);
            };
            this.apiIpSetting_args = {pre_key: ''};

            this.apiSaveIpSetting = function(args, success, failure, logHandler, logLevelHandler, fixArgs) {
                var _p = '/develop/throttle/apiSaveIpSetting';args = args || {};
                logHandler = logHandler || function (t, u, a, d) {
                    self.debug && t in _l && (_l[t])(_d(),'['+t+'] '+_p+'('+u+'ms)','args:',a,'data:',d);
                };
                return !success && Promise ? new Promise(function(resolve, reject){
                    self._ajax(_h, _p, args, resolve, reject, logHandler, logLevelHandler, fixArgs);
                }) : self._ajax(_h, _p, args, success, failure, logHandler, logLevelHandler, fixArgs);
            };
            this.apiSaveIpSetting_args = {pre_key: '', throttle: {}};
        }

        /*  */

        return new ThrottleApiHelper();
    })));

});