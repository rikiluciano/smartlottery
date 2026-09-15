<?php
function obfuscate_html($buffer) {
    // If it's a small buffer or an error, don't obfuscate
    if (strlen($buffer) < 10) return $buffer;
    
    $hex = bin2hex($buffer);
    $script = "<script>
    var _0x1a2b = '$hex';
    var _0x3c4d = '';
    for (var _0x5e6f = 0; _0x5e6f < _0x1a2b.length; _0x5e6f += 2) {
        _0x3c4d += String.fromCharCode(parseInt(_0x1a2b.substr(_0x5e6f, 2), 16));
    }
    document.write(_0x3c4d);
    // Bloquear click derecho y F12
    document.addEventListener('contextmenu', e => e.preventDefault());
    document.addEventListener('keydown', e => {
        if(e.keyCode == 123 || (e.ctrlKey && e.shiftKey && e.keyCode == 73)) {
            e.preventDefault();
        }
    });
    </script>
    <noscript><h1>Ghost Page / Access Denied</h1></noscript>";
    return $script;
}
ob_start('obfuscate_html');
?>
<!DOCTYPE html>
<html>
<head><title>Test</title></head>
<body>
<h1>Hello World!</h1>
<p>This is a test of the onion layer.</p>
</body>
</html>
