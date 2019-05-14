/**
 * Module for user provided API configurations
 * @module api/klutil
 */

function CacheFactory(cacheId, option) {
  var caches = {};

  if (caches[cacheId]) {
    return caches[cacheId];
  }

  function Cache(cacheId, options) {
    var size = 0,
      stats = extend({}, options),
      data = {},
      capacity = (options && options.capacity) || Number.MAX_VALUE,
      lruHash = {},
      freshEnd = null, //最近使用的元素
      staleEnd = null; //未使用时间最长的元素

    function refresh(entry) {
      if (entry != freshEnd) {
        if (!staleEnd) {
          staleEnd = entry;
        } else if (staleEnd == entry) {
          staleEnd = staleEnd.n;
        }
        link(entry.n, entry.p);
        link(entry, freshEnd);
      }
    }

    function link(nextEntry, prevEntry) {
      if (nextEntry != prevEntry) {
        if (nextEntry) {
          nextEntry.p = prevEntry;
        }
        if (prevEntry) {
          prevEntry.n = nextEntry;
        }
      }
    }

    var self = {
      /**
       * 往缓存中添加一个元素
       * 将最近使用的元素置为新增元素
       * @param key
       * @param value
       * @returns {*}
       */
      put: function (key, value) {
        var lruEntry = lruHash[key] || (lruHash[key] = {
          key: key
        });
        refresh(lruEntry);

        if (!(key in data)) {
          size++;
        }
        data[key] = value;
        if (size > capacity) {
          this.remove(staleEnd.key);
        }
        return value;
      },
      load: function (key, callback) {
        var item = self.get(key);
        if (item) {
          return item;
        }
        if (callback) {
          item = callback();
          self.put(key, item);
        }
        return item;
      },
      /**
       * 获取key指定值的value
       * 将最近使用的置置为该值
       * @param key
       * @returns {*}
       */
      get: function (key) {
        var lruEntry = lruHash[key];
        if (!lruEntry) {
          return;
        }
        refresh(lruEntry);
        return data[key];
      },
      remove: function (key) {
        var lruEntry = lruHash[key];
        if (!lruEntry) {
          return;
        }
        if (lruEntry == freshEnd) {
          freshEnd = lruEntry.p;
        }

        if (lruEntry == staleEnd) {
          staleEnd = staleEnd.n;
        }

        link(lruEntry.n, lruEntry.p);
        delete lruHash[key];
        delete data[key];
        size--;
      },
      removeAll: function () {
        data = {};
        size = 0;
        lruHash = {};
        freshEnd = staleEnd = null;
      },
      destroy: function () {
        data = null;
        stats = null;
        lruHash = null;
        delete caches[cacheId];
      },
      info: function () {
        return extend({}, stats, {
          size: size,
          data: data,
          lruHash: lruHash
        });
      }
    };
    return self;
  }

  caches[cacheId] = Cache(cacheId, option);

  CacheFactory.info = function () {
    var info = {};
    forEach(caches, function (cache, cacheId) {
      info[cacheId] = cache.info();
    });
    return info;
  };

  return caches[cacheId];
}

var slice = [].slice;
var toString = Object.prototype.toString();
var getPrototypeOf = Object.getPrototypeOf;

function isArrayLike(obj) {
  if (obj == null || isWindow(obj)) {
    return false;
  }

  var length = 'length' in Object(obj) && obj.length;
  if (obj.nodeType === NODE_TYPE_ELEMENT && length) {
    return true;
  }

  return isString(obj) || isArray(obj) || length === 0 || typeof length === 'number' && length > 0 && (length - 1) in obj;
}

function isWindow(obj) {
  return obj && obj.window === obj;
}


function isBlankObject(value) {
  return value !== null && typeof value === 'object' && !getPrototypeOf(value);
}

function forEach(obj, iterator, context) {
  var key, length;
  if (obj) {
    if (isFunction(obj)) {
      for (key in obj) {
        if (key != 'prototype' && key != 'length' && key != 'name' && (!obj.hasOwnProperty || obj.hasOwnProperty(key))) {
          iterator.call(context, obj[key], key, obj);
        }
      }
    } else if (isArray(obj) || isArrayLike(obj)) {
      var isPrimitive = typeof obj != 'object';
      for (key = 0, length = obj.length; key < length; key++) {
        if (isPrimitive || key in obj) {
          iterator.call(context, obj[key], key, obj);
        }
      }
    } else if (obj.forEach && obj.forEach !== forEach) {
      obj.forEach(iterator, context, obj);
    } else if (isBlankObject(obj)) {
      for (key in obj) {
        iterator.call(context, obj[key], key, obj);
      }
    } else if (typeof obj.hasOwnProperty === 'function') {
      for (key in obj) {
        iterator.call(context, obj[key], key, obj);
      }
    } else {
      for (key in obj) {
        if (hasOwnProperty.call(obj, key)) {
          iterator.call(context, obj[key], key, obj);
        }
      }
    }
  }
  return obj;
}

function extend(dst) {
  var objs = slice.call(arguments, 1);
  for (var i = 0, ii = objs.length; i < ii; ++i) {
    var obj = objs[i];
    if (!isObject(obj) && !isFunction(obj)) continue;
    var keys = Object.keys(obj);
    for (var j = 0, jj = keys.length; j < jj; j++) {
      var key = keys[j];
      var src = obj[key];

      if (isObject(src)) {
        if (isDate(src)) {
          dst[key] = new Date(src.valueOf());
        } else if (isRegExp(src)) {
          dst[key] = new RegExp(src);
        } else {
          if (!isObject(dst[key])) {
            dst[key] = isArray(src) ? [] : {};
          }
          extend(dst[key], [src]);
        }
      } else {
        dst[key] = src;
      }
    }
  }


}

var isArray = Array.isArray;

function isRegExp(value) {
  return toString.call(value) === '[object RegExp]';
}

function isDate(value) {
  return toString.call(value) === '[object Date]';
}

function isObject(value) {
  return value !== null && typeof value === 'object';
}

function isFunction(value) {
  return typeof value === 'function';
}

var valSeq = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];

function parseSequence(num) {
  var result = '';
  var digits = [];
  var base = valSeq.length;
  while (num > base - 1) {
    digits.push(num % base);
    num = Math.floor(num / base);
  }
  digits.push(num);
  result = "";
  for (var i = digits.length - 1; i >= 0; --i) {
    result = result + valSeq[digits[i]];
  }
  return result;
}

