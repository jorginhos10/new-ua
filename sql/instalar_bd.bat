@echo off
setlocal

set MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe
set SQL_FILE=%~dp0instalar_bd.sql

if not exist "%MYSQL_EXE%" (
    echo No se encontro MySQL de XAMPP en %MYSQL_EXE%
    echo Instala XAMPP o ajusta la ruta en este archivo.
    exit /b 1
)

if not exist "%SQL_FILE%" (
    echo No se encontro el archivo de instalacion: %SQL_FILE%
    exit /b 1
)

echo Creando la base de datos new_ua...
"%MYSQL_EXE%" -u root < "%SQL_FILE%"

if errorlevel 1 (
    echo Error al crear la base de datos.
    exit /b 1
)

echo.
echo Base de datos montada correctamente.
echo Luego abre la app en tu navegador y accede con el usuario admin.
endlocal
