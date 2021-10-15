Packeta = window.Packeta || {};
Packeta.ViewportHD = {
    element: null,
    originalValue: null,
    set: function() {
        if (!Packeta.ViewportHD.element) {
            Packeta.ViewportHD.element = document.querySelector("meta[name=viewport]");
            if (Packeta.ViewportHD.element) {
                Packeta.ViewportHD.originalValue = Packeta.ViewportHD.element.getAttribute("content");
            } else {
                Packeta.ViewportHD.originalValue = 'user-scalable=yes';
                Packeta.ViewportHD.element = document.createElement('meta');
                Packeta.ViewportHD.element.setAttribute("name", "viewport");
                (document.head || document.getElementsByTagName('head')[0]).appendChild(Packeta.ViewportHD.element);
            }
        }
        Packeta.ViewportHD.element.setAttribute('content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=yes');
    },
    restore: function() {
        if (Packeta.ViewportHD.originalValue !== null) {
            Packeta.ViewportHD.element.setAttribute('content', Packeta.ViewportHD.originalValue);
        }
    }
};

Packeta.UtilHD = {

    makeRequest: function(method, url, data, callback) {
        try {
            var xhr = new XMLHttpRequest();
            xhr.open(method, url);
            xhr.timeout = 5000;

            xhr.onload = function() {
                if (this.status >= 200 && this.status < 300) {
                    callback(xhr.response, false);
                } else {
                    callback({
                        status: this.status,
                        statusText: xhr.statusText
                    }, true);
                }
            };

            xhr.onerror = function() {
                callback({
                    status: this.status,
                    statusText: xhr.statusText
                }, true);
            };

            xhr.ontimeout = function() {
                callback({
                    status: this.status,
                    statusText: xhr.statusText
                }, true);
            };

            if (method === "POST" && data) {
                xhr.send(data);
            } else {
                xhr.send();
            }
        } catch (error) {
            callback({
                error: "XMLHttpRequest error: " + error
            }, true)
        }
    }
};

Packeta.WidgetHD = {
    baseUrl: 'https://widget-hd.packeta.com/',
    healthUrl: 'https://health-check-svc-widget-hd-prod.prod.packeta-com.codenow.com/api/healthcheck',
    versions: {
        backup: 'backup',
        v6: 'v6'
    },
    close: function() {},
    initIframe: function(apiKey, callback, opts, inElement) {
        Packeta.WidgetHD.close();

        if (opts === undefined) {
            opts = {};
        }
        if (!('version' in opts)) {
            opts.version = 3;
        }

        opts.apiKey = apiKey;

        var url = Packeta.WidgetHD.baseUrl + 'v6/#/?';

        if (opts.currentVersion === Packeta.WidgetHD.versions.backup) {
            Packeta.WidgetHD.baseUrl = 'https://widget3.packeta.com/v6/#/?';
            url = 'https://widget3.packeta.com/v6/#/?';
        }

        for (i in opts) {
            url += "&" + i + "=" + encodeURIComponent(opts[i]);
        }


        var inline = (typeof(inElement) != "undefined" && inElement !== null);
        var wrapper;
        if (inline) {
            wrapper = inElement;
        } else {
            Packeta.ViewportHD.set();
            wrapper = document.createElement("div");
            wrapper.setAttribute("style", "z-index: 999999; position: fixed; -webkit-backface-visibility: hidden; left: 0; top: 0; width: 100%; height: 100%; background: " + (opts.overlayColor || "rgba(0, 0, 0, 0.3)") + "; ");
            wrapper.addEventListener("click", function() {
                Packeta.WidgetHD.close();
            });

            // fix for some older browsers which fail to do 100% width of position:absolute inside position:fixed element
            setTimeout(
                function() {
                    var rect = iframe.getBoundingClientRect();
                    var width = ('width' in rect ? rect.width : rect.right - rect.left);
                    if (Math.round(width) < window.innerWidth - 10) { // 10px = side padding sum, just as a safety measure
                        iframe.style.width = window.innerWidth + "px";
                        iframe.style.height = window.innerHeight + "px";
                    }
                },
                0
            );
        }

        // always support Escape key immediatelly after the widget is displayed, even for inline
        wrapper.addEventListener("keyup", function(e) {
            if (e.keyCode == 27) {
                Packeta.WidgetHD.close();
            }
        });

        var iframe = document.createElement("iframe");
        if (inline) {
            iframe.setAttribute("style", "border: hidden; width: 100%; height: 100%; ");
        } else {
            iframe.setAttribute("style", "border: hidden; position: absolute; left: 0; top: 0; width: 100%; height: 100%; padding: 10px 5px; box-sizing: border-box; ");
        }
        iframe.setAttribute('id', "packeta-widget");
        iframe.setAttribute('sandbox', "allow-scripts allow-same-origin");
        iframe.setAttribute('allow', "geolocation");
        iframe.setAttribute('src', url);

        wrapper.appendChild(iframe);
        if (!inline) {
            document.body.appendChild(wrapper);
        }

        if (wrapper.getAttribute("tabindex") === null) {
            wrapper.setAttribute("tabindex", "-1"); // make it focusable
        }
        wrapper.focus();

        var receiver = function(e) {
            // check if this is message from the Packeta Widget
            try {
                var message = JSON.parse(e.data);
                if (!message.packetaWidgetMessage) return;
            } catch (err) {
                return;
            }

            Packeta.WidgetHD.close(message);
        };
        window.addEventListener('message', receiver);

        Packeta.WidgetHD.close = function(point) {
            window.removeEventListener('message', receiver);
            if (inline) {
                try {
                    iframe.parentNode.removeChild(iframe);
                } catch (err) {
                    // ignore
                }
            } else {
                document.body.removeChild(wrapper);
                Packeta.ViewportHD.restore();
            }
            callback(point || null);
            Packeta.WidgetHD.close = function() {};
        };
    },
    pick: function(apiKey, callback, opts, inElement) {

        if (opts === undefined) {
            opts = {};
        }

        function runV6() {
            opts.currentVersion = Packeta.WidgetHD.versions.v6;
            Packeta.WidgetHD.initIframe(apiKey, callback, opts, inElement);
        };

        function runBA() {
            opts.currentVersion = Packeta.WidgetHD.versions.backup;
            Packeta.WidgetHD.initIframe(apiKey, callback, opts, inElement);
        }

        Packeta.UtilHD.makeRequest("GET", Packeta.WidgetHD.healthUrl, null, function(data, error) {

            if (!error) {

                var result = data.toLocaleLowerCase() === "true";

                console.log("v6 widget health check result: " + result);

                if (result === true) {
                    console.log("starting v6");
                    runV6();
                } else {
                    console.log("starting backup environment");
                    runBA();
                }

            } else {
                console.log("v6 widget health check result: " + JSON.stringify(data));
                console.log("starting backup environment");
                runBA();
            }
        })



    }
};
