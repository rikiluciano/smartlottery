import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    print([f for f in files if not f.startswith('history_')])
except Exception as e:
    print("Error:", e)