var util = {
  parseSequence: parseSequence,
  extend: extend,
  forEach: forEach,
  isBlankObject: isBlankObject,
  isWindow: isWindow,
  isArrayLike: isArrayLike,
  CacheFactory: CacheFactory,
  isArray: isArray,
  isRegExp: isRegExp,
  isDate: isDate,
  isObject: isObject,
  isFunction: isFunction
};


util.deepExtend = function (obj1, obj2) {
  if (Object.prototype.toString.call(obj1) === '[object Object]' && Object.prototype.toString.call(obj2) === '[object Object]') {
    for (prop2 in obj2) { //obj1无值,都有取obj2
      if (obj2.hasOwnProperty(prop2)) {
        if (!obj1[prop2]) {
          obj1[prop2] = obj2[prop2];
        } else { //递归赋值
          obj1[prop2] = util.deepExtend(obj1[prop2], obj2[prop2]);
        }
      }
    }
  } else if (Object.prototype.toString.call(obj1) === '[object Array]' && Object.prototype.toString.call(obj2) === '[object Array]') {
    // 两个都是数组，进行合并
    obj1 = obj2;
  } else { //其他情况，取obj2的值
    obj1 = obj2;
  }
  return obj1;
};

util.up2camel = function (str) {
  str.toLowerCase().replace(/_(\w)/g, function (all, letter) {
    return letter.toUpperCase();
  }).replace(/\b\w+\b/g, function (word) {
    return word.substring(0, 1).toUpperCase() + word.substring(1);
  });
  return str;
};

util.parseUid = function (clientId) {
  var id = clientId;
  var index = id.indexOf('_');
  if (index === -1) {
    return null;
  }
  id = id.substring(index + 1);
  index = id.indexOf('_');
  if (index === -1) {
    return null;
  }
  return {
    'uid': id.substring(0, index),
    'plat': id.substring(index + 1),
    'clientId': clientId
  };
};

util.empty = function (r) {
  r = r || {};
  return !r || JSON.stringify(r) == '{}' || JSON.stringify(r) == '[]';
};

util.replaceAll = function (replaceThis, withThis, baseStr) {
  withThis = withThis.replace(/\$/g, "$$$$");
  return baseStr.replace(new RegExp(replaceThis.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|<>\-\&])/g, "\\$&"), "g"), withThis);
};

//去除字符串全部的空白
util.replaceSpace = function (str) {
  return str.replace(/(\s*)/g, "");
};

//去除字符串两边的空白
util.trim = function (str) {
  str = str || '';
  return str.replace(/(^\s*)|(\s*$)/g, "");
};

//去除字符串两边的空白 并转为小写
util.trimlower = function (str) {
  str = str || '';
  str = str.replace(/(^\s*)|(\s*$)/g, "");
  return str.toLowerCase();
};

//只去除字符串左边空白
util.ltrim = function (str) {
  str = str || '';
  return str.replace(/(^\s*)/g, "");
};

//只去除字符串右边空白
util.rtrim = function (str) {
  str = str || '';
  return str.replace(/(\s*$)/g, "");
};

util.getChinese = function (strValue) {
  if (strValue != null && strValue != "") {
    var reg = /[\u4e00-\u9fa5]/g;
    return strValue.match(reg).join("");
  } else {
    return "";
  }

};
//去掉汉字
util.removeChinese = function (strValue) {
  if (strValue != null && strValue != "") {
    var reg = /[\u4e00-\u9fa5]/g;
    return strValue.replace(reg, "");
  } else {
    return "";
  }
};

util.uniqueId = function (idStrLen) {
  idStrLen = idStrLen || 8;
  // always start with a letter -- base 36 makes for a nice shortcut
  var idStr = (Math.floor((Math.random() * 25)) + 10).toString(36);
  // add a timestamp in milliseconds (base 36 again) as the base
  idStr += (new Date()).getTime().toString(36);
  // similar to above, complete the Id using random, alphanumeric characters
  do {
    idStr += (Math.floor((Math.random() * 35))).toString(36);
  } while (idStr.length < idStrLen);

  return (idStr);
};

/**
 * 生成一个范围内的随机数
 * nMin 最小值
 * nMax 最大值
 */
util.getRandomNum = function (nMin, nMax) {
  var Range = nMax - nMin;
  var Rand = Math.random();
  return parseInt(nMin + Math.round(Rand * Range));
};

util.intday2time = function (per_day) {
  per_day = parseInt(per_day);
  var month = parseInt(per_day / 100) % 100;
  var day = per_day % 100;
  var year = parseInt(per_day / 10000);
  var tt = new Date(year, month - 1, day, 0, 0, 0, 0);
  return Date.parse(tt) / 1000;
};

util.time = function () {
  var timestamp = new Date().getTime();
  return parseInt(timestamp / 1000);
};

