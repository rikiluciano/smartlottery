import ftplib
import urllib.request
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('local_htaccess_test.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
    
    try:
        urllib.request.urlopen('http://numerosrd.42web.io/lottery/resultados.php')
        print("HTTP OK")
    except Exception as e:
        print("HTTP Error:", e)
        
    with open('local_current_htaccess.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
    print("Reverted .htaccess")
except Exception as e:
    print("FTP Error:", e)
