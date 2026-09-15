import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    for f in ftp.nlst():
        if f.startswith('debug_'):
            ftp.delete(f)
    print("Deleted old debugs")
except Exception as e:
    print("Error:", e)