util.strtotime = function (text, now) {
  //  discuss at: http://locutus.io/php/strtotime/
  // original by: Caio Ariede (http://caioariede.com)
  // improved by: Kevin van Zonneveld (http://kvz.io)
  // improved by: Caio Ariede (http://caioariede.com)
  // improved by: A. Matías Quezada (http://amatiasq.com)
  // improved by: preuter
  // improved by: Brett Zamir (http://brett-zamir.me)
  // improved by: Mirko Faber
  //    input by: David
  // bugfixed by: Wagner B. Soares
  // bugfixed by: Artur Tchernychev
  // bugfixed by: Stephan Bösch-Plepelits (http://github.com/plepe)
  //      note 1: Examples all have a fixed timestamp to prevent
  //      note 1: tests to fail because of variable time(zones)
  //   example 1: strtotime('+1 day', 1129633200)
  //   returns 1: 1129719600
  //   example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200)
  //   returns 2: 1130425202
  //   example 3: strtotime('last month', 1129633200)
  //   returns 3: 1127041200
  //   example 4: strtotime('2009-05-04 08:30:00 GMT')
  //   returns 4: 1241425800
  //   example 5: strtotime('2009-05-04 08:30:00+00')
  //   returns 5: 1241425800
  //   example 6: strtotime('2009-05-04 08:30:00+02:00')
  //   returns 6: 1241418600
  //   example 7: strtotime('2009-05-04T08:30:00Z')
  //   returns 7: 1241425800

  var parsed
  var match
  var today
  var year
  var date
  var days
  var ranges
  var len
  var times
  var regex
  var i
  var fail = false

  if (!text) {
    return fail
  }

  // Unecessary spaces
  text = text.replace(/^\s+|\s+$/g, '')
    .replace(/\s{2,}/g, ' ')
    .replace(/[\t\r\n]/g, '')
    .toLowerCase()

  // in contrast to php, js Date.parse function interprets:
  // dates given as yyyy-mm-dd as in timezone: UTC,
  // dates with "." or "-" as MDY instead of DMY
  // dates with two-digit years differently
  // etc...etc...
  // ...therefore we manually parse lots of common date formats
  var pattern = new RegExp([
    '^(\\d{1,4})',
    '([\\-\\.\\/:])',
    '(\\d{1,2})',
    '([\\-\\.\\/:])',
    '(\\d{1,4})',
    '(?:\\s(\\d{1,2}):(\\d{2})?:?(\\d{2})?)?',
    '(?:\\s([A-Z]+)?)?$'
  ].join(''))
  match = text.match(pattern)

  if (match && match[2] === match[4]) {
    if (match[1] > 1901) {
      switch (match[2]) {
        case '-':
          // YYYY-M-D
          if (match[3] > 12 || match[5] > 31) {
            return fail
          }

          return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // YYYY.M.D is not parsed by strtotime()
          return fail
        case '/':
          // YYYY/M/D
          if (match[3] > 12 || match[5] > 31) {
            return fail
          }

          return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
      }
    } else if (match[5] > 1901) {
      switch (match[2]) {
        case '-':
          // D-M-YYYY
          if (match[3] > 12 || match[1] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // D.M.YYYY
          if (match[3] > 12 || match[1] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '/':
          // M/D/YYYY
          if (match[1] > 12 || match[3] > 31) {
            return fail
          }

          return new Date(match[5], parseInt(match[1], 10) - 1, match[3],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
      }
    } else {
      switch (match[2]) {
        case '-':
          // YY-M-D
          if (match[3] > 12 || match[5] > 31 || (match[1] < 70 && match[1] > 38)) {
            return fail
          }

          year = match[1] >= 0 && match[1] <= 38 ? +match[1] + 2000 : match[1]
          return new Date(year, parseInt(match[3], 10) - 1, match[5],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case '.':
          // D.M.YY or H.MM.SS
          if (match[5] >= 70) {
            // D.M.YY
            if (match[3] > 12 || match[1] > 31) {
              return fail
            }

            return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
              match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
          }
          if (match[5] < 60 && !match[6]) {
            // H.MM.SS
            if (match[1] > 23 || match[3] > 59) {
              return fail
            }

            today = new Date()
            return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
              match[1] || 0, match[3] || 0, match[5] || 0, match[9] || 0) / 1000
          }

          // invalid format, cannot be parsed
          return fail
        case '/':
          // M/D/YY
          if (match[1] > 12 || match[3] > 31 || (match[5] < 70 && match[5] > 38)) {
            return fail
          }

          year = match[5] >= 0 && match[5] <= 38 ? +match[5] + 2000 : match[5]
          return new Date(year, parseInt(match[1], 10) - 1, match[3],
            match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
        case ':':
          // HH:MM:SS
          if (match[1] > 23 || match[3] > 59 || match[5] > 59) {
            return fail
          }

          today = new Date()
          return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
            match[1] || 0, match[3] || 0, match[5] || 0) / 1000
      }
    }
  }

  // other formats and "now" should be parsed by Date.parse()
  if (text === 'now') {
    return now === null || isNaN(now) ?
      new Date().getTime() / 1000 | 0 :
      now | 0
  }
  if (!isNaN(parsed = Date.parse(text))) {
    return parsed / 1000 | 0
  }
  // Browsers !== Chrome have problems parsing ISO 8601 date strings, as they do
  // not accept lower case characters, space, or shortened time zones.
  // Therefore, fix these problems and try again.
  // Examples:
  //   2015-04-15 20:33:59+02
  //   2015-04-15 20:33:59z
  //   2015-04-15t20:33:59+02:00
  pattern = new RegExp([
    '^([0-9]{4}-[0-9]{2}-[0-9]{2})',
    '[ t]',
    '([0-9]{2}:[0-9]{2}:[0-9]{2}(\\.[0-9]+)?)',
    '([\\+-][0-9]{2}(:[0-9]{2})?|z)'
  ].join(''))
  match = text.match(pattern)
  if (match) {
    // @todo: time zone information
    if (match[4] === 'z') {
      match[4] = 'Z'
    } else if (match[4].match(/^([+-][0-9]{2})$/)) {
      match[4] = match[4] + ':00'
    }

    if (!isNaN(parsed = Date.parse(match[1] + 'T' + match[2] + match[4]))) {
      return parsed / 1000 | 0
    }
  }

  date = now ? new Date(now * 1000) : new Date()
  days = {
    'sun': 0,
    'mon': 1,
    'tue': 2,
    'wed': 3,
    'thu': 4,
    'fri': 5,
    'sat': 6
  }
  ranges = {
    'yea': 'FullYear',
    'mon': 'Month',
    'day': 'Date',
    'hou': 'Hours',
    'min': 'Minutes',
    'sec': 'Seconds'
  }

  function lastNext(type, range, modifier) {
    var diff
    var day = days[range]

    if (typeof day !== 'undefined') {
      diff = day - date.getDay()

      if (diff === 0) {
        diff = 7 * modifier
      } else if (diff > 0 && type === 'last') {
        diff -= 7
      } else if (diff < 0 && type === 'next') {
        diff += 7
      }

      date.setDate(date.getDate() + diff)
    }
  }

  function process(val) {
    // @todo: Reconcile this with regex using \s, taking into account
    // browser issues with split and regexes
    var splt = val.split(' ')
    var type = splt[0]
    var range = splt[1].substring(0, 3)
    var typeIsNumber = /\d+/.test(type)
    var ago = splt[2] === 'ago'
    var num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1)

    if (typeIsNumber) {
      num *= parseInt(type, 10)
    }

    if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
      return date['set' + ranges[range]](date['get' + ranges[range]]() + num)
    }

    if (range === 'wee') {
      return date.setDate(date.getDate() + (num * 7))
    }

    if (type === 'next' || type === 'last') {
      lastNext(type, range, num)
    } else if (!typeIsNumber) {
      return false
    }

    return true
  }

  times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
    '|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
    '|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)'
  regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?'

  match = text.match(new RegExp(regex, 'gi'))
  if (!match) {
    return fail
  }

  for (i = 0, len = match.length; i < len; i++) {
    if (!process(match[i])) {
      return fail
    }
  }

  return parseInt(date.getTime() / 1000);
};

util.date = function (format, timestamp) {
  //  discuss at: http://locutus.io/php/date/
  // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
  // original by: gettimeofday
  //    parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
  // improved by: Kevin van Zonneveld (http://kvz.io)
  // improved by: MeEtc (http://yass.meetcweb.com)
  // improved by: Brad Touesnard
  // improved by: Tim Wiel
  // improved by: Bryan Elliott
  // improved by: David Randall
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Brett Zamir (http://brett-zamir.me)
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Thomas Beaucourt (http://www.webapp.fr)
  // improved by: JT
  // improved by: Theriault (https://github.com/Theriault)
  // improved by: Rafał Kukawski (http://blog.kukawski.pl)
  // improved by: Theriault (https://github.com/Theriault)
  //    input by: Brett Zamir (http://brett-zamir.me)
  //    input by: majak
  //    input by: Alex
  //    input by: Martin
  //    input by: Alex Wilson
  //    input by: Haravikk
  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
  // bugfixed by: majak
  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
  // bugfixed by: Brett Zamir (http://brett-zamir.me)
  // bugfixed by: omid (http://locutus.io/php/380:380#comment_137122)
  // bugfixed by: Chris (http://www.devotis.nl/)
  //      note 1: Uses global: locutus to store the default timezone
  //      note 1: Although the function potentially allows timezone info
  //      note 1: (see notes), it currently does not set
  //      note 1: per a timezone specified by date_default_timezone_set(). Implementers might use
  //      note 1: $locutus.currentTimezoneOffset and
  //      note 1: $locutus.currentTimezoneDST set by that function
  //      note 1: in order to adjust the dates in this function
  //      note 1: (or our other date functions!) accordingly
  //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400)
  //   returns 1: '07:09:40 m is month'
  //   example 2: date('F j, Y, g:i a', 1062462400)
  //   returns 2: 'September 2, 2003, 12:26 am'
  //   example 3: date('Y W o', 1062462400)
  //   returns 3: '2003 36 2003'
  //   example 4: var $x = date('Y m d', (new Date()).getTime() / 1000)
  //   example 4: $x = $x + ''
  //   example 4: var $result = $x.length // 2009 01 09
  //   returns 4: 10
  //   example 5: date('W', 1104534000)
  //   returns 5: '52'
  //   example 6: date('B t', 1104534000)
  //   returns 6: '999 31'
  //   example 7: date('W U', 1293750000.82); // 2010-12-31
  //   returns 7: '52 1293750000'
  //   example 8: date('W', 1293836400); // 2011-01-01
  //   returns 8: '52'
  //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
  //   returns 9: '52 2011-01-02'
  //        test: skip-1 skip-2 skip-5
  var now = new Date();
  var now_t = parseInt(now.getTime() / 1000);
  var ymd_t = parseInt(now.getFullYear() * 10000 + (now.getMonth() + 1) * 100 + now.getDate());

  if (typeof timestamp === "undefined" && typeof format === "number") {
    timestamp = parseInt(format);
    format = 'Y-m-d H:i:s';
  }
  format = format || 'Y-m-d H:i:s';
  timestamp = timestamp === undefined ? now_t : (timestamp instanceof Date ? parseInt((new Date(timestamp)).getTime() / 1000) : timestamp);

  if (typeof timestamp === "string") {
    timestamp = this.strtotime(timestamp);
  }
  if (timestamp >= this.intday2time(19800101) * 1000) {
    timestamp = parseInt(timestamp / 1000);
  }

  if (timestamp <= now_t / 60 && timestamp > ymd_t) {
    timestamp = timestamp * 60;
  } else if (timestamp <= ymd_t && timestamp >= 19700101) {
    timestamp = this.intday2time(ymd_t);
  } else if (timestamp <= now_t / 3600 && timestamp > now_t / (24 * 3600)) {
    timestamp = timestamp * 3600;
  } else if (timestamp <= now_t / (24 * 3600)) {
    timestamp = timestamp * 24 * 3600;
  }

  var jsdate, f;
  // Keep this here (works, but for code commented-out below for file size reasons)
  // var tal= [];
  var txtWords = [
    'Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur',
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  // trailing backslash -> (dropped)
  // a backslash followed by any character (including backslash) -> the character
  // empty string -> empty string
  var formatChr = /\\?(.?)/gi
  var formatChrCb = function (t, s) {
    return f[t] ? f[t]() : s
  }
  var _pad = function (n, c) {
    n = String(n)
    while (n.length < c) {
      n = '0' + n
    }
    return n
  }
  f = {
    // Day
    d: function () {
      // Day of month w/leading 0; 01..31
      return _pad(f.j(), 2)
    },
    D: function () {
      // Shorthand day name; Mon...Sun
      return f.l()
        .slice(0, 3)
    },
    j: function () {
      // Day of month; 1..31
      return jsdate.getDate()
    },
    l: function () {
      // Full day name; Monday...Sunday
      return txtWords[f.w()] + 'day'
    },
    N: function () {
      // ISO-8601 day of week; 1[Mon]..7[Sun]
      return f.w() || 7
    },
    S: function () {
      // Ordinal suffix for day of month; st, nd, rd, th
      var j = f.j()
      var i = j % 10
      if (i <= 3 && parseInt((j % 100) / 10, 10) === 1) {
        i = 0
      }
      return ['st', 'nd', 'rd'][i - 1] || 'th'
    },
    w: function () {
      // Day of week; 0[Sun]..6[Sat]
      return jsdate.getDay()
    },
    z: function () {
      // Day of year; 0..365
      var a = new Date(f.Y(), f.n() - 1, f.j())
      var b = new Date(f.Y(), 0, 1)
      return Math.round((a - b) / 864e5)
    },

    // Week
    W: function () {
      // ISO-8601 week number
      var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3)
      var b = new Date(a.getFullYear(), 0, 4)
      return _pad(1 + Math.round((a - b) / 864e5 / 7), 2)
    },

    // Month
    F: function () {
      // Full month name; January...December
      return txtWords[6 + f.n()]
    },
    m: function () {
      // Month w/leading 0; 01...12
      return _pad(f.n(), 2)
    },
    M: function () {
      // Shorthand month name; Jan...Dec
      return f.F()
        .slice(0, 3)
    },
    n: function () {
      // Month; 1...12
      return jsdate.getMonth() + 1
    },
    t: function () {
      // Days in month; 28...31
      return (new Date(f.Y(), f.n(), 0))
        .getDate()
    },

    // Year
    L: function () {
      // Is leap year?; 0 or 1
      var j = f.Y()
      return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0
    },
    o: function () {
      // ISO-8601 year
      var n = f.n()
      var W = f.W()
      var Y = f.Y()
      return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0)
    },
    Y: function () {
      // Full year; e.g. 1980...2010
      return jsdate.getFullYear()
    },
    y: function () {
      // Last two digits of year; 00...99
      return f.Y()
        .toString()
        .slice(-2)
    },

    // Time
    a: function () {
      // am or pm
      return jsdate.getHours() > 11 ? 'pm' : 'am'
    },
    A: function () {
      // AM or PM
      return f.a()
        .toUpperCase()
    },
    B: function () {
      // Swatch Internet time; 000..999
      var H = jsdate.getUTCHours() * 36e2
      // Hours
      var i = jsdate.getUTCMinutes() * 60
      // Minutes
      // Seconds
      var s = jsdate.getUTCSeconds()
      return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3)
    },
    g: function () {
      // 12-Hours; 1..12
      return f.G() % 12 || 12
    },
    G: function () {
      // 24-Hours; 0..23
      return jsdate.getHours()
    },
    h: function () {
      // 12-Hours w/leading 0; 01..12
      return _pad(f.g(), 2)
    },
    H: function () {
      // 24-Hours w/leading 0; 00..23
      return _pad(f.G(), 2)
    },
    i: function () {
      // Minutes w/leading 0; 00..59
      return _pad(jsdate.getMinutes(), 2)
    },
    s: function () {
      // Seconds w/leading 0; 00..59
      return _pad(jsdate.getSeconds(), 2)
    },
    u: function () {
      // Microseconds; 000000-999000
      return _pad(jsdate.getMilliseconds() * 1000, 6)
    },

    // Timezone
    e: function () {
      // Timezone identifier; e.g. Atlantic/Azores, ...
      // The following works, but requires inclusion of the very large
      // timezone_abbreviations_list() function.
      /*              return that.date_default_timezone_get();
       */
      var msg = 'Not supported (see source code of date() for timezone on how to add support)'
      throw new Error(msg)
    },
    I: function () {
      // DST observed?; 0 or 1
      // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
      // If they are not equal, then DST is observed.
      var a = new Date(f.Y(), 0)
      // Jan 1
      var c = Date.UTC(f.Y(), 0)
      // Jan 1 UTC
      var b = new Date(f.Y(), 6)
      // Jul 1
      // Jul 1 UTC
      var d = Date.UTC(f.Y(), 6)
      return ((a - c) !== (b - d)) ? 1 : 0
    },
    O: function () {
      // Difference to GMT in hour format; e.g. +0200
      var tzo = jsdate.getTimezoneOffset()
      var a = Math.abs(tzo)
      return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4)
    },
    P: function () {
      // Difference to GMT w/colon; e.g. +02:00
      var O = f.O()
      return (O.substr(0, 3) + ':' + O.substr(3, 2))
    },
    T: function () {
      // The following works, but requires inclusion of the very
      // large timezone_abbreviations_list() function.
      /*              var abbr, i, os, _default;
      if (!tal.length) {
        tal = that.timezone_abbreviations_list();
      }
      if ($locutus && $locutus.default_timezone) {
        _default = $locutus.default_timezone;
        for (abbr in tal) {
          for (i = 0; i < tal[abbr].length; i++) {
            if (tal[abbr][i].timezone_id === _default) {
              return abbr.toUpperCase();
            }
          }
        }
      }
      for (abbr in tal) {
        for (i = 0; i < tal[abbr].length; i++) {
          os = -jsdate.getTimezoneOffset() * 60;
          if (tal[abbr][i].offset === os) {
            return abbr.toUpperCase();
          }
        }
      }
      */
      return 'UTC'
    },
    Z: function () {
      // Timezone offset in seconds (-43200...50400)
      return -jsdate.getTimezoneOffset() * 60
    },

    // Full Date/Time
    c: function () {
      // ISO-8601 date.
      return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb)
    },
    r: function () {
      // RFC 2822
      return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb)
    },
    U: function () {
      // Seconds since UNIX epoch
      return jsdate / 1000 | 0
    }
  }

  var _date = function (format, timestamp) {
    jsdate = (timestamp === undefined ? new Date() // Not provided
      :
      (timestamp instanceof Date) ? new Date(timestamp) // JS Date()
      :
      new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
    )
    return format.replace(formatChr, formatChrCb)
  }

  return _date(format, timestamp);
};

