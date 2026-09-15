import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    if 'debug_early.txt' in files:
        with open('local_debug.txt', 'wb') as f:
            ftp.retrbinary('RETR debug_early.txt', f.write)
        with open('local_debug.txt', 'r') as f:
            print("EARLY DEBUG:", f.read())
            
    if 'debug_pre_select.txt' in files:
        with open('local_debug_pre.txt', 'wb') as f:
            ftp.retrbinary('RETR debug_pre_select.txt', f.write)
        with open('local_debug_pre.txt', 'r') as f:
            print("PRE SELECT DEBUG:", f.read())
            
    if 'debug_todos.txt' in files:
        with open('local_debug_todos.txt', 'wb') as f:
            ftp.retrbinary('RETR debug_todos.txt', f.write)
        with open('local_debug_todos.txt', 'r') as f:
            print("TODOS DEBUG:", f.read())
            
    if 'debug_error.txt' in files:
        with open('local_debug_error.txt', 'wb') as f:
            ftp.retrbinary('RETR debug_error.txt', f.write)
        with open('local_debug_error.txt', 'r') as f:
            print("DEBUG ERROR:", f.read())
            
    if 'db_error.txt' in files:
        with open('local_db_error.txt', 'wb') as f:
            ftp.retrbinary('RETR db_error.txt', f.write)
        with open('local_db_error.txt', 'r') as f:
            print("DB ERROR:", f.read())
            
    for step in ['1', '2', '3']:
        name = f'debug_step{step}.txt'
        if name in files:
            with open(f'local_{name}', 'wb') as f:
                ftp.retrbinary(f'RETR {name}', f.write)
            with open(f'local_{name}', 'r') as f:
                print(f"STEP {step}:", f.read())
except Exception as e:
    print("Error:", e)
