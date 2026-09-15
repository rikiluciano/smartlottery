import ftplib
import urllib.request
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('sec.php', 'rb') as f:
        ftp.storbinary('STOR sec.php', f)
    with open('local_htaccess_test.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
    
    try:
        html = urllib.request.urlopen('http://numerosrd.42web.io/lottery/resultados.php').read().decode('utf-8')
        if "BLOCKED_BY_SEC" in html:
            print("HTTP OK, Prepend WORKED!")
        else:
            print("HTTP OK, but prepend FAILED/IGNORED!")
    except Exception as e:
        print("HTTP Error:", e)
        
    with open('local_current_htaccess.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
    ftp.delete('sec.php')
    print("Reverted .htaccess")
except Exception as e:
    print("FTP Error:", e)
