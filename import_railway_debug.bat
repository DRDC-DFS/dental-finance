@echo off
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -h hopper.proxy.rlwy.net -P 47946 -u root -pYpdRpbHZeIrDuMTJIJqVaczurlNOObsO railway < "C:\laragon\www\dental-finance\dental_finance_full.sql" 1> "C:\laragon\www\dental-finance\import_ok.txt" 2> "C:\laragon\www\dental-finance\import_err.txt"
pause