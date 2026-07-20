# paynkolay for OpenCart

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![OpenCart](https://img.shields.io/badge/OpenCart-2.0%20%7C%202.3%20%7C%203.x%20%7C%204.x-1e91cf)](https://www.opencart.com)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-777BB4)](https://php.net)

OpenCart payment extension for [paynkolay](https://paynkolay.com.tr) virtual POS. Accept credit card payments on your OpenCart store with 3D Secure support.

paynkolay sanal POS ile OpenCart mağazanızda kredi kartı ile 3D Secure destekli ödeme alın.

---

## Features

- 3D Secure payment support
- Hash verification (SHA-512 `hashDatav2`) for transaction security
- Test mode for development and testing
- Minimum order total and geo-zone restrictions
- Single-file PHP SDK (`system/library/paynkolay.php`) shared across all OpenCart versions

## Supported versions

| OpenCart | Plugin folder | Tested on |
|---|---|---|
| 2.0.x | `opencart-2.0/` | 2.0.3.1 |
| 2.3.x | `opencart-2.3/` | 2.3.0.2 |
| 3.x | `opencart-3.x/` | 3.0.2.0, 3.0.3.9 |
| 4.x | `opencart-4.x/` | 4.0.2.3 |

Requires the PHP `curl` extension. PHP 7.0+ (on PHP < 7.3 the SDK falls back to a legacy `setcookie()` call for the 3D Secure `SameSite` cookie fix).

## Installation

1. Download the `.ocmod.zip` for your OpenCart version from the [latest release](https://github.com/paynkolay/paynkolay-opencart/releases)
2. Install it:
   - **OpenCart 2.0 / 3.x / 4.x**: Admin → **Extensions → Installer**, upload the zip
   - **OpenCart 2.3**: extract the zip and upload the `admin/`, `catalog/` and `system/` folders into your store root (merge with existing folders)
3. Go to **Extensions → Extensions → Payments** (OpenCart 2.0: **Extensions → Payments**)
4. Find **paynkolay**, click **Install**, then **Edit**

## Configuration

| Setting | Description |
|---|---|
| **Token (SX)** | Your merchant token from the paynkolay dashboard |
| **Merchant Secret Key** | Your secret key from the paynkolay dashboard |
| **Test Mode** | Enable to use the test environment for development |
| **3D Secure** | Whether payments go through 3D Secure (recommended: on) |
| **Order Status** | Status assigned to orders after successful payment |
| **Minimum Total / Geo Zone** | Optional availability restrictions |

## Test mode

When test mode is enabled, transactions are processed through the paynkolay test environment (`paynkolaytest.nkolayislem.com.tr`). No real charges are made.

You can obtain test credentials from your paynkolay merchant dashboard.

## Development

### Local test stores (Docker)

`docker-compose.yml` spins up one store per supported OpenCart version, with the plugin folders bind-mounted so edits apply immediately:

```bash
docker compose up -d --build
```

| URL | Version | Notes |
|---|---|---|
| http://localhost:8000 | — | Landing page |
| http://localhost:8020 | OpenCart 2.0.3.1 | |
| http://localhost:8023 | OpenCart 2.3.0.2 | |
| http://localhost:8030 | OpenCart 3.0.3.9 | PHP 8.1 |
| http://localhost:8032 | OpenCart 3.0.2.0 | PHP 7.2 (era-typical legacy host) |
| http://localhost:8040 | OpenCart 4.0.2.3 | |

Each store auto-installs on first boot (admin login: `admin` / `admin`) and registers the paynkolay payment extension.

### Building release zips

```bash
./build.sh   # writes dist/paynkolay-opencart-{2.0,2.3,3.x,4.x}.ocmod.zip
```

### Project structure

```
paynkolay-opencart/
├── opencart-2.0/        # Plugin for OpenCart 2.0.x
├── opencart-2.3/        # Plugin for OpenCart 2.3.x
├── opencart-3.x/        # Plugin for OpenCart 3.x
├── opencart-4.x/        # Plugin for OpenCart 4.x
├── docker/              # Per-version test store images
├── landing/             # Local dev landing page
└── build.sh             # Builds the .ocmod.zip files
```

Each plugin folder contains the standard OpenCart layout (`admin/`, `catalog/`, `system/library/paynkolay.php`). The SDK file is identical across versions; only the controllers/views differ per OpenCart API.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## License

This project is licensed under the [MIT License](LICENSE).

## Support

- [GitHub Issues](https://github.com/paynkolay/paynkolay-opencart/issues)
- [paynkolay website](https://paynkolay.com.tr)
