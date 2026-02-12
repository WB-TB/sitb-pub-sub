# Script to install SITB-CKG from GitHub for Windows
# Usage: .\install.ps1 [fresh|update]

param(
    [Parameter(Position=0)]
    [ValidateSet("fresh", "update")]
    [string]$InstallMode = "fresh"
)

# Configuration variables
$REPO_URL = "https://github.com/WB-TB/sitb-pub-sub.git"
$TARGET_DIR = "C:\sitb-ckg"
$NO_GIT = $false

# Check if PHP is installed (XAMPP first, then system PATH)
$xamppPhpPath = "C:\xampp\php\php.exe"
if (Test-Path $xamppPhpPath) {
    $phpPath = $xamppPhpPath
    Write-Host "Found PHP in XAMPP: $phpPath"
} else {
    $phpExec = Get-Command php -ErrorAction SilentlyContinue
    if (-not $phpExec) {
        Write-Host "   -> [ERROR] PHP is not installed. Please install XAMPP or PHP first." -ForegroundColor Red
        exit 1
    }
    $phpPath = $phpExec.Source
}

$phpVersion = & $phpPath -r "echo PHP_VERSION;"
Write-Host "Using PHP version: $phpVersion"

# Check if composer is installed (XAMPP first, then system PATH)
$xamppComposerPath = "C:\xampp\php\composer.bat"
if (Test-Path $xamppComposerPath) {
    $composerExec = $xamppComposerPath
    Write-Host "Found Composer in XAMPP: $composerExec"
} else {
    $composerExec = Get-Command composer -ErrorAction SilentlyContinue
    if (-not $composerExec) {
        Write-Host " + [WARNING]: Composer is not installed. Composer will be installed locally." -ForegroundColor Yellow
        Invoke-Expression "& $phpPath -r `"copy('https://getcomposer.org/installer', 'composer-setup.php');`""
        Invoke-Expression "& $phpPath composer-setup.php"
        Remove-Item composer-setup.php -ErrorAction SilentlyContinue
        $composerExec = ".\composer.phar"
    }
}

# Check if git is installed
$gitExec = Get-Command git -ErrorAction SilentlyContinue
if (-not $gitExec) {
    Write-Host "   -> [WARNING] git is not installed. Will download repository as zip file instead." -ForegroundColor Yellow
    $NO_GIT = $true
} elseif (Test-Path "$TARGET_DIR\version") {
    if (-not (Test-Path "$TARGET_DIR\.git")) {
        $NO_GIT = $true
    }
}

if ($InstallMode -eq "update") {
    Write-Host "Starting SITB-CKG update..."
} else {
    $InstallMode = "fresh"
    Write-Host "Starting installation of SITB-CKG ($InstallMode)..."
}

if ($InstallMode -eq "update") {
    if (-not $NO_GIT -and -not (Test-Path "$TARGET_DIR\.git")) {
        Write-Host "   -> [ERROR] Cannot update: Repository does not exist at $TARGET_DIR" -ForegroundColor Red
        exit 1
    }
    if ($NO_GIT -and -not (Test-Path $TARGET_DIR)) {
        Write-Host "   -> [ERROR] Cannot update: Directory does not exist at $TARGET_DIR" -ForegroundColor Red
        exit 1
    }

    if (-not (Test-Path "$TARGET_DIR\composer.json")) {
        Write-Host "   -> [ERROR] No composer.json found in the $TARGET_DIR." -ForegroundColor Red
        exit 1
    }
}

if ($NO_GIT) {
    if ($InstallMode -eq "update") {
        Write-Host "   -> Checking for version update..."
        $VERSION_URL = "https://raw.githubusercontent.com/WB-TB/sitb-pub-sub/refs/heads/main/version"
        
        try {
            $remoteVersion = (Invoke-WebRequest -Uri $VERSION_URL -UseBasicParsing).Content.Trim()
        } catch {
            Write-Host "   -> [ERROR] Failed to download version file" -ForegroundColor Red
            exit 1
        }
        
        # Read local version if exists
        if (Test-Path "$TARGET_DIR\version") {
            $localVersion = (Get-Content "$TARGET_DIR\version").Trim()
        } else {
            $localVersion = ""
        }
        
        # Compare versions
        if ($remoteVersion -eq $localVersion) {
            Write-Host "   -> [INFO] No version update available. Current version: $localVersion"
            exit 0
        } else {
            Write-Host "   -> [INFO] Version update available: $localVersion -> $remoteVersion"
        }
    }

    # Handle installation without git - download zip from REPO_URL and extract
    Write-Host " + Downloading repository as zip file..."
    
    # Create temp directory for download
    $TEMP_DIR = Join-Path $env:TEMP "sitb-ckg-temp-$(Get-Date -Format 'yyyyMMddHHmmss')"
    New-Item -ItemType Directory -Path $TEMP_DIR -Force | Out-Null
    
    # Download zip file from GitHub
    $ZIP_URL = "https://github.com/WB-TB/sitb-pub-sub/archive/refs/heads/main.zip"
    Write-Host "   -> Downloading from: $ZIP_URL"
    
    try {
        Invoke-WebRequest -Uri $ZIP_URL -OutFile "$TEMP_DIR\repo.zip" -UseBasicParsing
    } catch {
        Write-Host "   -> [ERROR] Failed to download repository zip file" -ForegroundColor Red
        Remove-Item $TEMP_DIR -Recurse -Force -ErrorAction SilentlyContinue
        exit 1
    }
    
    Write-Host "   -> Download completed successfully"
    
    # Extract zip file
    Write-Host "   -> Extracting zip file..."
    try {
        Expand-Archive -Path "$TEMP_DIR\repo.zip" -DestinationPath $TEMP_DIR -Force
    } catch {
        Write-Host "   -> [ERROR] Failed to extract zip file" -ForegroundColor Red
        Remove-Item $TEMP_DIR -Recurse -Force -ErrorAction SilentlyContinue
        exit 1
    }
    
    # Find the extracted directory
    $EXTRACTED_DIR = Get-ChildItem -Path $TEMP_DIR -Directory | Where-Object { $_.Name -like "sitb-pub-sub-*" } | Select-Object -FirstObject
    
    if (-not $EXTRACTED_DIR) {
        Write-Host "   -> [ERROR] Could not find extracted repository directory" -ForegroundColor Red
        Remove-Item $TEMP_DIR -Recurse -Force -ErrorAction SilentlyContinue
        exit 1
    }
    
    Write-Host "   -> Extraction completed successfully"
    
    # Copy/overwrite contents to TARGET_DIR
    Write-Host "   -> Copying files to $TARGET_DIR..."
    
    if ($InstallMode -eq "fresh") {
        # For fresh install, create directory if it doesn't exist
        if (-not (Test-Path $TARGET_DIR)) {
            New-Item -ItemType Directory -Path $TARGET_DIR -Force | Out-Null
        }
    }
    
    # Copy all files from extracted directory to TARGET_DIR, overwriting existing files
    Copy-Item -Path "$($EXTRACTED_DIR.FullName)\*" -Destination $TARGET_DIR -Recurse -Force
    
    Write-Host "   -> Files copied successfully"
    
    # Clean up temp directory
    Remove-Item $TEMP_DIR -Recurse -Force -ErrorAction SilentlyContinue
    Write-Host "   -> Temporary files cleaned up"
} else {
    # Clone or update the repository
    if (Test-Path "$TARGET_DIR\.git") {
        # Repository exists, pull latest changes
        Write-Host " + Repository already exists at $TARGET_DIR"
        Write-Host " + Updating repository from $REPO_URL..."
        Push-Location $TARGET_DIR
        try {
            & git fetch origin
            & git reset --hard origin/main
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Error: Failed to update repository" -ForegroundColor Red
                Pop-Location
                exit 1
            }
            Write-Host "   -> Repository updated successfully"
        } finally {
            Pop-Location
        }
    } else {
        # Repository doesn't exist, clone it
        Write-Host " + Cloning repository from $REPO_URL to $TARGET_DIR..."
        if (Test-Path $TARGET_DIR) {
            Write-Host "   -> Directory $TARGET_DIR exists but is not a git repository" -ForegroundColor Red
            Write-Host "   -> Please remove it or move it before running this script" -ForegroundColor Red
            exit 1
        }
        try {
            & git clone $REPO_URL $TARGET_DIR
            if ($LASTEXITCODE -ne 0) {
                Write-Host "   -> Error: Failed to clone repository" -ForegroundColor Red
                exit 1
            }
            Write-Host "   -> Repository cloned successfully"
        } catch {
            Write-Host "   -> Error: Failed to clone repository" -ForegroundColor Red
            exit 1
        }
    }
}

# Install Composer dependencies
Push-Location $TARGET_DIR
Write-Host " + Installing Composer dependencies..."
try {
    if ($composerExec -eq ".\composer.phar") {
        & $phpPath composer.phar update
    } elseif ($composerExec -like "*.bat") {
        # XAMPP composer.bat
        & $composerExec update
    } else {
        & composer update
    }
    if ($LASTEXITCODE -ne 0) {
        Write-Host "   -> [ERROR] Failed to install Composer dependencies" -ForegroundColor Red
        Pop-Location
        exit 1
    }
    Write-Host "   -> Composer dependencies installed successfully"
} finally {
    Pop-Location
}

Write-Host ""
Write-Host " ----------------------------------------"
Write-Host ""
if ($InstallMode -eq "fresh") {
    Write-Host "Installation completed successfully!" -ForegroundColor Green
} else {
    Write-Host "Update completed successfully!" -ForegroundColor Green
}
Write-Host ""
Write-Host "Repository installed at: $TARGET_DIR"
Write-Host ""
Write-Host "To update the repository in the future, run:"
if ($NO_GIT) {
    Write-Host "  powershell.exe -ExecutionPolicy Bypass -File `"$TARGET_DIR\scripts\install.ps1`" update"
} else {
    Write-Host "  cd $TARGET_DIR; git pull"
}
Write-Host ""
Write-Host "To run the consumer manually:"
Write-Host "  cd $TARGET_DIR"
Write-Host "  php consumer.php"
Write-Host ""
Write-Host "To run the producer manually:"
Write-Host "  cd $TARGET_DIR"
Write-Host "  php producer.php [pubsub|api]"
Write-Host ""

exit 0
