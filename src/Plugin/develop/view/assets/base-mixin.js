function trim(str) { //删除左右两端的空格
    return str.replace(/(^\s*)|(\s*$)/g, "");
}

function ajax(api_url, json_data, success, error) {
    $.ajax({
        type: "POST",
        url: api_url,
        data: json_data,
        dataType: "json",
        success: function (data) {
            var use_time = Math.round((new Date().getTime() - start_time));
            if (data.code === 0 || !data.error) {
                api_log(cls, method, 'INFO', use_time, json_data, data);
                success && success(data);
            } else {
                api_log(cls, method, 'ERROR', use_time, json_data, data);
                error && error(data);
            }
        }
    });
}

function api_log(cls, func, tag, use_time, args, data) {
    delete args.csrf;
    var _log_func_dict = (typeof console !== "undefined" && typeof console.info === "function" && typeof console.warn === "function") ? {
        INFO: console.info.bind(console),
        ERROR: console.warn.bind(console)
    } : {};

    var f = _log_func_dict[tag];
    f && f(formatDateNow(), '[' + tag + '] ' + cls + '.' + func + '(' + use_time + 'ms)', 'args:', args, 'data:', data);
}

function formatDateNow() {
    var now = new Date(new Date().getTime());
    var year = now.getFullYear();
    var month = now.getMonth() + 1;
    var date = now.getDate();
    var hour = now.getHours();
    var minute = now.getMinutes();
    if (minute < 10) {
        minute = '0' + minute.toString();
    }
    var seconds = now.getSeconds();
    if (seconds < 10) {
        seconds = '0' + seconds.toString();
    }
    return year + "-" + month + "-" + date + " " + hour + ":" + minute + ":" + seconds;
}

(function ($) {
    $.fn.serializeJson = function () {
        var serializeObj = {};
        var array = this.serializeArray();
        $(array).each(function () {
            if (serializeObj[this.name]) {
                if ($.isArray(serializeObj[this.name])) {
                    serializeObj[this.name].push(this.value);
                } else {
                    serializeObj[this.name] = [serializeObj[this.name], this.value];
                }
            } else {
                serializeObj[this.name] = this.value;
            }
        });
        return serializeObj;
    };
})(jQuery);

const types = {
    copy: o => JSON.parse(JSON.stringify(o)),
    ThrottleApi: require('develop/api/ThrottleApi'),
    defaultPageInfo: (field, direction, num, page) => {
        direction = direction.toUpperCase();
        direction = direction == "DESC" ? "DESC" : "ASC";
        return {
            page: page || 1,
            num: num || 20,
            total: 0,
            sortOption: {
                field: field,
                direction: direction
            },
            _t: Date.parse(new Date()) / 1000 // 时间戳 主要用于 重新获取数据 并且限制每秒获取一次
        };
    },
};

/* base-mixin.js */
(function(){

const base_message = (vm, slug, content, autoClose, callback) => {
    // vm.$store.commit(types.SPIN_SHOW, false);

    slug = slug || 'success';
    autoClose = autoClose || 0;

    var args = {
        duration: slug == 'success' && autoClose == 0 ? 1.5 : autoClose,
        content: content || "操作成功",
        onClose: () => {
            typeof callback == 'function' && callback();
        }
    };

    setTimeout(() => {
        (vm.$Message[slug])(args);
    }, 200);
}

const base_notice = (vm, slug, title, content, autoClose, callback) => {
    // vm.$store.commit(types.SPIN_SHOW, false);
    slug = slug || 'success';
    autoClose = autoClose || 0;

    var args = {
        title: title || "成功",
        desc: content || "操作成功",
        duration: slug == 'success' && autoClose == 0 ? 4.5 : autoClose,
        onClose: () => {
            typeof callback == 'function' && callback();
        }
    };

    setTimeout(() => {
        (vm.$Notice[slug])(args);
    }, 200);
}

const base_modal = (vm, slug, title, content, okText, autoClose, callback) => {
    // vm.$store.commit(types.SPIN_SHOW, false);
    slug = slug || 'success';
    autoClose = autoClose || 0;
    var args = {
        title: title || "成功",
        content: content || "操作成功",
        okText: okText || "确定",
        onOk: () => {
            typeof callback == 'function' && callback();
        }
    };

    setTimeout(() => {
        if (autoClose > 0) {
            setTimeout(() => {
                vm.$Modal.remove();
                typeof callback == 'function' && callback();
            }, autoClose);
        }

        (vm.$Modal[slug])(args);
    }, 200);
}

Vue.mixin({
    computed: {
        ...Vuex.mapState(['default_pre_key', 'record_index', 'acc_seq_list', 'develop']),
    },
    methods: {
        _success(res, type, callback) {
            type = type || 'modal';
            type = type.toLowerCase();

            if (type == 'modal') {
                return base_modal(this, 'success', res.title || "成功", res.msg || "操作成功", "2s自动关闭", 2200, callback);
            } else if (type == 'msg' || type == 'message') {
                return base_message(this, 'success', res.msg || "操作成功", 2.2, callback);
            } else if (type == 'tips' || type == 'notice') {
                return base_notice(this, 'success', res.title || "成功", res.msg || "操作成功", 4.5, callback);
            }

            return base_modal(this, 'success', res.title || "成功", res.msg || "操作成功", "2s自动关闭", 2200, callback);
        },
        _error(res, type, callback) {
            console.error(res);

            type = type || 'modal';
            type = type.toLowerCase();

            if (type == 'modal') {
                return base_modal(this, 'error', res.title || "错误", res.msg || "操作失败", "确定", 0, callback);
            } else if (type == 'msg' || type == 'message') {
                return base_message(this, 'error', res.msg || "操作失败", 0, callback);
            } else if (type == 'tips' || type == 'notice') {
                return base_notice(this, 'success', res.title || "错误", res.msg || "操作失败", 0, callback);
            }

            return base_modal(this, 'error', res.title || "错误", res.msg || "操作失败", "确定", 0, callback);
        },
        _modal(slug, title, content, okText, autoClose, callback) {
            return base_modal(this, slug, title, content, okText, autoClose, callback);
        }
    }
});

/* base-mixin.js */
})();