# Ollama Gateway with Monetization & more

A production-ready, OpenAI-compatible API gateway powered by Ollama. Feature-rich with prepaid billing, admin panel, two-factor authentication, user management, an interactive AI Playground, and a complete promotional ecosystem (referral programs, discount codes, gift cards, seller API). Built for individuals, communities, and businesses who want full control over their AI infrastructure.

## Features

- **OpenAI-compatible API** – Drop-in replacement for `/v1/chat/completions` and `/v1/models`. Use any existing OpenAI client with your own Ollama backend.
- **Ollama Backend** – Works with any model served by Ollama (LLaMA, Mistral, Qwen, DeepSeek, Gemma, and more).
- **Prepaid Billing** – Per-token pricing with automatic balance deduction during requests. HTTP 402 for insufficient funds.
- **Real-time Streaming** – Server-sent events (SSE) for instant token-by-token output.
- **Request Cancellation** – Cancel in-flight streaming requests via the API.
- **Admin Panel** – Comprehensive dashboard for managing users, models and pricing, transactions, payment configuration, and all promotional systems.
- **Two-Factor Authentication** – TOTP-based 2FA with recovery codes.
- **AI Playground** – Interactive web UI for testing models directly in the browser.
- **Referral System** – Reward users for referring new customers. Configurable per-code limits and reward amounts.
- **Discount Codes** – Percentage-based discounts on top-ups with expiry and usage limits.
- **Gift Cards** – Pre-paid, single-use codes redeemable during top-up.
- **Seller API** – Verified sellers can generate gift cards programmatically via a dedicated REST endpoint.
- **Queue System** – Optional request queuing with configurable concurrency limits.
- **Rate Limiting** – Built-in rate limiting for authentication endpoints.

## Requirements

| Dependency | Version |
|-----------|---------|
| PHP | 8.3 or higher |
| SQLite | (development) |
| MySQL | 8.0 or higher (production) |
| Composer | latest |
| Ollama | running instance (default: `http://127.0.0.1:11434`) |
| PHP Extensions | `pdo`, `pdo_sqlite`, `pdo_mysql`, `curl`, `mbstring`, `sodium`, `bcmath` |

## Quick Start

```bash
git clone https://github.com/justinsanjp/Ollama-Gateway-with-Monetization-and-more.git
cd Ollama-Gateway-with-Monetization-and-more
composer install
php setup.php
php -S localhost:8000 -t public
```

Open http://localhost:8000 in your browser and follow the interactive setup wizard.

## Manual Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Run `php migrations/migrate.php`
5. Point your web server to the `public/` directory

## Web Server Configuration

### Apache

Ensure `mod_rewrite` is enabled, then create a virtual host:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/ollama-gateway/public
    <Directory /var/www/ollama-gateway/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/ollama-gateway/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Configuration

All settings are managed through the `.env` file:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Ollama Gateway` |
| `APP_ENV` | Environment (`production`/`development`) | `production` |
| `APP_URL` | Public URL of the application | `http://localhost` |
| `APP_DEBUG` | Enable debug mode | `false` |
| `DB_DRIVER` | Database driver (`sqlite`/`mysql`) | `sqlite` |
| `DB_HOST` | MySQL host | `127.0.0.1` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_DATABASE` | Database name or SQLite path | `data/ollama_gateway.sqlite` |
| `DB_USERNAME` | MySQL username | `root` |
| `DB_PASSWORD` | MySQL password | |
| `OLLAMA_BASE_URL` | Ollama server URL | `http://127.0.0.1:11434` |
| `ADMIN_EMAIL` | Admin account email (required for setup) | |
| `ADMIN_PASSWORD` | Admin account password (required for setup) | |

## API Usage

```bash
curl https://your-domain.com/v1/chat/completions \
  -H "Authorization: Bearer og_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5-coder:7b",
    "messages": [{"role": "user", "content": "Hello!"}]
  }'
```

Streaming response:

```bash
curl -N https://your-domain.com/v1/chat/completions \
  -H "Authorization: Bearer og_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5-coder:7b",
    "messages": [{"role": "user", "content": "Write a poem"}],
    "stream": true
  }'
```

## Project Structure

```
├── public/              # Web server root (entry point)
├── src/                 # Application source code
│   ├── Config/          # Configuration and constants
│   ├── Controllers/     # Request handlers
│   ├── Helpers/         # Utility classes (View, etc.)
│   ├── Middleware/      # Auth, rate limiting, CSRF, etc.
│   ├── Models/          # Database models
│   ├── Router/          # Request routing
│   └── Services/        # Business logic (Ollama, Auth, Billing, Queue)
├── views/               # PHP view templates
├── migrations/          # Database migrations
├── data/                # SQLite data directory (gitignored)
├── setup.php            # Interactive CLI installer
├── .env.example         # Environment configuration template
└── vendor/              # Composer dependencies
```

## Contributing

Contributions are welcome and valued. Financial contributions and code contributions are both appreciated equally. Please open an issue or pull request in the official repository.

## License

This project is licensed under the **Ollama Gateway Custom License** — see the [LICENSE](LICENSE) file for full terms.

Key points:
- **Free for everyone** — all features including monetization can be used without a paid license.
- **Attribution required** — you must link back to the official repository when using this software.
- **No false ownership** — you may not claim the software as your own creation.
- **Open source derivatives** — any modifications, forks, or extensions must remain open source.
- **Inclusive community** — this project stands against right-wing extremism, racism, and queerphobia.

## Contact

For licensing inquiries and commercial license requests, please open an issue in the official repository.
