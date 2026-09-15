#!/bin/bash
DATE=$(date +%Y-%m-%d)
BACKUP_DIR=$HOME/Lottery-Backup
mkdir -p $BACKUP_DIR/lottery_raw

echo "Fetching project files via wget..."
cd $BACKUP_DIR
wget -m ftp://if0_40933868:WYDk3sCTGK8s0u@ftpupload.net/htdocs/lottery/ -P lottery_raw

tar -czf lottery_backup_$DATE.tar.gz lottery_raw
echo "Backup saved as lottery_backup_$DATE.tar.gz"
