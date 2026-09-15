import ftplib

try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    
    content = b"""<?php
// security.php
// Cebolla desactivada temporalmente para debug
function apply_onion_security($buffer) {
    return $buffer;
}
ob_start('apply_onion_security');
?>"""
    with open('local_sec.php', 'wb') as f:
        f.write(content)
        
    with open('local_sec.php', 'rb') as f:
        ftp.storbinary('STOR security.php', f)
        
    print("Security bypassed.")
except Exception as e:
    print("Error:", e)
