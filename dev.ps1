param(
    [Parameter(Position=0)]
    [string]$Command = "help",
    [string]$Filter = ""
)

$SERVICE = "sdk"

function Invoke-Build {
    Write-Host ""
    Write-Host "  Building Docker image..." -ForegroundColor Cyan
    docker compose build
}

switch ($Command) {

    "install" {
        Invoke-Build
        Write-Host ""
        Write-Host "  Installing Composer dependencies..." -ForegroundColor Cyan
        docker compose run --rm $SERVICE "composer install --no-interaction --prefer-dist"
    }

    "test" {
        Write-Host ""
        Write-Host "  Running test suite..." -ForegroundColor Cyan
        docker compose run --rm $SERVICE "vendor/bin/phpunit --testdox"
    }

    "test-filter" {
        if (-not $Filter) {
            Write-Host "  ERROR: provide -Filter, e.g: .\dev.ps1 test-filter -Filter ExceptionsTest" -ForegroundColor Red
            exit 1
        }
        Write-Host ""
        Write-Host "  Running tests matching '$Filter'..." -ForegroundColor Cyan
        docker compose run --rm $SERVICE "vendor/bin/phpunit --testdox --filter=$Filter"
    }

    "shell" {
        Write-Host ""
        Write-Host "  Opening shell inside container. Type 'exit' to leave." -ForegroundColor Cyan
        docker compose run --rm --entrypoint bash $SERVICE
    }

    "example" {
        Write-Host ""
        Write-Host "  Running example_usage.php..." -ForegroundColor Cyan
        docker compose run --rm $SERVICE "php example_usage.php"
    }

    "build" {
        Invoke-Build
    }

    "clean" {
        Write-Host ""
        Write-Host "  Removing containers, volumes, and image..." -ForegroundColor Yellow
        docker compose down -v
        docker compose rm -f
        docker image rm pyzit-tempmail-php-sdk 2>$null
        Write-Host "  Done. Run '.\dev.ps1 install' to start fresh." -ForegroundColor Green
    }

    "help" {
        Write-Host ""
        Write-Host "  pyzit/tempmail PHP SDK - dev commands" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "  .\dev.ps1 install                       Build image + composer install"
        Write-Host "  .\dev.ps1 test                          Run all 81 tests"
        Write-Host "  .\dev.ps1 test-filter -Filter FooTest   Run tests matching a name"
        Write-Host "  .\dev.ps1 shell                         Bash shell inside container"
        Write-Host "  .\dev.ps1 example                       Run example_usage.php"
        Write-Host "  .\dev.ps1 build                         Rebuild the Docker image"
        Write-Host "  .\dev.ps1 clean                         Full reset (volumes + image)"
        Write-Host ""
    }

    default {
        Write-Host "  Unknown command '$Command'. Run '.\dev.ps1 help' to see options." -ForegroundColor Red
        exit 1
    }
}