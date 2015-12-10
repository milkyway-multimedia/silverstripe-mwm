var mwm = window.mwm || {jquery: null};

mwm.jquery = (function (mwm, $) {
    var exports = mwm.jquery || null,
        globals = [], global;

    function isNodeListOrArray(item) {
        return item.constructor === Array || (typeof item.length != 'undefined' &&
            typeof item.item != 'undefined');
    }

    function setJquery() {
        if($ === null)
            $ = window.jQuery || window.Zepto || window.Sprint || null;
    }

    function deepExtend(out) {
        out = out || {};

        for (var i = 1; i < arguments.length; i++) {
            var obj = arguments[i];

            if (!obj)
                continue;

            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    if (typeof obj[key] === 'object')
                        deepExtend(out[key], obj[key]);
                    else
                        out[key] = obj[key];
                }
            }
        }

        return out;
    }

    function executeUsingJqueryOrDefault(method, args, fn) {
        setJquery();

        if($ && $.hasOwnProperty(method)) {
            return $[method].apply(this, args);
        }
        else {
            return fn();
        }
    }

    var standby = function (selected) {
        setJquery();

        if($)
            return $(selected);

        var that = this;

        if (!selected) {
            selected = window;
        }
        else if (typeof selected === 'string' || selected instanceof String) {
            selected = document.querySelectorAll(selected);
        }

        function executeUsingJqueryOrDefault(method, args, fn, element) {
            setJquery();

            if(!element)
                element = selected;

            if($ && $.hasOwnProperty(method)) {
                return $(element)[method].apply(this, args);
            }
            else {
                return fn(element);
            }
        }

        function addEventListener(event, callback, element) {
            if (element.addEventListener) {
                element.addEventListener(event, callback, false);
            }
            else if (element.attachEvent) {
                element.attachEvent(event, callback);
            }
            else {
                var onEvent = "on" + event;

                if (!element.hasOwnProperty(onEvent)) {
                    return false;
                }

                element[onEvent] = callback;
            }
        }

        this.ready = function(callback, element) {
            return executeUsingJqueryOrDefault('ready', [callback], function() {
                if (document.readyState != 'loading'){
                    callback();
                } else {
                    document.addEventListener('DOMContentLoaded', callback);
                }
            }, element);
        };

        this.on = function (event, callback, element) {
            return executeUsingJqueryOrDefault('on', [event, callback], function(element) {
                var events = event.split(' '), i=0;

                for(i;i<events.length;i++) {
                    addEventListener(events[i], callback, element);
                }

                return that;
            }, element);
        };

        this.off = function (event, callback, element) {
            return executeUsingJqueryOrDefault('off', [event, callback], function(element) {
                var events = event.split(' '), i=0;

                for(i;i<events.length;i++) {
                    element.removeEventListener(events[i], callback);
                }

                return that;
            }, element);
        };

        this.once = function (event, element, callback) {
            return executeUsingJqueryOrDefault('once', [event, callback], function(element) {
                return that.on(event, element, function(e) {
                    callback.apply(this, arguments);

                    if (element.removeEventListener) {
                        element.removeEventListener('click', callbackFn, false);
                    }
                    else if (element.attachEvent) {
                        element.detachEvent(event, callbackFn);
                    }
                    else if(element.hasOwnProperty("on" + event)) {
                        element["on" + event] = null;
                    }
                });
            }, element);
        };

        this.trigger = function (event, eventArgs, element) {
            return executeUsingJqueryOrDefault('trigger', [event, eventArgs], function(element) {
                var isNativeEvent = function (eventToCheck) {
                        return document.body && typeof document.body['on' + eventToCheck] !== 'undefined';
                    },
                    dispatchEvent = function (eventToDispatch, elementToTrigger) {
                        if (!elementToTrigger) {
                            elementToTrigger = window;
                        }

                        if (typeof eventToDispatch === 'string' || eventToDispatch instanceof String) {
                            elementToTrigger.fireEvent(eventToDispatch);
                        }
                        else {
                            elementToTrigger.dispatchEvent(eventToDispatch);
                        }
                    },
                    dispatch;

                if (isNativeEvent(event)) {
                    if (document.createEvent) {
                        dispatch = document.createEvent('HTMLEvents');
                        dispatch.initEvent(event, true, false);
                    }
                    else {
                        dispatch = 'on' + event;
                    }
                }
                else if (window.CustomEvent) {
                    dispatch = new CustomEvent(event, {detail: eventArgs});
                }
                else if (document.createEvent) {
                    dispatch = document.createEvent("CustomEvent");
                    dispatch.initCustomEvent(event, true, true, eventArgs);
                }

                if (isNodeListOrArray(element)) {
                    for (var i = 0; i < element.length; i++) {
                        dispatchEvent(dispatch, element[i]);
                    }
                }
                else {
                    dispatchEvent(dispatch, element);
                }

                return that;
            }, element);
        };

        return this;
    };

    globals.ajax = function(options) {
        var that = this;
        return executeUsingJqueryOrDefault('ajax', [options], function() {
            var type = options.hasOwnProperty('type') ? options.type : 'GET',
                request = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");

            request.open(type, options.url, true);

            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            if (type === "POST") {
                request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
            }

            if (options.hasOwnProperty('success') || options.hasOwnProperty('error') || options.hasOwnProperty('complete')) {
                request.onreadystatechange = function () {
                    if(this.readyState == 4) {
                        var response = this.responseText;

                        if(options.hasOwnProperty('dataType') && options.dataType === 'JSON') {
                            try {
                                response = window.JSON ? JSON.parse(response) : {};
                            } catch(ex) {
                                response = {};
                            }
                        }

                        if (options.hasOwnProperty('success') && this.status >= 200 && this.status < 400) {
                            options.success(response, this.status, this);
                        }
                        else if (options.hasOwnProperty('error')) {
                            options.error(response, this.status, this);
                        }

                        if (options.hasOwnProperty('complete')) {
                            options.complete(response, this.status, this);
                        }
                    }
                };
            }

            if (type === "POST") {
                var data = options.hasOwnProperty('data') ? options.data : {};
                request.send(data);
            }
            else {
                request.send();
            }

            return that;
        });
    };

    globals.extend = function(deep, out) {
        var originalArgs = [].slice.call(arguments),
            argsForJquery = [].slice.call(arguments);

        originalArgs.shift();

        if(deep !== true)
            argsForJquery.shift();

        return executeUsingJqueryOrDefault('extend', argsForJquery, function() {
            if(deep === true)
                return deepExtend.apply(this, originalArgs);
            else {
                out = out || {};

                for (var i = 1; i < originalArgs.length; i++) {
                    if (!originalArgs[i])
                        continue;

                    for (var key in originalArgs[i]) {
                        if (originalArgs[i].hasOwnProperty(key))
                            out[key] = originalArgs[i][key];
                    }
                }

                return out;
            }
        });
    };

    globals.param = function(data) {
        return executeUsingJqueryOrDefault('param', [data], function() {
            var query = [];

            for (var column in data) {
                if(!data.hasOwnProperty(column)) continue;
                query.push(encodeURIComponent(column) + "=" + encodeURIComponent(data[column]));
            }

            return query.join("&");
        });
    };

    for(global in globals) {
        if(!globals.hasOwnProperty(global)) continue;
        standby.prototype[global] = globals[global];
    }

    if (exports === null) {
        exports = function(selector) {
            if (this.constructor === standby)
                return this;
            else
                return new standby(selector);
        };

        for(global in globals) {
            if(!globals.hasOwnProperty(global)) continue;
            exports[global] = globals[global];
        }
    }

    return exports;
}(window.mwm || {}, window.jQuery || window.Zepto || window.Sprint || null));
var mwm = window.mwm || {utilities: {}};