util.htmlspecialchars = function (str) {
  str = str.replace(/&/g, '&amp;');
  str = str.replace(/</g, '&lt;');
  str = str.replace(/>/g, '&gt;');
  str = str.replace(/"/g, '&quot;');
  str = str.replace(/'/g, '&#039;');
  return str;
};

util.base64encode = function (input) {
  var _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
  input = String(input);
  var output = "";
  var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
  var i = 0;
  input = this._utf8_encode(input);
  while (i < input.length) {
    chr1 = input.charCodeAt(i++);
    chr2 = input.charCodeAt(i++);
    chr3 = input.charCodeAt(i++);
    enc1 = chr1 >> 2;
    enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
    enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
    enc4 = chr3 & 63;
    if (isNaN(chr2)) {
      enc3 = enc4 = 64
    } else if (isNaN(chr3)) {
      enc4 = 64
    }
    output = output + _keyStr.charAt(enc1) + _keyStr.charAt(enc2) + _keyStr.charAt(enc3) + _keyStr.charAt(enc4)
  }
  return output;
};

util.base64decode = function (input) {
  var _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
  var output = "";
  var chr1, chr2, chr3;
  var enc1, enc2, enc3, enc4;
  var i = 0;
  input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
  while (i < input.length) {
    enc1 = _keyStr.indexOf(input.charAt(i++));
    enc2 = _keyStr.indexOf(input.charAt(i++));
    enc3 = _keyStr.indexOf(input.charAt(i++));
    enc4 = _keyStr.indexOf(input.charAt(i++));
    chr1 = (enc1 << 2) | (enc2 >> 4);
    chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
    chr3 = ((enc3 & 3) << 6) | enc4;
    output = output + String.fromCharCode(chr1);
    if (enc3 != 64) {
      output = output + String.fromCharCode(chr2)
    }
    if (enc4 != 64) {
      output = output + String.fromCharCode(chr3)
    }
  }
  output = this._utf8_decode(output);
  return output;
};

util._utf8_encode = function (string) {
  string = string.replace(/\r\n/g, "\n");
  var utftext = "";
  for (var n = 0; n < string.length; n++) {
    var c = string.charCodeAt(n);
    if (c < 128) {
      utftext += String.fromCharCode(c)
    } else if ((c > 127) && (c < 2048)) {
      utftext += String.fromCharCode((c >> 6) | 192);
      utftext += String.fromCharCode((c & 63) | 128)
    } else {
      utftext += String.fromCharCode((c >> 12) | 224);
      utftext += String.fromCharCode(((c >> 6) & 63) | 128);
      utftext += String.fromCharCode((c & 63) | 128)
    }
  }
  return utftext
};

util._utf8_decode = function (utftext) {
  var string = "";
  var i = 0;
  var c = c1 = c2 = 0;
  while (i < utftext.length) {
    c = utftext.charCodeAt(i);
    if (c < 128) {
      string += String.fromCharCode(c);
      i++
    } else if ((c > 191) && (c < 224)) {
      c2 = utftext.charCodeAt(i + 1);
      string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
      i += 2
    } else {
      c2 = utftext.charCodeAt(i + 1);
      c3 = utftext.charCodeAt(i + 2);
      string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
      i += 3
    }
  }
  return string
};

util.uriDecode = function (uriComponent) {
  if (!uriComponent) {
    return uriComponent;
  }
  var ret;
  try {
    ret = decodeURIComponent(uriComponent);
  } catch (ex) {
    ret = uriComponent;
  }
  return ret;
};

util.md5 = function (string) {
  string = string || '';
  var rotateLeft = function (lValue, iShiftBits) {
    return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits))
  };
  var addUnsigned = function (lX, lY) {
    var lX4, lY4, lX8, lY8, lResult;
    lX8 = (lX & 0x80000000);
    lY8 = (lY & 0x80000000);
    lX4 = (lX & 0x40000000);
    lY4 = (lY & 0x40000000);
    lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
    if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
    if (lX4 | lY4) {
      if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
      else return (lResult ^ 0x40000000 ^ lX8 ^ lY8)
    } else {
      return (lResult ^ lX8 ^ lY8)
    }
  };
  var F = function (x, y, z) {
    return (x & y) | ((~x) & z)
  };
  var G = function (x, y, z) {
    return (x & z) | (y & (~z))
  };
  var H = function (x, y, z) {
    return (x ^ y ^ z)
  };
  var I = function (x, y, z) {
    return (y ^ (x | (~z)))
  };
  var FF = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b)
  };
  var GG = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b)
  };
  var HH = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b)
  };
  var II = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b)
  };
  var convertToWordArray = function (string) {
    var lWordCount;
    var lMessageLength = string.length;
    var lNumberOfWordsTempOne = lMessageLength + 8;
    var lNumberOfWordsTempTwo = (lNumberOfWordsTempOne - (lNumberOfWordsTempOne % 64)) / 64;
    var lNumberOfWords = (lNumberOfWordsTempTwo + 1) * 16;
    var lWordArray = Array(lNumberOfWords - 1);
    var lBytePosition = 0;
    var lByteCount = 0;
    while (lByteCount < lMessageLength) {
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
      lByteCount++
    }
    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
    lBytePosition = (lByteCount % 4) * 8;
    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
    lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
    lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
    return lWordArray
  };
  var wordToHex = function (lValue) {
    var WordToHexValue = "",
      WordToHexValueTemp = "",
      lByte, lCount;
    for (lCount = 0; lCount <= 3; lCount++) {
      lByte = (lValue >>> (lCount * 8)) & 255;
      WordToHexValueTemp = "0" + lByte.toString(16);
      WordToHexValue = WordToHexValue + WordToHexValueTemp.substr(WordToHexValueTemp.length - 2, 2)
    }
    return WordToHexValue
  };
  var uTF8Encode = function (string) {
    string = string.replace(/\x0d\x0a/g, "\x0a");
    var output = "";
    for (var n = 0; n < string.length; n++) {
      var c = string.charCodeAt(n);
      if (c < 128) {
        output += String.fromCharCode(c)
      } else if ((c > 127) && (c < 2048)) {
        output += String.fromCharCode((c >> 6) | 192);
        output += String.fromCharCode((c & 63) | 128)
      } else {
        output += String.fromCharCode((c >> 12) | 224);
        output += String.fromCharCode(((c >> 6) & 63) | 128);
        output += String.fromCharCode((c & 63) | 128)
      }
    }
    return output
  };

  var _md5 = function (string) {
    var x = Array();
    var k, AA, BB, CC, DD, a, b, c, d;
    var S11 = 7,
      S12 = 12,
      S13 = 17,
      S14 = 22;
    var S21 = 5,
      S22 = 9,
      S23 = 14,
      S24 = 20;
    var S31 = 4,
      S32 = 11,
      S33 = 16,
      S34 = 23;
    var S41 = 6,
      S42 = 10,
      S43 = 15,
      S44 = 21;
    string = uTF8Encode(string);
    x = convertToWordArray(string);
    a = 0x67452301;
    b = 0xEFCDAB89;
    c = 0x98BADCFE;
    d = 0x10325476;
    for (k = 0; k < x.length; k += 16) {
      AA = a;
      BB = b;
      CC = c;
      DD = d;
      a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
      d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
      c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
      b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
      a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
      d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
      c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
      b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
      a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
      d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
      c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
      b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
      a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
      d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
      c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
      b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
      a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
      d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
      c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
      b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
      a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
      d = GG(d, a, b, c, x[k + 10], S22, 0x2441453);
      c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
      b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
      a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
      d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
      c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
      b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
      a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
      d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
      c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
      b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
      a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
      d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
      c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
      b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
      a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
      d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
      c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
      b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
      a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
      d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
      c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
      b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
      a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
      d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
      c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
      b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
      a = II(a, b, c, d, x[k + 0], S41, 0xF4292244);
      d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
      c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
      b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
      a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
      d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
      c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
      b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
      a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
      d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
      c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
      b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
      a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
      d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
      c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
      b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
      a = addUnsigned(a, AA);
      b = addUnsigned(b, BB);
      c = addUnsigned(c, CC);
      d = addUnsigned(d, DD)
    }
    var tempValue = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);
    return tempValue.toLowerCase()
  };

  return _md5(string);
};

