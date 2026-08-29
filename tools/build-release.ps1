param(
    [Parameter(Mandatory = $true)][string]$OutputPath,
    [Parameter(Mandatory = $true)][string]$SigningKeyPath,
    [Parameter(Mandatory = $false)][string]$SourceArtifactPath = '',
    [string]$NodePath = 'node'
)

$ErrorActionPreference = 'Stop'
$pluginRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$output = [System.IO.Path]::GetFullPath($OutputPath)
$pluginBoundary = $pluginRoot + [System.IO.Path]::DirectorySeparatorChar
if ($output.StartsWith($pluginBoundary, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'The release archive must be written outside the plugin source directory.'
}
if (Test-Path -LiteralPath $output) {
    throw "Output already exists: $output"
}
if (-not (Test-Path -LiteralPath (Split-Path -Parent $output) -PathType Container)) {
    throw "Output directory does not exist: $(Split-Path -Parent $output)"
}

$manifest = Get-Content -LiteralPath (Join-Path $pluginRoot 'release-manifest.json') -Raw | ConvertFrom-Json
if ($manifest.schema -ne 'slotera-release-manifest/v2') { throw 'Unsupported release manifest schema.' }

if ($SourceArtifactPath) {
    $sourceArtifact = [System.IO.Path]::GetFullPath($SourceArtifactPath)
    if (-not (Test-Path -LiteralPath $sourceArtifact -PathType Leaf)) { throw "Source artifact not found: $sourceArtifact" }
    $sourceHash = (Get-FileHash -LiteralPath $sourceArtifact -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($sourceHash -ne ([string]$manifest.lineage.previous_source.sha256).ToLowerInvariant()) {
        throw "Source artifact SHA-256 does not match release manifest. Expected $($manifest.lineage.previous_source.sha256), got $sourceHash"
    }
}

$psVersion = $PSVersionTable.PSVersion.ToString()
$canonicalCommand = "powershell -NoProfile -ExecutionPolicy Bypass -File tools/build-release.ps1 -OutputPath `"$OutputPath`" -SigningKeyPath `"$SigningKeyPath`" -NodePath `"$NodePath`""
if ($SourceArtifactPath) { $canonicalCommand += " -SourceArtifactPath `"$SourceArtifactPath`"" }
$env:SLTR_BUILD_COMMAND = $canonicalCommand
$env:SLTR_BUILD_OUTPUT = [System.IO.Path]::GetFileName($output)
$env:SLTR_POWERSHELL_VERSION = $psVersion
$env:SLTR_SIGNING_STATUS = 'performed-by-build-release.ps1'
$env:SLTR_VCS_REQUIRED = '1'
if (-not $env:SOURCE_DATE_EPOCH) {
    throw 'SOURCE_DATE_EPOCH is required for a reproducible release build.'
}
$entryTimestamp = [DateTimeOffset]::FromUnixTimeSeconds([long]$env:SOURCE_DATE_EPOCH)

& $NodePath (Join-Path $PSScriptRoot 'build-rc.mjs') --output $output --source-date-epoch $env:SOURCE_DATE_EPOCH
if ($LASTEXITCODE -ne 0) { throw 'Canonical release archive build failed.' }

$archiveHash = (Get-FileHash -LiteralPath $output -Algorithm SHA256).Hash.ToLowerInvariant()
Write-Output "ARCHIVE=$output"
Write-Output "SHA256=$archiveHash"
$attestation = $output + '.attestation.json'
$signature = $output + '.attestation.sig'
$publicKey = $output + '.public.pem'
& $NodePath (Join-Path $PSScriptRoot 'release-attestation.mjs') sign $output ([System.IO.Path]::GetFullPath($SigningKeyPath)) $attestation $signature $publicKey
if ($LASTEXITCODE -ne 0) { throw 'Release attestation signing failed.' }

& $NodePath (Join-Path $PSScriptRoot 'release-attestation.mjs') verify $output $attestation $signature $publicKey
if ($LASTEXITCODE -ne 0) { throw 'Release attestation verification failed.' }
