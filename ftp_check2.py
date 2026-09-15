import ftplib

try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    
    if 'db_count.txt' in files:
        with open('local_count.txt', 'wb') as f:
            ftp.retrbinary('RETR db_count.txt', f.write)
            
        with open('local_count.txt', 'r') as f:
            print("DB COUNT:", f.read())
    else:
        print("db_count.txt not found. Meaning check_db.php was never executed.")
        
except Exception as e:
    print("Error:", e)
