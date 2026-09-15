import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('local_config_db.php', 'wb') as f:
        ftp.retrbinary('RETR config_db.php', f.write)
    with open('local_config_db.php', 'r') as f:
        print(f.read())
except Exception as e:
    print("Error:", e)
