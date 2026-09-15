import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('local_current_htaccess.txt', 'wb') as f:
        ftp.retrbinary('RETR .htaccess', f.write)
except Exception as e:
    pass