util.isIntNum = function (value) {
  var patrn = /^(-)?\d+(\.\d+)?$/;
  if (patrn.exec(value) == null || value == "") {
    return false
  } else {
    return true
  }
}

util.rangeHour = function (start, end) {
  start = util.isIntNum(start) ? parseInt(start) : util.strtotime(start);
  end = util.isIntNum(end) ? parseInt(end) : util.strtotime(end);

  var ret = [];
  for (var i = start; i < end; i += 3600) {
    ret.push(util.ceilToHour(i));
  }
  return ret;
}

util.ceilToHour = function (time_str) {
  var t = util.isIntNum(time_str) ? parseInt(time_str) : util.strtotime(time_str);
  var pre_hour = util.date('Y-m-d H:00:00', t);
  t = t > util.strtotime(pre_hour) ? util.strtotime(pre_hour) + 3600 : t;
  return util.date('Y-m-d H:00:00', t);
}

var _parseLssSlugCache = {};
util.parseLssSlug = function (slug) {
  if (_parseLssSlugCache[slug]) {
    return _parseLssSlugCache[slug];
  }
  slug = slug || '';
  var arr = slug.split('_');
  var typeArr = ['room', 'stream', 'parent', 'agent'];
  var tag = arr.splice(0, 1)[0] || '';
  var args = {
    countly_pre: tag
  };
  if (tag) {
    for (var i = 0; i < arr.length; i++) {
      var k = (typeArr[i] || 'type') + '_id';
      args[k] = parseInt(arr[i]);
    }
  }
  _parseLssSlugCache[slug] = args;
  return args;
}

