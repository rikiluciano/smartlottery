import ftplib
ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
ftp.cwd('htdocs/lottery')
with open('db_backup.json', 'wb') as local_file:
    ftp.retrbinary('RETR db_backup.json', local_file.write)
