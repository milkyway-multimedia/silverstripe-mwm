var mwm = window.mwm || {};

mwm.utilities = (function (utilities, $) {
    var publicFunctions = {};

    publicFunctions['attachToEvent'] = function (element, event, callback, once) {
        var callbackFn;

        if ($ && $.hasOwnProperty('on')) {
            if(once)
                $(element).once(event, callback);
            else
                $(element).on(event, callback);
        }
        else if (element.addEventListener) {
            if(once) {
                callbackFn = function() {
                    callback.apply(this, arguments);
                    window.removeEventListener('click', callbackFn, false);
                };

                element.addEventListener(event, callbackFn, false);
            }
            else {
                element.addEventListener(event, callback, false);
            }
        }
        else if (element.attachEvent) {
            if(once) {
                callbackFn = function() {
                    callback.apply(this, arguments);
                    element.detachEvent(event, callbackFn);
                };

                element.attachEvent(event, callbackFn);
            }
            else {
                element.attachEvent(event, callback);
            }
        }
        else {
            var m = "on" + event;

            if (!element.hasOwnProperty(m))
                return false;

            if(once) {
                callbackFn = function() {
                    callback.apply(this, arguments);
                    element[m] = null;
                };

                element[m] = callbackFn;
            }
            else {
                element[m] = callback;
            }
        }
    };

    publicFunctions['triggerCustomEvent'] = function (element, event, eventArgs) {
        if ($ && $.hasOwnProperty('trigger'))
            $.trigger(event, eventArgs);
        else {
            var customEvent;

            if (window.CustomEvent) {
                customEvent = new CustomEvent(event, {detail: eventArgs});
            }
            else if (document.createEvent) {
                customEvent = document.createEvent("CustomEvent");
                customEvent.initCustomEvent(event, true, true, eventArgs);
            }

            element.dispatchEvent(customEvent);
        }
    };

    publicFunctions['requestViaAjax'] = function (url, type, successCb, errorCb, completeCb) {
        var requestType;

        if ($ && $.hasOwnProperty('ajax')) {
            requestType = type === "JSON" ? "GET" : type;

            $.ajax(url, {
                type:     requestType,
                success:  successCb,
                error:    errorCb,
                complete: completeCb
            });
        }
        else {
            requestType = type !== "POST" ? "GET" : "POST";

            var request = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
            request.open(requestType, url, true);

            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            if (requestType === "POST") {
                request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
            }

            if (successCb || errorCb || completeCb) {
                request.onreadystatechange = function () {
                    if(request.readyState == 4) {
                        var response = type == "JSON" && window.JSON ? JSON.parse(request.responseText) : request.responseText;

                        if (successCb && request.status >= 200 && request.status < 400) {
                            successCb(response, request);
                        }
                        else if (errorCb) {
                            errorCb(response, request);
                        }

                        if (completeCb) {
                            completeCb(response, request);
                        }
                    }
                }
            }

            request.send();
        }
    };

    publicFunctions['deferJsFiles'] = function (files, noAsyncOrDefer, cb) {
        files = files.constructor === Array ? files : [files];

        var element,
            filesInPage = document.getElementsByTagName("script"),
            included = false;

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

            (function(e, f) {
                utilities.attachToEvent(e, 'load', function () {
                    utilities.triggerCustomEvent(window, "mwm::loaded:js", [f]);
                    if (cb)
                        cb(f);
                });
            })(element, files[i]);
        }

        utilities.triggerCustomEvent(window, "mwm::injected:js", [files]);
    };

    publicFunctions['deferCssFiles'] = function (files, cb) {
        if(files !== null && typeof files !== 'object') {
            var newFiles = {};
            newFiles[files] = {};
            files = newFiles;
        }

        var element,
            filesInPage = document.getElementsByTagName("link"),
            included = false;

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

                (function(e, f) {
                    utilities.attachToEvent(e, 'load', function () {
                        utilities.triggerCustomEvent(window, "mwm::loaded:css", [f]);
                        if (cb)
                            cb(f);
                    });
                })(element, file);
            }
        }

        utilities.triggerCustomEvent(window, "mwm::injected:css", [files]);
    };

    for (var fn in publicFunctions) {
        if (publicFunctions.hasOwnProperty(fn) && !utilities.hasOwnProperty(fn)) {
            utilities[fn] = publicFunctions[fn];
        }
    }

    if(utilities.hasOwnProperty('loaded'))
        utilities.loaded = +new Date();

    return utilities;
}(mwm.utilities || {}, window.jQuery || window.zepto || {}));