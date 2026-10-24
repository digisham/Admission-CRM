# GD Goenka Admission CRM - Live Bi-directional Instant Synchronizer
# Keeps D:\SHAMSHER-PHOTO\ADMISSION-CRM-LEAD FOLLOW UP and C:\xampp\htdocs\ADMISSION-CRM-LEAD FOLLOW UP 100% in sync in real time!

$dirD = "D:\SHAMSHER-PHOTO\ADMISSION-CRM-LEAD FOLLOW UP"
$dirC = "C:\xampp\htdocs\ADMISSION-CRM-LEAD FOLLOW UP"

Write-Host "=========================================================="
Write-Host " GD GOENKA CRM - REAL-TIME INSTANT FILE SYNCHRONIZER " -ForegroundColor Cyan
Write-Host " Source D: $dirD"
Write-Host " Target C: $dirC"
Write-Host "=========================================================="

# Baseline sync on launch
try {
    robocopy "$dirD" "$dirC" /E /XO /XD .git .agents .agent scratch | Out-Null
    Write-Host "[OK] Baseline synchronized." -ForegroundColor Green
} catch {
    Write-Host "[WARN] Initial robocopy sync: $_" -ForegroundColor Yellow
}

$lastSync = @{}

function Sync-File($sourcePath, $fromDir, $toDir) {
    if ([string]::IsNullOrWhiteSpace($sourcePath)) { return }
    if ($sourcePath -like "*\.git\*" -or $sourcePath -like "*\.agents\*" -or $sourcePath -like "*\scratch\*" -or $sourcePath -like "*\storage\logs\*") {
        return
    }
    
    if (!$sourcePath.StartsWith($fromDir, [System.StringComparison]::OrdinalIgnoreCase)) {
        return
    }

    $rel = $sourcePath.Substring($fromDir.Length).TrimStart("\/")
    if ([string]::IsNullOrWhiteSpace($rel)) { return }

    $targetPath = Join-Path $toDir $rel
    
    $now = [DateTime]::UtcNow
    if ($lastSync.ContainsKey($rel) -and ($now - $lastSync[$rel]).TotalMilliseconds -lt 200) {
        return
    }
    $lastSync[$rel] = $now

    Start-Sleep -Milliseconds 50

    if (Test-Path -LiteralPath $sourcePath -PathType Leaf) {
        try {
            $destDir = Split-Path -Parent $targetPath
            if (!(Test-Path -LiteralPath $destDir)) {
                New-Item -ItemType Directory -Force -Path $destDir | Out-Null
            }
            Copy-Item -LiteralPath $sourcePath -Destination $targetPath -Force -ErrorAction Stop
            Write-Host "[SYNC $(Get-Date -Format 'HH:mm:ss')] $rel -> $(Split-Path -Leaf $toDir)" -ForegroundColor Green
        } catch {
            Start-Sleep -Milliseconds 150
            try {
                Copy-Item -LiteralPath $sourcePath -Destination $targetPath -Force -ErrorAction SilentlyContinue
                Write-Host "[SYNC $(Get-Date -Format 'HH:mm:ss')] (Retry) $rel -> $(Split-Path -Leaf $toDir)" -ForegroundColor Green
            } catch {}
        }
    } elseif (!(Test-Path -LiteralPath $sourcePath) -and (Test-Path -LiteralPath $targetPath -PathType Leaf)) {
        try {
            Remove-Item -LiteralPath $targetPath -Force -ErrorAction SilentlyContinue
            Write-Host "[SYNC $(Get-Date -Format 'HH:mm:ss')] Deleted $rel in $(Split-Path -Leaf $toDir)" -ForegroundColor Yellow
        } catch {}
    }
}

# Setup FileSystemWatcher for D -> C
$watcherD = New-Object System.IO.FileSystemWatcher
$watcherD.Path = $dirD
$watcherD.IncludeSubdirectories = $true
$watcherD.EnableRaisingEvents = $true
$watcherD.NotifyFilter = [System.IO.NotifyFilters]'FileName, LastWrite, Size, DirectoryName'