util.range = function (start, stop, step) {
  if (typeof (stop) == 'undefined') {
    // one param defined
    stop = start;
    start = 0;
  }
  if (typeof (step) == 'undefined') {
    step = 1;
  }
  if ((step > 0 && start >= stop) || (step < 0 && start <= stop)) {
    return [];
  }
  var result = [];
  for (var i = start; step > 0 ? i < stop : i > stop; i += step) {
    result.push(i);
  }
  return result;
}

util.trim = function (str) {
  return str.replace(/(^\s*)|(\s*$)/g, "");
};

util.ltrim = function (str) {
  return str.replace(/(^\s*)/g, "");
};

util.rtrim = function (str) {
  return str.replace(/(\s*$)/g, "");
};

util.str_cmp = function (self, str) {
  return self == str;
};

util.stri_cmp = function (self, str) {
  return self.toLowerCase() == str.toLowerCase();
};

util.str_endswith = function (self, str) {
  if (str == null || str == "" || self.length == 0 || str.length > self.length) {
    return false;
  }
  return self.substring(self.length - str.length) == str;
};

util.str_startwith = function (self, str) {
  if (str == null || str == "" || self.length == 0 || str.length > self.length) {
    return false;
  }
  return self.substr(0, str.length) == str;
};

util.stri_endswith = function (self, str) {
  if (str == null || str == "" || self.length == 0 || str.length > self.length) {
    return false;
  }

  return self.substring(self.length - str.length).toLowerCase() == str.toLowerCase();
};
util.byte2size = function (num, in_tag, out_tag, dot) {
  in_tag = in_tag || '';
  out_tag = out_tag || '';
  dot = dot || 2;
  num = num * 1.0;
  out_tag = out_tag.toUpperCase();
  in_tag = in_tag.toUpperCase();
  dot = dot > 0 ? parseInt(dot) : 0;
  var tag_map = {
    K: 1024,
    M: 1024 * 1024,
    G: 1024 * 1024 * 1024,
    T: 1024 * 1024 * 1024 * 1024
  };

  if (in_tag && tag_map[in_tag]) {
    num = num * tag_map[in_tag]; //正确转换输入数据 去掉单位
  }
  var zero_str = '.';
  for (var i = 0; i < dot; i++) {
    zero_str = zero_str + '0';
  }

  if (num < 1024) {
    return num.toFixed(2).replace(zero_str, '');
  } else if (out_tag && tag_map[out_tag]) {
    var tmp = (num / tag_map[out_tag]).toFixed(dot).replace(zero_str, '');
    return tmp + out_tag; //使用设置的单位输出
  } else {
    for (var key of Object.keys(tag_map)) {
      var tmp = (num / tag_map[key]).toFixed(dot).replace(zero_str, '');
      if (tmp >= 1 && tmp < 1024) {
        return tmp + key;
      }
    }
    return util.byte2size(num, '', 'T', dot);
  }
}

