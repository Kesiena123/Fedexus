$ErrorActionPreference = "Stop"

$checks = @()
$hasFailures = $false

function Add-Result {
    param(
        [string]$Name,
        [bool]$Passed,
        [string]$Detail
    )

    $script:checks += [PSCustomObject]@{
        Check = $Name
        Passed = $Passed
        Detail = $Detail
    }

    if (-not $Passed) {
        $script:hasFailures = $true
    }
}

function Test-Http {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Url,
        [int]$ExpectedStatus,
        [string]$Body = $null
    )

    try {
        $request = @{
            Uri = $Url
            Method = $Method
            UseBasicParsing = $true
            TimeoutSec = 12
        }

        if ($Method -in @("POST", "PUT", "PATCH") -and -not [string]::IsNullOrWhiteSpace($Body)) {
            $request["Body"] = $Body
            $request["ContentType"] = "application/json"
        }

        $response = Invoke-WebRequest @request
        Add-Result -Name $Name -Passed ($response.StatusCode -eq $ExpectedStatus) -Detail "status=$($response.StatusCode), expected=$ExpectedStatus"
    } catch {
        $status = $null
        if ($_.Exception.Response -and $_.Exception.Response.StatusCode) {
            $status = [int]$_.Exception.Response.StatusCode
        }

        if ($null -ne $status) {
            Add-Result -Name $Name -Passed ($status -eq $ExpectedStatus) -Detail "status=$status, expected=$ExpectedStatus"
        } else {
            Add-Result -Name $Name -Passed $false -Detail $_.Exception.Message
        }
    }
}

function Test-Port {
    param(
        [string]$Name,
        [string]$HostName,
        [int]$Port
    )

    try {
        $ok = (Test-NetConnection -ComputerName $HostName -Port $Port -WarningAction SilentlyContinue).TcpTestSucceeded
        Add-Result -Name $Name -Passed $ok -Detail "tcp=${HostName}:$Port"
    } catch {
        Add-Result -Name $Name -Passed $false -Detail $_.Exception.Message
    }
}

function Test-ProcessMatch {
    param(
        [string]$Name,
        [string]$Pattern
    )

    try {
        $matches = Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -and $_.CommandLine -match $Pattern }
        $count = @($matches).Count
        Add-Result -Name $Name -Passed ($count -gt 0) -Detail "matches=$count"
    } catch {
        Add-Result -Name $Name -Passed $false -Detail $_.Exception.Message
    }
}

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Test-Port -Name "Frontend dev server port" -HostName "127.0.0.1" -Port 3000
Test-Port -Name "Backend API port" -HostName "127.0.0.1" -Port 8000
Test-Port -Name "Reverb websocket port" -HostName "127.0.0.1" -Port 8080

Test-Http -Name "Frontend tracking page" -Method "GET" -Url "http://127.0.0.1:3000/tracking" -ExpectedStatus 200
Test-Http -Name "Frontend dashboard page" -Method "GET" -Url "http://127.0.0.1:3000/dashboard" -ExpectedStatus 200
Test-Http -Name "Backend login route method guard" -Method "GET" -Url "http://127.0.0.1:8000/api/auth/login" -ExpectedStatus 405
Test-Http -Name "Backend quotes validation" -Method "POST" -Url "http://127.0.0.1:8000/api/quotes" -ExpectedStatus 422 -Body "{}"

try {
    $artisanHealth = & php .\backend\artisan freightflow:health 2>&1
    $healthText = ($artisanHealth | Out-String).Trim()
    $ok = $healthText -match "FreightFlow API ready"
    Add-Result -Name "Backend artisan health command" -Passed $ok -Detail ($healthText -replace "\r?\n", " ")
} catch {
    Add-Result -Name "Backend artisan health command" -Passed $false -Detail $_.Exception.Message
}

Test-ProcessMatch -Name "Queue worker process" -Pattern "artisan\s+queue:work"
Test-ProcessMatch -Name "Reverb worker process" -Pattern "artisan\s+reverb:start"

Write-Host ""
Write-Host "FreightFlow Health Check"
Write-Host "------------------------"

foreach ($item in $checks) {
    if ($item.Passed) {
        Write-Host "PASS" $item.Check "-" $item.Detail -ForegroundColor Green
    } else {
        Write-Host "FAIL" $item.Check "-" $item.Detail -ForegroundColor Red
    }
}

Write-Host ""
if ($hasFailures) {
    Write-Host "Overall: FAILED" -ForegroundColor Red
    exit 1
}

Write-Host "Overall: PASSED" -ForegroundColor Green
exit 0
