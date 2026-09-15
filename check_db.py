import sqlite3
import pandas as pd

conn = sqlite3.connect('C:\\Users\\Ricardo\\OneDrive\\Escritorio\\Lottery\\database.sqlite')
df = pd.read_sql_query("SELECT * FROM sorteos WHERE fecha='2026-09-05'", conn)
print("Today:")
print(df['nombre_loteria'].tolist())

df2 = pd.read_sql_query("SELECT * FROM sorteos WHERE fecha='2026-09-04'", conn)
print("Yesterday:")
print(df2['nombre_loteria'].tolist())
