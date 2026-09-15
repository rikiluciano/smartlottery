import sqlite3

conn = sqlite3.connect('C:\\Users\\Ricardo\\OneDrive\\Escritorio\\Lottery\\database.sqlite')
c = conn.cursor()
c.execute("SELECT nombre_loteria FROM sorteos WHERE fecha='2026-09-05'")
print("Today:")
for row in c.fetchall():
    print(row[0])

c.execute("SELECT nombre_loteria FROM sorteos WHERE fecha='2026-09-04'")
print("\nYesterday:")
for row in c.fetchall():
    print(row[0])