util.stri_startwith = function (self, str) {
  if (str == null || str == "" || self.length == 0 || str.length > self.length) {
    return false;
  }
  return self.substr(0, str.length).toLowerCase() == str.toLowerCase();
};

util.fixurl = function (url, schema) { //复制对象方法
  schema = schema || 'http';
  if (util.stri_startwith(url, 'http://') || util.stri_startwith(url, 'https://')) {
    return url;
  }

  if (util.stri_startwith(url, '://')) {
    return schema + url;
  }

  if (util.stri_startwith(url, '//')) {
    return schema + ':' + url;
  }

  return schema + '://' + url;
};

util.cloneObj = function (obj1) { //复制对象方法
  return JSON.parse(JSON.stringify(obj1));
};

util.extendObj = function (obj1, obj2) { //扩展对象
  obj2 = obj2 || {};
  for (var key in obj2) {
    if (obj2.hasOwnProperty(key)) {
      obj1[key] = obj2[key];
    }
  }
  return obj1;
};

util.parseRtmpUrl = function (rtmpUrl) {
  var conf = {};
  var rtmpMode = /^rtmp\:\/\/(.*)\/([a-zA-Z0-9\-_]*)\/([a-zA-Z0-9\-_]*)(\?auth_key\=([a-zA-Z0-9\-_]*))?$/;
  if (!rtmpMode.test(rtmpUrl)) {
    return conf;
  }

  var arr = rtmpUrl.match(rtmpMode);
  conf.cname = arr[1];
  conf.app = arr[2];
  conf.key = '';
  conf.pk = arr[4] ? arr[4] : '';
  conf.stream = arr[3] + conf.pk;
  conf.addr = 'rtmp://' + conf.cname + '/' + conf.app;
  return conf;
};

