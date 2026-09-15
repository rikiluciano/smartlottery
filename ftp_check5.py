import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    def parse_list(line):
        if 'db_wiped.flag' in line or 'db_backup.json' in line:
            print(line)
    ftp.retrlines('LIST', parse_list)
except Exception as e:
    print("Error:", e)
