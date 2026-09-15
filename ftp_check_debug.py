import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    if 'debug_todos.txt' in files:
        with open('local_debug.txt', 'wb') as f:
            ftp.retrbinary('RETR debug_todos.txt', f.write)
        with open('local_debug.txt', 'r') as f:
            print("DEBUG FILE CONTENT:", f.read())
    else:
        print("debug_todos.txt NOT FOUND yet.")
except Exception as e:
    print("Error:", e)