# Setup FileSystemWatcher for C -> D
$watcherC = New-Object System.IO.FileSystemWatcher
$watcherC.Path = $dirC
$watcherC.IncludeSubdirectories = $true
$watcherC.EnableRaisingEvents = $true
$watcherC.NotifyFilter = [System.IO.NotifyFilters]'FileName, LastWrite, Size, DirectoryName'

$actionD = {
    $path = $Event.SourceEventArgs.FullPath
    Sync-File $path $dirD $dirC
}

$actionC = {
    $path = $Event.SourceEventArgs.FullPath
    Sync-File $path $dirC $dirD
}

$actionDRename = {
    $path = $Event.SourceEventArgs.FullPath
    $oldPath = $Event.SourceEventArgs.OldFullPath
    if ($oldPath -and (Test-Path -LiteralPath (Join-Path $dirC ($oldPath.Substring($dirD.Length).TrimStart("\/"))))) {
        Remove-Item -LiteralPath (Join-Path $dirC ($oldPath.Substring($dirD.Length).TrimStart("\/"))) -Force -ErrorAction SilentlyContinue
    }
    Sync-File $path $dirD $dirC
}

$actionCRename = {
    $path = $Event.SourceEventArgs.FullPath
    $oldPath = $Event.SourceEventArgs.OldFullPath
    if ($oldPath -and (Test-Path -LiteralPath (Join-Path $dirD ($oldPath.Substring($dirC.Length).TrimStart("\/"))))) {
        Remove-Item -LiteralPath (Join-Path $dirD ($oldPath.Substring($dirC.Length).TrimStart("\/"))) -Force -ErrorAction SilentlyContinue
    }
    Sync-File $path $dirC $dirD
}

Register-ObjectEvent $watcherD 'Changed' -Action $actionD | Out-Null
Register-ObjectEvent $watcherD 'Created' -Action $actionD | Out-Null
Register-ObjectEvent $watcherD 'Deleted' -Action $actionD | Out-Null
Register-ObjectEvent $watcherD 'Renamed' -Action $actionDRename | Out-Null

Register-ObjectEvent $watcherC 'Changed' -Action $actionC | Out-Null
Register-ObjectEvent $watcherC 'Created' -Action $actionC | Out-Null
Register-ObjectEvent $watcherC 'Deleted' -Action $actionC | Out-Null
Register-ObjectEvent $watcherC 'Renamed' -Action $actionCRename | Out-Null

Write-Host "[ACTIVE] Real-time file sync is live. Any file you edit in VS Code will update instantly in browser!" -ForegroundColor Cyan

# Continuous loop with 1-second pulse that also sweeps recently modified files
while ($true) {
    try {
        # Fast sweep: check if any files in D were modified in last 3 seconds
        $recentThreshold = (Get-Date).AddSeconds(-3)
        $changedInD = Get-ChildItem -Path $dirD -Recurse -File -Exclude "*.log" | Where-Object { 
            $_.FullName -notmatch "\\(\.git|\.agents|\.agent|scratch|storage\\logs)\\" -and 
            $_.LastWriteTime -gt $recentThreshold 
        }

        foreach ($file in $changedInD) {
            $rel = $file.FullName.Substring($dirD.Length).TrimStart("\/")
            $targetFile = Join-Path $dirC $rel
            if (!(Test-Path -LiteralPath $targetFile) -or (Get-Item -LiteralPath $targetFile).LastWriteTime -ne $file.LastWriteTime) {
                $targetParent = Split-Path -Parent $targetFile
                if (!(Test-Path -LiteralPath $targetParent)) {
                    New-Item -ItemType Directory -Force -Path $targetParent | Out-Null
                }
                Copy-Item -LiteralPath $file.FullName -Destination $targetFile -Force -ErrorAction SilentlyContinue
                Write-Host "[PULSE SYNC $(Get-Date -Format 'HH:mm:ss')] $rel" -ForegroundColor Green
            }
        }
    } catch {}

    Start-Sleep -Milliseconds 800
}