mwm.utilities = (function (mwm, $) {
    var exports = mwm.utilities || {},
        publicFunctions = {};

    publicFunctions.setJquery = function() {
        if($ === null)
            $ = window.jQuery || window.zepto || window.Sprint || mwm._jquery;
    };

    publicFunctions.$ = function() {
        return $;
    };

    publicFunctions.attachToEvent = function (element, event, callback, once) {
        exports.setJquery();

        if ($) {
            if(once)
                $(element).once(event, callback);
            else
                $(element).on(event, callback);
        }
    };

    publicFunctions.triggerCustomEvent = function (element, event, eventArgs) {
        exports.setJquery();

        if ($)
            $(element).trigger(event, eventArgs);
    };

    publicFunctions.requestViaAjax = function (url, type, success, error, complete) {
        exports.setJquery();

        if ($) {
            $.ajax(url, {
                type:     type === 'JSON' ? 'GET' : type,
                dataType: type === 'JSON' ? type : null,
                success:  success,
                error:    error,
                complete: complete
            });
        }
    };

    publicFunctions.deferJsFiles = function (files, noAsyncOrDefer, cb) {
        files = files.constructor === Array ? files : [files];

        var element,
            filesInPage = document.getElementsByTagName("script"),
            included = false,
            attachEvents = function(element, file) {
                exports.attachToEvent(element, 'load', function () {
                    exports.triggerCustomEvent(window, "mwm::loaded:js", [file]);
                    if (cb)
                        cb(file);
                });
            };

        for (var i = 0; i < files.length; i++) {
            for (var j = filesInPage.length; j--;) {
                if (filesInPage[j].src == files[i]) {
                    included = true;
                    break;
                }
            }

            if (included) {
                included = false;
                continue;
            }

            element = document.createElement("script");
            element.src = files[i];

            if(!noAsyncOrDefer) {
                element.async = true;
                element.defer = true;
            }

            document.getElementsByTagName("body")[0].appendChild(element);

            (attachEvents)(element, files[i]);
        }

        exports.triggerCustomEvent(window, "mwm::injected:js", [files]);
    };

    publicFunctions.deferCssFiles = function (files, cb) {
        if(files !== null && typeof files !== 'object') {
            var newFiles = {};
            newFiles[files] = {};
            files = newFiles;
        }

        var element,
            filesInPage = document.getElementsByTagName("link"),
            included = false,
            attachEvents = function(element, file) {
                exports.attachToEvent(element, 'load', function () {
                    exports.triggerCustomEvent(window, "mwm::loaded:css", [file]);
                    if (cb)
                        cb(file);
                });
            };

        for (var file in files) {
            if (files.hasOwnProperty(file)) {
                for (var j = filesInPage.length; j--;) {
                    if (filesInPage[j].href == file) {
                        included = true;
                        break;
                    }
                }

                if(included) {
                    included = false;
                    continue;
                }

                element = document.createElement("link");
                element.href = file;
                element.rel = "stylesheet";
                element.type = "text/css";

                if (files[file].hasOwnProperty('media'))
                    element.media = files[file].media;

                document.getElementsByTagName("head")[0].appendChild(element);

                (attachEvents)(element, files[i]);
            }
        }

        exports.triggerCustomEvent(window, 'mwm::injected:css', [files]);
    };

    for (var fn in publicFunctions) {
        if (publicFunctions.hasOwnProperty(fn) && !exports.hasOwnProperty(fn)) {
            exports[fn] = publicFunctions[fn];
        }
    }

    return exports;
}(window.mwm || {}, window.jQuery || window.Zepto || window.Sprint || (window.mwm && mwm.jquery ? mwm.jquery : null)));