import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    if 'db_wiped.flag' in files:
        print("db_wiped.flag EXISTS!")
    else:
        print("db_wiped.flag DOES NOT EXIST!")
except Exception as e:
    print("Error:", e)
