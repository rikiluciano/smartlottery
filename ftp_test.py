import ftplib

host = "ftpupload.net"
user = "if0_40933868"
passwd = "WYDk3sCTGK8s0u"

try:
    ftp = ftplib.FTP(host)
    ftp.login(user, passwd)
    ftp.cwd('htdocs')
    print("Files in htdocs:")
    ftp.dir()
    ftp.cwd('lottery')
    print("\nFiles in htdocs/lottery:")
    ftp.dir()
    ftp.quit()
except Exception as e:
    print(e)
