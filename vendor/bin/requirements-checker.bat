@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../symfony/requirements-checker/bin/requirements-checker
php "%BIN_TARGET%" %*
