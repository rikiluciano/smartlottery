import ftplib
import urllib.request
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('test_obf.php', 'rb') as f:
        ftp.storbinary('STOR test_obf.php', f)
    
    html = urllib.request.urlopen('http://numerosrd.42web.io/lottery/test_obf.php').read().decode('utf-8')
    print("RAW HTML:")
    print(html[:200] + "...")
except Exception as e:
    print("Error:", e)
