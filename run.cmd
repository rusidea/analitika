@echo off
goto begin

:usage
echo Usage: %~n0
echo.
echo Starts DokuWiki on a Stick (http://www.dokuwiki.org/dokuwiki_on_a_stick)
echo and waits for user to press a key to stop.
goto end

:begin
if not "%1"=="" goto usage
cd ipfs
del %HOMEDRIVE%%HOMEPATH%\.ipfs\repo.lock
echo %HOMEDRIVE%%HOMEPATH%\.ipfs\repo.lock
start "IPFS" /B ipfs.exe daemon --init=true
echo IPFS started...
echo.
cd ..\server
start "Apache server" /B mapache.exe
echo Analitika started...
echo.

:runbrowser
echo Your web browser will now open at http://localhost:8800
echo.
start http://localhost:8800/

:wait
echo STOP Analitika BY PRESSING ANY KEY
echo.
echo Logging ipfs below:
pause

:stop
ApacheKill.exe
echo ... Analitika stopped.
echo You can close this window now.

:end
