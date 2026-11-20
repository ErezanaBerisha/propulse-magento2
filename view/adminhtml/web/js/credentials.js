define(['Solu1_ProPulse/js/qrcode-generator'], function () {
    'use strict';

    return function (config) {
        function markCopied(button) {
            var original = button.textContent;
            button.textContent = config.copied || 'Copied';
            window.setTimeout(function () {
                button.textContent = original;
            }, 1500);
        }

        function copyText(value, button) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(function () {
                    markCopied(button);
                });
                return;
            }

            var field = document.createElement('textarea');
            field.value = value;
            document.body.appendChild(field);
            field.select();
            document.execCommand('copy');
            document.body.removeChild(field);
            markCopied(button);
        }

        document.querySelectorAll('.propulse-copy').forEach(function (button) {
            button.addEventListener('click', function () {
                var id = button.getAttribute('data-target');
                var input = id ? document.getElementById(id) : null;
                if (!input || !input.value) {
                    return;
                }
                copyText(input.value, button);
            });
        });

        var mount = document.getElementById('propulse-qr');
        if (!mount || typeof window.propulseQrcode !== 'function') {
            return;
        }
        if (!config.username || !config.password || !config.shopUrl) {
            return;
        }

        var payload = [config.username, config.password, config.shopUrl, String(config.platformType)].join('|');
        var code = window.propulseQrcode(0, 'M');
        code.addData(payload);
        code.make();
        mount.innerHTML = code.createSvgTag({cellSize: 4, margin: 8});
    };
});
