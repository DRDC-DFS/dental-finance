@echo off
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" -u root --databases dental_finance --routines --triggers --events > "C:\laragon\www\dental-finance\dental_finance_full.sql"
pause