util.parseAppAndStream = function (tag, url) {
  var n = url.indexOf("//");
  var tmpStr = '';
  if (tag === "rtmp" && url.indexOf("rtmp://") == 0) {
    tmpStr = url.substring(n + 2, url.length);
    tmpStr = tmpStr.substring(tmpStr.indexOf("/") + 1, tmpStr.length);
    return {
      lssApp: tmpStr.substring(0, tmpStr.indexOf("/")),
      lssStream: tmpStr.substring(tmpStr.indexOf("/") + 1, tmpStr.length)
    };
  } else if (tag === "hls" && url.indexOf(".m3u8") > 0) {
    tmpStr = url.substring(n + 2, url.length);
    tmpStr = tmpStr.substring(tmpStr.indexOf("/") + 1, tmpStr.length);
    return {
      lssApp: tmpStr.substring(0, tmpStr.indexOf("/")),
      lssStream: tmpStr.substring(tmpStr.indexOf("/") + 1, tmpStr.indexOf("."))
    };
  }
  return {};
};

util.inOf = function (arr, targetArr) {
  var res = true;
  arr.forEach(item => {
    if (targetArr.indexOf(item) < 0) {
      res = false;
    }
  });
  return res;
};

util.oneOf = function (ele, targetArr) {
  if (targetArr.indexOf(ele) >= 0) {
    return true;
  } else {
    return false;
  }
};

util.allOfObj = function (obj, func) {
  func =
    func ||
    function (k, t) {
      return t;
    };
  for (var key of Object.keys(obj)) {
    if (!func(key, obj[key])) {
      return false;
    }
  }
  return true;
};

util.oneOfObj = function (obj, func) {
  func =
    func ||
    function (k, t) {
      return t;
    };
  for (var key of Object.keys(obj)) {
    if (func(key, obj[key])) {
      return true;
    }
  }
  return false;
};

util.allOfArray = function (arr, func) {
  func =
    func ||
    function (i, t) {
      return t;
    };
  for (var idx of arr) {
    if (!func(idx, arr[key])) {
      return false;
    }
  }
  return true;
};

util.buildMap = function (arr) {
  var obj = {};
  for (var key of arr) {
    obj[key] = 1;
  }
  return obj;
}

util.oneOfArray = function (arr, func) {
  func =
    func ||
    function (i, t) {
      return t;
    };
  for (var idx of arr) {
    if (func(idx, arr[key])) {
      return true;
    }
  }
  return false;
};

util.startsWith = function (str, prefix) {
  return str.slice(0, prefix.length) === prefix;
};

util.endsWith = function (str, suffix) {
  return str.indexOf(suffix, str.length - suffix.length) !== -1;
};

var timer_type_map = {
  'minute': 60,
  '5minute': 300,
  'hour': 3600,
  'day': 86400,
};

util.count2time = function (timer_count, timer_type) {
  return timer_count * (timer_type_map[timer_type] || 60);
}

util.addCommas = function (num, dot_num, seq) {
  num = num || 0;
  dot_num = dot_num || 0;
  seq = seq || ',';

  var string = num + '';
  var arr = string.split('.');
  var num1 = arr[0];
  var num2 = '';
  if (dot_num > 0) {
    num2 = arr[1] ? '.' + arr[1] : '.';
    if (num2.length > dot_num + 1) {
      num2 = num2.substr(0, dot_num + 1);
    }
    while (num2.length < dot_num + 1) {
      num2 = num2 + "0";
    }
  }
  var reg = /(\d+)(\d{3})/;
  while (reg.test(num1)) {
    num1 = num1.replace(reg, '$1' + seq + '$2')
  }
  return num1 + num2;
}

util.time2sec = function (str, format) {
  format = format.replace("HH", "(\\d+)");
  format = format.replace("mm", "(\\d+)");
  format = format.replace("ss", "(\\d+)");
  var reg = new RegExp(format);
  var tmp = str.match(reg);
  if (tmp && tmp.length > 0) {
    var h = tmp[1] ? parseInt(tmp[1]) : 0;
    var m = tmp[2] ? parseInt(tmp[2]) : 0;
    var s = tmp[3] ? parseInt(tmp[3]) : 0;
    return h * 3600 + m * 60 + s;
  }
  return 0;
};

util.daystr2int = function (str) {
  if (!str) {
    return 0;
  }
  var tmp = str.split("-");
  var year = tmp[0] && parseInt(tmp[0]);
  var month = tmp[1] && parseInt(tmp[1]);
  var day = tmp[2] && parseInt(tmp[2]);
  return year * 10000 + month * 100 + day;
};

util.interval2str = function (c) {
  c = Math.abs(parseInt(c));
  if (c <= 0) {
    return "-";
  }
  var s = c % 60;
  c = (c - s) / 60;
  var m = c % 60;
  var h = (c - m) / 60;
  var rst = h > 0 ? h + "小时" : "";
  rst += m > 0 ? m + "分" : "";
  rst += s > 0 ? s + "秒" : "";
  return rst;
};

util.intday2time = function (per_day) {
  per_day = parseInt(per_day);
  var month = parseInt(per_day / 100) % 100;
  var day = per_day % 100;
  var year = parseInt(per_day / 10000);
  var tt = new Date(year, month - 1, day, 0, 0, 0, 0);
  return Date.parse(tt) / 1000;
};
if(typeof module != 'undefined'){
    module.exports = util;
}
