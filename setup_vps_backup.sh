#!/bin/bash
mkdir -p ~/Lottery-Backup
cat > ~/Lottery-Backup/backup_project.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y-%m-%d)
BACKUP_DIR=$HOME/Lottery-Backup
mkdir -p $BACKUP_DIR/lottery_raw

echo "Fetching project files via FTP..."
# Use python script instead of lftp if lftp is not installed
cat > $BACKUP_DIR/fetch.py << 'PYEOF'
import ftplib
import os
try:
    ftp = ftplib.FTP(os.environ['LOTTERY_FTP_HOST'], os.environ['LOTTERY_FTP_USER'], os.environ['LOTTERY_FTP_PASS'])
    ftp.cwd('htdocs/lottery')
    for f in ftp.nlst():
        if f not in ['.', '..']:
            print("Downloading", f)
            with open(f, 'wb') as local_file:
                ftp.retrbinary(f'RETR {f}', local_file.write)
except Exception as e:
    print(e)
PYEOF

cd $BACKUP_DIR/lottery_raw
python3 $BACKUP_DIR/fetch.py

cd $BACKUP_DIR
tar -czf lottery_backup_$DATE.tar.gz lottery_raw
echo "Backup saved as lottery_backup_$DATE.tar.gz"
EOF

chmod +x ~/Lottery-Backup/backup_project.sh
(crontab -l 2>/dev/null | grep -v "backup_project.sh" ; echo "0 4 * * * ~/Lottery-Backup/backup_project.sh") | crontab -
echo "Cron job configured."
