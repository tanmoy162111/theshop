# Control-Plane Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the foundation that lets us resell "The Shop" to many self-hosted clients and track/bill commission: a central super-admin app plus an in-app agent that registers, reports sales, and obeys soft-enforcement status.

**Architecture:** Each client runs an ordinary single-store install of The Shop with an embedded *agent* module. The agent is client-initiated only (outbound HTTPS). A separate central Laravel app holds the client registry, ingests aggregate sales reports, computes commission (percent or flat-per-order), and sets a status the agent obeys (active → warning → locked_admin → maintenance). See spec: `docs/superpowers/specs/2026-06-16-saas-control-plane-foundation-design.md`.

**Tech Stack:** PHP 8.1, Laravel 10 (central app, new) + Laravel 9 (The Shop, existing), MySQL/MariaDB (prod) + SQLite in-memory (tests), Laravel Breeze (super-admin auth, Blade), Laravel `Http` client + `Http::fake()` for tests, PHPUnit.

**Conventions:** TDD throughout (failing test first). Commit after every green step. Exact paths below are relative to repo root `/home/tanmoy/Projects/Shop/theshop`. The Shop app root is `codecanyon-34858541-the-shop/install/` (referred to as `<shop>/`). The central app lives in `control-plane/`.

---

## Phase 0 — Repo & Scaffolding

### Task 0.1: Initialize git at repo root

**Files:**
- Create: `.gitignore`

- [ ] **Step 1: Initialize repo**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git init
```

- [ ] **Step 2: Write `.gitignore`**

```
# dependencies
**/vendor/
**/node_modules/
# env & local
**/.env
**/.env.backup
# build & cache
**/public/build/
**/storage/*.key
codecanyon-34858541-the-shop/install/storage/framework/cache/data/*
codecanyon-34858541-the-shop/install/storage/logs/*
control-plane/storage/logs/*
# uploads (demo/runtime assets)
codecanyon-34858541-the-shop/install/public/uploads/all/
# os
.DS_Store
```

- [ ] **Step 3: Initial commit**

```bash
git add .gitignore docs/
git commit -m "chore: init repo with gitignore and design docs"
```

Expected: commit succeeds; `vendor/` and `node_modules/` not staged.

---

### Task 0.2: Scaffold the central Laravel app

**Files:**
- Create: `control-plane/` (Laravel 10 project)

- [ ] **Step 1: Create the project (inside the PHP 8.1 toolchain)**

Run from `<shop>/` so the existing `app` container's PHP/composer is available, OR use host composer if present:

```bash
cd /home/tanmoy/Projects/Shop/theshop
docker compose -f codecanyon-34858541-the-shop/install/docker-compose.yml run --rm \
  -v "$PWD":/work -w /work app \
  composer create-project laravel/laravel control-plane "^10.0"
```

Expected: `control-plane/artisan` exists; `php control-plane/artisan --version` reports Laravel 10.x.

- [ ] **Step 2: Install Breeze (Blade) for super-admin auth**

```bash
cd /home/tanmoy/Projects/Shop/theshop/control-plane
docker compose -f ../codecanyon-34858541-the-shop/install/docker-compose.yml run --rm \
  -v "$PWD/..":/work -w /work/control-plane app composer require laravel/breeze --dev
docker compose -f ../codecanyon-34858541-the-shop/install/docker-compose.yml run --rm \
  -v "$PWD/..":/work -w /work/control-plane app php artisan breeze:install blade
```

Expected: `routes/auth.php`, `app/Http/Controllers/Auth/*`, login views exist.

- [ ] **Step 3: Configure SQLite in-memory for tests**

Modify `control-plane/phpunit.xml` — ensure these env lines are present/uncommented inside `<php>`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 4: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane
git commit -m "chore(control-plane): scaffold Laravel 10 app with Breeze auth"
```

---

## Phase A — Central Super-Admin App

All commands in Phase A run with the working directory `control-plane/`. Shorthand for running artisan/phpunit in the container:

```bash
# from control-plane/
alias cp-art='docker compose -f ../codecanyon-34858541-the-shop/install/docker-compose.yml run --rm -v "$PWD/..":/work -w /work/control-plane app php artisan'
alias cp-test='docker compose -f ../codecanyon-34858541-the-shop/install/docker-compose.yml run --rm -v "$PWD/..":/work -w /work/control-plane app php artisan test'
```
(If host PHP 8.1 is available, plain `php artisan` / `php artisan test` works too.)

### Task A1: `clients` and `sales_reports` migrations + models

**Files:**
- Create: `control-plane/database/migrations/2026_06_16_000001_create_clients_table.php`
- Create: `control-plane/database/migrations/2026_06_16_000002_create_sales_reports_table.php`
- Create: `control-plane/app/Models/Client.php`
- Create: `control-plane/app/Models/SalesReport.php`
- Test: `control-plane/tests/Feature/ClientModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// control-plane/tests/Feature/ClientModelTest.php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_has_reports_relation_and_defaults(): void
    {
        $client = Client::create([
            'business_name' => 'Acme Pharma',
            'contact_email' => 'a@acme.test',
            'primary_domain' => 'acme.test',
            'token' => hash('sha256', 'secret-token'),
        ]);

        $client->refresh(); // load DB defaults (status/commission_type/commission_rate)
        $this->assertSame('pending', $client->status);
        $this->assertSame('percent', $client->commission_type);
        $this->assertEquals(0, $client->commission_rate);

        $client->reports()->create([
            'period_start' => '2026-06-15',
            'period_end' => '2026-06-15',
            'gross_sales' => 100.50,
            'order_count' => 3,
            'currency' => 'USD',
            'received_at' => now(),
        ]);

        $this->assertCount(1, $client->reports);
        $this->assertInstanceOf(SalesReport::class, $client->reports->first());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cp-test --filter=ClientModelTest`
Expected: FAIL ("Class App\Models\Client not found" / no such table).

- [ ] **Step 3: Write the `clients` migration**

```php
<?php
// control-plane/database/migrations/2026_06_16_000001_create_clients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_email');
            $table->string('primary_domain')->unique();
            $table->enum('status', ['pending','active','warning','locked_admin','maintenance','rejected'])
                  ->default('pending');
            $table->enum('commission_type', ['percent','per_order'])->default('percent');
            $table->decimal('commission_rate', 20, 2)->default(0);
            $table->string('token');
            $table->string('app_version')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_report_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('clients'); }
};
```

- [ ] **Step 4: Write the `sales_reports` migration**

```php
<?php
// control-plane/database/migrations/2026_06_16_000002_create_sales_reports_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_sales', 20, 2)->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->unique(['client_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void { Schema::dropIfExists('sales_reports'); }
};
```

- [ ] **Step 5: Write the models**

```php
<?php
// control-plane/app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'business_name','contact_email','primary_domain','status',
        'commission_type','commission_rate','token','app_version',
        'registered_at','approved_at','last_report_at','last_seen_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'last_report_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(SalesReport::class);
    }
}
```

```php
<?php
// control-plane/app/Models/SalesReport.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReport extends Model
{
    protected $fillable = [
        'client_id','period_start','period_end','gross_sales',
        'order_count','currency','received_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_sales' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `cp-test --filter=ClientModelTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane/database control-plane/app/Models control-plane/tests
git commit -m "feat(control-plane): clients and sales_reports models + migrations"
```

---

### Task A2: Commission computation service

**Files:**
- Create: `control-plane/app/Services/CommissionCalculator.php`
- Test: `control-plane/tests/Unit/CommissionCalculatorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// control-plane/tests/Unit/CommissionCalculatorTest.php
namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    public function test_percent_commission(): void
    {
        // 10% of 1000 gross = 100
        $this->assertEquals(100.00, (new CommissionCalculator)->owed('percent', 10, 1000.00, 7));
    }

    public function test_per_order_commission(): void
    {
        // $2.50 flat per order, 7 orders = 17.50 (gross ignored)
        $this->assertEquals(17.50, (new CommissionCalculator)->owed('per_order', 2.50, 1000.00, 7));
    }

    public function test_zero_rate_returns_zero(): void
    {
        $this->assertEquals(0.0, (new CommissionCalculator)->owed('percent', 0, 1000.00, 7));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cp-test --filter=CommissionCalculatorTest`
Expected: FAIL ("Class App\Services\CommissionCalculator not found").

- [ ] **Step 3: Write the implementation**

```php
<?php
// control-plane/app/Services/CommissionCalculator.php
namespace App\Services;

class CommissionCalculator
{
    /**
     * @param string $type 'percent' | 'per_order'
     * @param float  $rate  percentage (percent) or flat amount per order (per_order)
     */
    public function owed(string $type, float $rate, float $grossSales, int $orderCount): float
    {
        return match ($type) {
            'per_order' => round($orderCount * $rate, 2),
            default     => round($grossSales * ($rate / 100), 2),
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cp-test --filter=CommissionCalculatorTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane/app/Services control-plane/tests/Unit
git commit -m "feat(control-plane): commission calculator (percent + per_order)"
```

---

### Task A3: Agent token auth middleware

**Files:**
- Create: `control-plane/app/Http/Middleware/AuthenticateAgent.php`
- Modify: `control-plane/app/Http/Kernel.php` (register route middleware alias `agent.auth`)
- Test: `control-plane/tests/Feature/AgentAuthTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// control-plane/tests/Feature/AgentAuthTest.php
namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AgentAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('agent.auth')->get('/_probe', fn () => response()->json([
            'client_id' => request()->attributes->get('agent_client')->id,
        ]));
    }

    public function test_rejects_missing_token(): void
    {
        $this->getJson('/_probe')->assertStatus(401);
    }

    public function test_rejects_domain_mismatch(): void
    {
        Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'),
        ]);
        $this->getJson('/_probe', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'evil.test',
        ])->assertStatus(403);
    }

    public function test_accepts_valid_token_and_domain(): void
    {
        $c = Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'),
        ]);
        $this->getJson('/_probe', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test',
        ])->assertOk()->assertJson(['client_id' => $c->id]);

        $this->assertNotNull($c->fresh()->last_seen_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cp-test --filter=AgentAuthTest`
Expected: FAIL ("Target class [agent.auth] does not exist").

- [ ] **Step 3: Write the middleware**

```php
<?php
// control-plane/app/Http/Middleware/AuthenticateAgent.php
namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Missing token'], 401);
        }

        $client = Client::where('token', hash('sha256', $token))->first();
        if (! $client) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($request->header('X-Agent-Domain') !== $client->primary_domain) {
            return response()->json(['message' => 'Domain mismatch'], 403);
        }

        $client->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('agent_client', $client);

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the alias**

In `control-plane/app/Http/Kernel.php`, add to the `$middlewareAliases` array:

```php
'agent.auth' => \App\Http\Middleware\AuthenticateAgent::class,
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cp-test --filter=AgentAuthTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane/app/Http
git commit -m "feat(control-plane): agent bearer-token + domain auth middleware"
```

---

### Task A4: Agent API — register / report / status

**Files:**
- Create: `control-plane/app/Http/Controllers/Api/AgentController.php`
- Modify: `control-plane/routes/api.php`
- Test: `control-plane/tests/Feature/AgentApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// control-plane/tests/Feature/AgentApiTest.php
namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_pending_client_and_returns_token(): void
    {
        $res = $this->postJson('/api/v1/agent/register', [
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'domain' => 'acme.test', 'app_version' => '1.0.0',
        ])->assertCreated()->assertJson(['status' => 'pending']);

        $token = $res->json('token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('clients', [
            'primary_domain' => 'acme.test', 'status' => 'pending',
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_register_duplicate_domain_returns_existing_status_without_new_token(): void
    {
        Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'primary_domain' => 'acme.test', 'token' => hash('sha256', 'orig'), 'status' => 'active',
        ]);

        $this->postJson('/api/v1/agent/register', [
            'business_name' => 'Acme', 'contact_email' => 'a@acme.test',
            'domain' => 'acme.test', 'app_version' => '1.0.0',
        ])->assertOk()->assertJson(['status' => 'active'])->assertJsonMissing(['token' => true]);
    }

    public function test_report_upserts_and_is_idempotent(): void
    {
        $c = $this->activeClient();

        $payload = [
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 250.00, 'order_count' => 4, 'currency' => 'USD', 'app_version' => '1.0.0',
        ];
        $headers = ['Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test'];

        $this->postJson('/api/v1/agent/report', $payload, $headers)
             ->assertOk()->assertJson(['accepted' => true, 'commission_type' => 'percent']);
        // resend same period -> still one row
        $this->postJson('/api/v1/agent/report', array_merge($payload, ['gross_sales' => 999.00]), $headers)
             ->assertOk();

        $this->assertDatabaseCount('sales_reports', 1);
        $this->assertEquals(999.00, $c->reports()->first()->gross_sales);
        $this->assertNotNull($c->fresh()->last_report_at);
    }

    public function test_report_rejected_when_pending(): void
    {
        Client::create([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'), 'status' => 'pending',
        ]);
        $this->postJson('/api/v1/agent/report', [
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 1, 'order_count' => 1, 'currency' => 'USD',
        ], ['Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test'])
          ->assertOk()->assertJson(['accepted' => false, 'status' => 'pending']);
        $this->assertDatabaseCount('sales_reports', 0);
    }

    public function test_status_endpoint_returns_config(): void
    {
        $this->activeClient(['commission_type' => 'per_order', 'commission_rate' => 3]);
        $this->getJson('/api/v1/agent/status', [
            'Authorization' => 'Bearer tok', 'X-Agent-Domain' => 'a.test',
        ])->assertOk()->assertJson([
            'status' => 'active', 'commission_type' => 'per_order', 'commission_rate' => '3.00',
        ]);
    }

    private function activeClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'business_name' => 'A', 'contact_email' => 'a@a.test',
            'primary_domain' => 'a.test', 'token' => hash('sha256', 'tok'), 'status' => 'active',
        ], $overrides));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cp-test --filter=AgentApiTest`
Expected: FAIL (routes/controller missing).

- [ ] **Step 3: Write the controller**

```php
<?php
// control-plane/app/Http/Controllers/Api/AgentController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'domain'        => 'required|string|max:255',
            'app_version'   => 'nullable|string|max:50',
        ]);

        $existing = Client::where('primary_domain', $data['domain'])->first();
        if ($existing) {
            return response()->json(['status' => $existing->status], 200);
        }

        $plain = Str::random(48);
        $client = Client::create([
            'business_name'  => $data['business_name'],
            'contact_email'  => $data['contact_email'],
            'primary_domain' => $data['domain'],
            'app_version'    => $data['app_version'] ?? null,
            'token'          => hash('sha256', $plain),
            'status'         => 'pending',
            'registered_at'  => now(),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'token'     => $plain,
            'status'    => $client->status,
        ], 201);
    }

    public function report(Request $request)
    {
        /** @var Client $client */
        $client = $request->attributes->get('agent_client');

        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date',
            'gross_sales'  => 'required|numeric|min:0',
            'order_count'  => 'required|integer|min:0',
            'currency'     => 'required|string|size:3',
            'app_version'  => 'nullable|string|max:50',
        ]);

        if (in_array($client->status, ['pending', 'rejected'], true)) {
            return response()->json(['accepted' => false] + $this->statusPayload($client), 200);
        }

        $client->reports()->updateOrCreate(
            ['period_start' => $data['period_start'], 'period_end' => $data['period_end']],
            [
                'gross_sales' => $data['gross_sales'],
                'order_count' => $data['order_count'],
                'currency'    => $data['currency'],
                'received_at' => now(),
            ]
        );

        $client->forceFill([
            'last_report_at' => now(),
            'app_version'    => $data['app_version'] ?? $client->app_version,
        ])->save();

        return response()->json(['accepted' => true] + $this->statusPayload($client), 200);
    }

    public function status(Request $request)
    {
        return response()->json($this->statusPayload($request->attributes->get('agent_client')), 200);
    }

    private function statusPayload(Client $client): array
    {
        return [
            'status'          => $client->status,
            'commission_type' => $client->commission_type,
            'commission_rate' => $client->commission_rate,
            'message'         => $this->messageFor($client->status),
            'grace_until'     => null, // automatic grace handled in billing sub-project
        ];
    }

    private function messageFor(string $status): ?string
    {
        return match ($status) {
            'warning'      => 'Commission payment is overdue. Please settle to avoid interruption.',
            'locked_admin' => 'Admin access is locked pending commission payment.',
            'maintenance'  => 'This store is temporarily unavailable.',
            default        => null,
        };
    }
}
```

- [ ] **Step 4: Add routes**

Append to `control-plane/routes/api.php`:

```php
use App\Http\Controllers\Api\AgentController;

Route::prefix('v1/agent')->group(function () {
    Route::post('register', [AgentController::class, 'register'])->middleware('throttle:20,1');
    Route::middleware('agent.auth')->group(function () {
        Route::post('report', [AgentController::class, 'report']);
        Route::get('status', [AgentController::class, 'status']);
    });
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cp-test --filter=AgentApiTest`
Expected: PASS (all 5 cases).

- [ ] **Step 6: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane/app/Http/Controllers control-plane/routes/api.php control-plane/tests
git commit -m "feat(control-plane): agent register/report/status API"
```

---

### Task A5: Super-admin dashboard (registry, approval, commission, status)

**Files:**
- Create: `control-plane/app/Http/Controllers/ClientAdminController.php`
- Modify: `control-plane/routes/web.php`
- Create: `control-plane/resources/views/clients/index.blade.php`
- Create: `control-plane/resources/views/clients/show.blade.php`
- Test: `control-plane/tests/Feature/ClientAdminTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// control-plane/tests/Feature/ClientAdminTest.php
namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_clients(): void
    {
        $this->get('/clients')->assertRedirect('/login');
    }

    public function test_admin_sees_clients_with_computed_commission(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'active',
            'commission_type' => 'percent', 'commission_rate' => 10,
        ]);
        $c->reports()->create([
            'period_start' => '2026-06-15', 'period_end' => '2026-06-15',
            'gross_sales' => 1000, 'order_count' => 5, 'currency' => 'USD', 'received_at' => now(),
        ]);

        $this->actingAs($admin)->get('/clients')
             ->assertOk()->assertSee('Acme')->assertSee('100.00'); // 10% of 1000
    }

    public function test_admin_can_approve_client(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/clients/{$c->id}", ['action' => 'approve'])
             ->assertRedirect();
        $this->assertSame('active', $c->fresh()->status);
        $this->assertNotNull($c->fresh()->approved_at);
    }

    public function test_admin_can_set_commission_and_status(): void
    {
        $admin = User::factory()->create();
        $c = Client::create([
            'business_name' => 'Acme', 'contact_email' => 'a@a.test', 'primary_domain' => 'a.test',
            'token' => hash('sha256', 't'), 'status' => 'active',
        ]);

        $this->actingAs($admin)->patch("/clients/{$c->id}", [
            'action' => 'update',
            'commission_type' => 'per_order', 'commission_rate' => 2.5, 'status' => 'warning',
        ])->assertRedirect();

        $c->refresh();
        $this->assertSame('per_order', $c->commission_type);
        $this->assertEquals(2.5, $c->commission_rate);
        $this->assertSame('warning', $c->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cp-test --filter=ClientAdminTest`
Expected: FAIL (route/controller/views missing).

- [ ] **Step 3: Write the controller**

```php
<?php
// control-plane/app/Http/Controllers/ClientAdminController.php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\CommissionCalculator;
use Illuminate\Http\Request;

class ClientAdminController extends Controller
{
    public function index(CommissionCalculator $calc)
    {
        $clients = Client::withSum('reports as gross_total', 'gross_sales')
            ->withSum('reports as orders_total', 'order_count')
            ->orderByDesc('created_at')->get()
            ->map(function (Client $c) use ($calc) {
                $c->commission_owed = $calc->owed(
                    $c->commission_type, (float) $c->commission_rate,
                    (float) ($c->gross_total ?? 0), (int) ($c->orders_total ?? 0)
                );
                return $c;
            });

        return view('clients.index', compact('clients'));
    }

    public function show(Client $client, CommissionCalculator $calc)
    {
        $client->loadMissing('reports');
        $grossTotal = (float) $client->reports->sum('gross_sales');
        $ordersTotal = (int) $client->reports->sum('order_count');
        $commissionOwed = $calc->owed($client->commission_type, (float) $client->commission_rate, $grossTotal, $ordersTotal);

        return view('clients.show', compact('client', 'grossTotal', 'ordersTotal', 'commissionOwed'));
    }

    public function update(Request $request, Client $client)
    {
        $action = $request->input('action');

        if ($action === 'approve') {
            $client->forceFill(['status' => 'active', 'approved_at' => now()])->save();
            return back()->with('status', 'Client approved.');
        }

        if ($action === 'reject') {
            $client->forceFill(['status' => 'rejected'])->save();
            return back()->with('status', 'Client rejected.');
        }

        $data = $request->validate([
            'commission_type' => 'required|in:percent,per_order',
            'commission_rate' => 'required|numeric|min:0',
            'status'          => 'required|in:active,warning,locked_admin,maintenance',
        ]);
        $client->update($data);

        return back()->with('status', 'Client updated.');
    }
}
```

- [ ] **Step 4: Add routes**

In `control-plane/routes/web.php`, inside the existing `auth` middleware group (Breeze created one for dashboard), add:

```php
use App\Http\Controllers\ClientAdminController;

Route::middleware('auth')->group(function () {
    Route::get('/clients', [ClientAdminController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [ClientAdminController::class, 'show'])->name('clients.show');
    Route::patch('/clients/{client}', [ClientAdminController::class, 'update'])->name('clients.update');
});
```

- [ ] **Step 5: Write the index view**

```blade
{{-- control-plane/resources/views/clients/index.blade.php --}}
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Clients</h2></x-slot>
    <div class="p-6">
        @if (session('status'))<div class="mb-4 text-green-700">{{ session('status') }}</div>@endif
        <table class="w-full text-left border">
            <thead><tr class="bg-gray-100">
                <th class="p-2">Business</th><th class="p-2">Domain</th><th class="p-2">Status</th>
                <th class="p-2">Commission</th><th class="p-2">Gross</th><th class="p-2">Owed</th>
                <th class="p-2">Last seen</th><th class="p-2"></th>
            </tr></thead>
            <tbody>
            @foreach ($clients as $c)
                <tr class="border-t">
                    <td class="p-2">{{ $c->business_name }}</td>
                    <td class="p-2">{{ $c->primary_domain }}</td>
                    <td class="p-2">{{ $c->status }}</td>
                    <td class="p-2">{{ $c->commission_type }} {{ $c->commission_rate }}</td>
                    <td class="p-2">{{ number_format($c->gross_total ?? 0, 2) }}</td>
                    <td class="p-2">{{ number_format($c->commission_owed, 2) }}</td>
                    <td class="p-2">{{ optional($c->last_seen_at)->diffForHumans() ?? '—' }}</td>
                    <td class="p-2"><a class="text-blue-600" href="{{ route('clients.show', $c) }}">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Write the show view**

```blade
{{-- control-plane/resources/views/clients/show.blade.php --}}
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $client->business_name }}</h2></x-slot>
    <div class="p-6 space-y-6 max-w-2xl">
        @if (session('status'))<div class="text-green-700">{{ session('status') }}</div>@endif

        <div class="border p-4 rounded">
            <p><strong>Domain:</strong> {{ $client->primary_domain }}</p>
            <p><strong>Status:</strong> {{ $client->status }}</p>
            <p><strong>Gross sales:</strong> {{ number_format($grossTotal, 2) }}
               ({{ $ordersTotal }} orders)</p>
            <p><strong>Commission owed:</strong> {{ number_format($commissionOwed, 2) }}</p>
        </div>

        @if ($client->status === 'pending')
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button class="bg-green-600 text-white px-4 py-2 rounded">Approve</button>
            </form>
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <button class="bg-red-600 text-white px-4 py-2 rounded">Reject</button>
            </form>
        @else
            <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-3">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="update">
                <label class="block">Commission type
                    <select name="commission_type" class="block border rounded w-full">
                        <option value="percent" @selected($client->commission_type==='percent')>Percent of sales</option>
                        <option value="per_order" @selected($client->commission_type==='per_order')>Flat per order</option>
                    </select>
                </label>
                <label class="block">Commission value
                    <input type="number" step="0.01" name="commission_rate" value="{{ $client->commission_rate }}"
                           class="block border rounded w-full">
                </label>
                <label class="block">Status
                    <select name="status" class="block border rounded w-full">
                        @foreach (['active','warning','locked_admin','maintenance'] as $s)
                            <option value="{{ $s }}" @selected($client->status===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
            </form>
        @endif

        <div>
            <h3 class="font-semibold">Report history</h3>
            <table class="w-full text-left border mt-2">
                <thead><tr class="bg-gray-100"><th class="p-2">Period</th><th class="p-2">Gross</th><th class="p-2">Orders</th></tr></thead>
                <tbody>
                @foreach ($client->reports->sortByDesc('period_start') as $r)
                    <tr class="border-t"><td class="p-2">{{ $r->period_start->toDateString() }}</td>
                        <td class="p-2">{{ number_format($r->gross_sales, 2) }} {{ $r->currency }}</td>
                        <td class="p-2">{{ $r->order_count }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 7: Run test to verify it passes**

Run: `cp-test --filter=ClientAdminTest`
Expected: PASS.

- [ ] **Step 8: Run the full central suite + commit**

```bash
cp-test
cd /home/tanmoy/Projects/Shop/theshop
git add control-plane
git commit -m "feat(control-plane): super-admin dashboard with approval, commission, status"
```

Expected: entire `control-plane` suite green.

---

## Phase B — Agent Module (inside The Shop)

Working directory: `<shop>/` = `codecanyon-34858541-the-shop/install/`. Run artisan/tests via the existing container:

```bash
# from <shop>/
alias shop-art='docker compose exec -T app php artisan'
alias shop-test='docker compose exec -T app php artisan test'
```

Tests use a dedicated SQLite connection so they never touch the live store DB.

### Task B0: Test harness for the agent (SQLite connection)

**Files:**
- Modify: `<shop>/phpunit.xml`
- Create: `<shop>/tests/Agent/AgentTestCase.php`

- [ ] **Step 1: Add a sqlite testing connection**

In `<shop>/config/database.php`, add inside `'connections' => [ ... ]`:

```php
'sqlite_testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
    'foreign_key_constraints' => true,
],
```

- [ ] **Step 2: Point tests at it**

In `<shop>/phpunit.xml` `<php>` section, set:

```xml
<env name="DB_CONNECTION" value="sqlite_testing"/>
<env name="AGENT_TESTING" value="true"/>
```

- [ ] **Step 3: Base test case that builds the tables the agent needs**

```php
<?php
// <shop>/tests/Agent/AgentTestCase.php
namespace Tests\Agent;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class AgentTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal schema the agent depends on (isolated from the full store schema).
        Schema::create('agent_settings', function ($t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function ($t) {
            $t->id();
            $t->decimal('grand_total', 20, 2)->default(0);
            $t->string('payment_status', 20)->default('unpaid');
            $t->timestamp('created_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
    }
}
```

- [ ] **Step 4: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/config/database.php \
        codecanyon-34858541-the-shop/install/phpunit.xml \
        codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "test(shop): sqlite-isolated agent test harness"
```

---

### Task B1: `agent_settings` table, model, and config store

**Files:**
- Create: `<shop>/database/migrations/2026_06_16_100001_create_agent_settings_table.php`
- Create: `<shop>/app/Agent/AgentSetting.php`
- Create: `<shop>/app/Agent/AgentConfig.php`
- Test: `<shop>/tests/Agent/AgentConfigTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/AgentConfigTest.php
namespace Tests\Agent;

use App\Agent\AgentConfig;

class AgentConfigTest extends AgentTestCase
{
    public function test_get_set_roundtrip_with_default(): void
    {
        $config = app(AgentConfig::class);
        $this->assertSame('unregistered', $config->get('status', 'unregistered'));

        $config->set('status', 'active');
        $config->set('commission_rate', '12.50');

        $this->assertSame('active', $config->get('status'));
        $this->assertSame('12.50', $config->get('commission_rate'));
    }

    public function test_is_registered_reflects_token_presence(): void
    {
        $config = app(AgentConfig::class);
        $this->assertFalse($config->isRegistered());
        $config->set('token', 'abc');
        $this->assertTrue($config->isRegistered());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=AgentConfigTest`
Expected: FAIL (classes missing).

- [ ] **Step 3: Write the migration**

```php
<?php
// <shop>/database/migrations/2026_06_16_100001_create_agent_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('agent_settings'); }
};
```

- [ ] **Step 4: Write the model + config store**

```php
<?php
// <shop>/app/Agent/AgentSetting.php
namespace App\Agent;

use Illuminate\Database\Eloquent\Model;

class AgentSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
```

```php
<?php
// <shop>/app/Agent/AgentConfig.php
namespace App\Agent;

class AgentConfig
{
    public function get(string $key, ?string $default = null): ?string
    {
        return optional(AgentSetting::where('key', $key)->first())->value ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        AgentSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function isRegistered(): bool
    {
        return ! empty($this->get('token'));
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `shop-test --filter=AgentConfigTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/database/migrations \
        codecanyon-34858541-the-shop/install/app/Agent codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): agent_settings store"
```

---

### Task B2: Sales aggregator

**Files:**
- Create: `<shop>/app/Agent/SalesAggregator.php`
- Test: `<shop>/tests/Agent/SalesAggregatorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/SalesAggregatorTest.php
namespace Tests\Agent;

use App\Agent\SalesAggregator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAggregatorTest extends AgentTestCase
{
    public function test_sums_only_paid_orders_in_period(): void
    {
        DB::table('orders')->insert([
            ['grand_total' => 100, 'payment_status' => 'paid',   'created_at' => '2026-06-15 10:00:00'],
            ['grand_total' => 50,  'payment_status' => 'paid',   'created_at' => '2026-06-15 23:59:59'],
            ['grand_total' => 999, 'payment_status' => 'unpaid', 'created_at' => '2026-06-15 12:00:00'],
            ['grand_total' => 777, 'payment_status' => 'paid',   'created_at' => '2026-06-16 00:00:01'], // out of range
        ]);

        $result = (new SalesAggregator)->forPeriod(
            Carbon::parse('2026-06-15 00:00:00'),
            Carbon::parse('2026-06-15 23:59:59')
        );

        $this->assertEquals(150.00, $result['gross_sales']);
        $this->assertSame(2, $result['order_count']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=SalesAggregatorTest`
Expected: FAIL (class missing).

- [ ] **Step 3: Write the implementation**

```php
<?php
// <shop>/app/Agent/SalesAggregator.php
namespace App\Agent;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SalesAggregator
{
    /** @return array{gross_sales: float, order_count: int} */
    public function forPeriod(CarbonInterface $start, CarbonInterface $end): array
    {
        $q = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end]);

        return [
            'gross_sales' => (float) $q->sum('grand_total'),
            'order_count' => (int) $q->count(),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=SalesAggregatorTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Agent codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): paid-order sales aggregator"
```

---

### Task B3: AgentClient (HTTP to central)

**Files:**
- Create: `<shop>/app/Agent/AgentClient.php`
- Test: `<shop>/tests/Agent/AgentClientTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/AgentClientTest.php
namespace Tests\Agent;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use Illuminate\Support\Facades\Http;

class AgentClientTest extends AgentTestCase
{
    public function test_register_stores_token_and_client_id(): void
    {
        Http::fake(['*/api/v1/agent/register' => Http::response([
            'client_id' => 7, 'token' => 'TKN', 'status' => 'pending',
        ], 201)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');

        $status = app(AgentClient::class)->register('Acme', 'a@acme.test', 'acme.test');

        $this->assertSame('pending', $status);
        $this->assertSame('TKN', $config->get('token'));
        $this->assertSame('7', $config->get('client_id'));
    }

    public function test_sync_status_caches_config(): void
    {
        Http::fake(['*/api/v1/agent/status' => Http::response([
            'status' => 'warning', 'commission_type' => 'per_order',
            'commission_rate' => '3.00', 'message' => 'overdue', 'grace_until' => null,
        ], 200)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');

        app(AgentClient::class)->syncStatus();

        $this->assertSame('warning', $config->get('status'));
        $this->assertSame('per_order', $config->get('commission_type'));
        $this->assertSame('3.00', $config->get('commission_rate'));
        $this->assertSame('overdue', $config->get('status_message'));
    }

    public function test_sync_status_is_fail_open_on_network_error(): void
    {
        Http::fake(['*/api/v1/agent/status' => fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');
        $config->set('status', 'active'); // last known

        app(AgentClient::class)->syncStatus(); // must not throw

        $this->assertSame('active', $config->get('status')); // unchanged
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=AgentClientTest`
Expected: FAIL (class missing).

- [ ] **Step 3: Write the implementation**

```php
<?php
// <shop>/app/Agent/AgentClient.php
namespace App\Agent;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentClient
{
    public function __construct(private AgentConfig $config) {}

    private function base(): string
    {
        return rtrim((string) $this->config->get('central_url'), '/');
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('token'),
            'X-Agent-Domain' => $this->domain(),
            'Accept' => 'application/json',
        ];
    }

    private function domain(): string
    {
        return (string) ($this->config->get('domain') ?: parse_url(config('app.url'), PHP_URL_HOST));
    }

    public function register(string $businessName, string $email, string $domain): string
    {
        $this->config->set('domain', $domain);

        $res = Http::acceptJson()->post($this->base() . '/api/v1/agent/register', [
            'business_name' => $businessName,
            'contact_email' => $email,
            'domain' => $domain,
            'app_version' => config('app.version', '1.0.0'),
        ])->throw()->json();

        if (! empty($res['token'])) {
            $this->config->set('token', $res['token']);
            $this->config->set('client_id', (string) $res['client_id']);
        }
        $this->config->set('status', $res['status']);

        return $res['status'];
    }

    public function report(CarbonInterface $start, CarbonInterface $end, float $gross, int $orders, string $currency): bool
    {
        try {
            $res = Http::withHeaders($this->authHeaders())
                ->post($this->base() . '/api/v1/agent/report', [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'gross_sales' => $gross,
                    'order_count' => $orders,
                    'currency' => $currency,
                    'app_version' => config('app.version', '1.0.0'),
                ])->throw()->json();

            $this->applyStatusPayload($res);
            return (bool) ($res['accepted'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('Agent report failed (fail-open): ' . $e->getMessage());
            return false;
        }
    }

    public function syncStatus(): void
    {
        try {
            $res = Http::withHeaders($this->authHeaders())
                ->get($this->base() . '/api/v1/agent/status')->throw()->json();
            $this->applyStatusPayload($res);
        } catch (\Throwable $e) {
            Log::warning('Agent status sync failed (fail-open): ' . $e->getMessage());
            // keep last known status
        }
    }

    private function applyStatusPayload(array $res): void
    {
        foreach ([
            'status' => 'status',
            'commission_type' => 'commission_type',
            'commission_rate' => 'commission_rate',
            'message' => 'status_message',
        ] as $from => $to) {
            if (array_key_exists($from, $res)) {
                $this->config->set($to, $res[$from] === null ? null : (string) $res[$from]);
            }
        }
        $this->config->set('last_synced_at', now()->toIso8601String());
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `shop-test --filter=AgentClientTest`
Expected: PASS (all 3 cases, including fail-open).

- [ ] **Step 5: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Agent codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): HTTP client for register/report/status (fail-open)"
```

---

### Task B4: Artisan commands + scheduler wiring

**Files:**
- Create: `<shop>/app/Console/Commands/AgentReport.php`
- Create: `<shop>/app/Console/Commands/AgentSyncStatus.php`
- Modify: `<shop>/app/Console/Kernel.php`
- Test: `<shop>/tests/Agent/AgentReportCommandTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/AgentReportCommandTest.php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AgentReportCommandTest extends AgentTestCase
{
    public function test_report_command_sends_yesterdays_paid_sales(): void
    {
        Http::fake(['*/api/v1/agent/report' => Http::response([
            'accepted' => true, 'status' => 'active', 'commission_type' => 'percent', 'commission_rate' => '10.00',
        ], 200)]);

        $config = app(AgentConfig::class);
        $config->set('central_url', 'https://central.test');
        $config->set('token', 'TKN');
        $config->set('domain', 'a.test');

        $yesterday = now()->subDay()->toDateString();
        DB::table('orders')->insert([
            ['grand_total' => 200, 'payment_status' => 'paid', 'created_at' => $yesterday . ' 09:00:00'],
        ]);

        $this->artisan('agent:report')->assertSuccessful();

        Http::assertSent(function ($request) use ($yesterday) {
            return str_contains($request->url(), '/agent/report')
                && $request['gross_sales'] == 200
                && $request['order_count'] == 1
                && $request['period_start'] === $yesterday;
        });
    }

    public function test_report_command_noops_when_unregistered(): void
    {
        Http::fake();
        $this->artisan('agent:report')->assertSuccessful();
        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=AgentReportCommandTest`
Expected: FAIL (command missing).

- [ ] **Step 3: Write the report command**

```php
<?php
// <shop>/app/Console/Commands/AgentReport.php
namespace App\Console\Commands;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use App\Agent\SalesAggregator;
use Illuminate\Console\Command;

class AgentReport extends Command
{
    protected $signature = 'agent:report';
    protected $description = 'Report yesterday\'s paid sales to the central control plane';

    public function handle(AgentConfig $config, SalesAggregator $aggregator, AgentClient $client): int
    {
        if (! $config->isRegistered()) {
            $this->info('Agent not registered; skipping.');
            return self::SUCCESS;
        }

        $start = now()->subDay()->startOfDay();
        $end = now()->subDay()->endOfDay();
        $totals = $aggregator->forPeriod($start, $end);

        $currency = $config->get('currency', env('DEFAULT_CURRENCY_CODE', 'USD'));
        $accepted = $client->report($start, $end, $totals['gross_sales'], $totals['order_count'], $currency);

        $this->info($accepted ? 'Report accepted.' : 'Report not accepted (held/fail-open).');
        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Write the status-sync command**

```php
<?php
// <shop>/app/Console/Commands/AgentSyncStatus.php
namespace App\Console\Commands;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use Illuminate\Console\Command;

class AgentSyncStatus extends Command
{
    protected $signature = 'agent:sync-status';
    protected $description = 'Fetch latest status + commission config from the control plane';

    public function handle(AgentConfig $config, AgentClient $client): int
    {
        if (! $config->isRegistered()) {
            $this->info('Agent not registered; skipping.');
            return self::SUCCESS;
        }
        $client->syncStatus();
        $this->info('Status: ' . $config->get('status', 'unknown'));
        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Wire the scheduler**

In `<shop>/app/Console/Kernel.php`, inside `schedule(Schedule $schedule)`, add:

```php
$schedule->command('agent:report')->dailyAt('00:30');
$schedule->command('agent:sync-status')->daily();
```

- [ ] **Step 6: Run test to verify it passes**

Run: `shop-test --filter=AgentReportCommandTest`
Expected: PASS (both cases).

- [ ] **Step 7: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Console codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): agent:report + agent:sync-status commands + scheduler"
```

---

### Task B5: Soft-enforcement middleware

**Files:**
- Create: `<shop>/app/Http/Middleware/AgentEnforcement.php`
- Modify: `<shop>/app/Http/Kernel.php` (register alias `agent.enforce`)
- Test: `<shop>/tests/Agent/AgentEnforcementTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/AgentEnforcementTest.php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Support\Facades\Route;

class AgentEnforcementTest extends AgentTestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(['web', 'agent.enforce:admin'])->get('/_admin_probe', fn () => 'ADMIN_OK');
        $router->middleware(['web', 'agent.enforce:storefront'])->get('/_store_probe', fn () => 'STORE_OK');
    }

    public function test_locked_admin_blocks_admin_but_not_storefront(): void
    {
        app(AgentConfig::class)->set('status', 'locked_admin');

        $this->get('/_admin_probe')->assertStatus(503);
        $this->get('/_store_probe')->assertOk()->assertSee('STORE_OK');
    }

    public function test_maintenance_blocks_storefront(): void
    {
        app(AgentConfig::class)->set('status', 'maintenance');
        $this->get('/_store_probe')->assertStatus(503);
    }

    public function test_active_allows_everything(): void
    {
        app(AgentConfig::class)->set('status', 'active');
        $this->get('/_admin_probe')->assertOk();
        $this->get('/_store_probe')->assertOk();
    }

    public function test_warning_allows_but_flags_banner(): void
    {
        app(AgentConfig::class)->set('status', 'warning');
        app(AgentConfig::class)->set('status_message', 'overdue');
        $this->get('/_admin_probe')->assertOk();
        // banner exposed via shared view data
        $this->assertSame('overdue', view()->shared('agent_banner'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=AgentEnforcementTest`
Expected: FAIL (alias/middleware missing).

- [ ] **Step 3: Write the middleware**

```php
<?php
// <shop>/app/Http/Middleware/AgentEnforcement.php
namespace App\Http\Middleware;

use App\Agent\AgentConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AgentEnforcement
{
    public function __construct(private AgentConfig $config) {}

    /** @param string $scope 'admin' | 'storefront' */
    public function handle(Request $request, Closure $next, string $scope = 'storefront')
    {
        $status = $this->config->get('status', 'active');

        // Warning: never blocks; surfaces a banner to views.
        if ($status === 'warning') {
            View::share('agent_banner', $this->config->get('status_message'));
        }

        $blockAdmin = in_array($status, ['locked_admin', 'maintenance'], true);
        $blockStore = $status === 'maintenance';

        if ($scope === 'admin' && $blockAdmin) {
            return $this->blocked($this->config->get('status_message') ?? 'Admin temporarily locked.');
        }
        if ($scope === 'storefront' && $blockStore) {
            return $this->blocked($this->config->get('status_message') ?? 'Store temporarily unavailable.');
        }

        return $next($request);
    }

    private function blocked(string $message)
    {
        return response($message, 503);
    }
}
```

- [ ] **Step 4: Register the alias**

In `<shop>/app/Http/Kernel.php` `$routeMiddleware` (Laravel 9 name) array, add:

```php
'agent.enforce' => \App\Http\Middleware\AgentEnforcement::class,
```

- [ ] **Step 5: Run test to verify it passes**

Run: `shop-test --filter=AgentEnforcementTest`
Expected: PASS.

- [ ] **Step 6: Apply the middleware to real route groups**

In `<shop>/routes/admin.php`, wrap the admin route group with `agent.enforce:admin`. In `<shop>/routes/web.php` (storefront catch-all that serves the SPA), add `agent.enforce:storefront`. Example for admin.php — find the top-level `Route::group([... 'middleware' => [...] ...])` and append `'agent.enforce:admin'` to its middleware array. For the storefront, locate the route returning the SPA view and add `->middleware('agent.enforce:storefront')`.

- [ ] **Step 7: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app codecanyon-34858541-the-shop/install/routes \
        codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): soft-enforcement middleware (warning/locked/maintenance)"
```

---

### Task B6: Admin registration UI

**Files:**
- Create: `<shop>/app/Http/Controllers/Admin/AgentController.php`
- Modify: `<shop>/routes/admin.php`
- Create: `<shop>/resources/views/backend/agent/settings.blade.php`
- Test: `<shop>/tests/Agent/AgentRegistrationUiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// <shop>/tests/Agent/AgentRegistrationUiTest.php
namespace Tests\Agent;

use App\Agent\AgentConfig;
use Illuminate\Support\Facades\Http;

class AgentRegistrationUiTest extends AgentTestCase
{
    public function test_register_action_calls_central_and_stores_status(): void
    {
        Http::fake(['*/api/v1/agent/register' => Http::response([
            'client_id' => 9, 'token' => 'TKN', 'status' => 'pending',
        ], 201)]);

        // hit the controller action directly (avoids full admin-auth stack in this unit-style test)
        $controller = app(\App\Http\Controllers\Admin\AgentController::class);
        $request = \Illuminate\Http\Request::create('/admin/agent', 'POST', [
            'central_url' => 'https://central.test',
            'business_name' => 'Acme',
            'contact_email' => 'a@acme.test',
            'domain' => 'acme.test',
        ]);

        $controller->register($request, app(\App\Agent\AgentClient::class), app(AgentConfig::class));

        $config = app(AgentConfig::class);
        $this->assertSame('pending', $config->get('status'));
        $this->assertSame('TKN', $config->get('token'));
        $this->assertSame('https://central.test', $config->get('central_url'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `shop-test --filter=AgentRegistrationUiTest`
Expected: FAIL (controller missing).

- [ ] **Step 3: Write the controller**

```php
<?php
// <shop>/app/Http/Controllers/Admin/AgentController.php
namespace App\Http\Controllers\Admin;

use App\Agent\AgentClient;
use App\Agent\AgentConfig;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function settings(AgentConfig $config)
    {
        return view('backend.agent.settings', [
            'status' => $config->get('status', 'unregistered'),
            'central_url' => $config->get('central_url'),
            'commission_type' => $config->get('commission_type'),
            'commission_rate' => $config->get('commission_rate'),
            'last_synced_at' => $config->get('last_synced_at'),
        ]);
    }

    public function register(Request $request, AgentClient $client, AgentConfig $config)
    {
        $data = $request->validate([
            'central_url' => 'required|url',
            'business_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'domain' => 'required|string|max:255',
        ]);

        $config->set('central_url', rtrim($data['central_url'], '/'));
        $client->register($data['business_name'], $data['contact_email'], $data['domain']);

        return redirect()->back()->with('success', 'Registered with control plane. Awaiting approval.');
    }

    public function sync(AgentClient $client)
    {
        $client->syncStatus();
        return redirect()->back()->with('success', 'Status synced.');
    }
}
```

- [ ] **Step 4: Add routes**

In `<shop>/routes/admin.php`, inside the authenticated admin group, add:

```php
use App\Http\Controllers\Admin\AgentController;

Route::get('agent', [AgentController::class, 'settings'])->name('agent.settings');
Route::post('agent/register', [AgentController::class, 'register'])->name('agent.register');
Route::post('agent/sync', [AgentController::class, 'sync'])->name('agent.sync');
```

- [ ] **Step 5: Write the Blade view**

```blade
{{-- <shop>/resources/views/backend/agent/settings.blade.php --}}
@extends('backend.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-3"><h1 class="h3">Platform Connection</h1></div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card"><div class="card-body">
    <p><strong>Status:</strong> {{ $status }}
       @if($last_synced_at)<small class="text-muted">(synced {{ $last_synced_at }})</small>@endif</p>
    @if($commission_type)
        <p><strong>Commission:</strong> {{ $commission_type }} {{ $commission_rate }}</p>
    @endif

    <form method="POST" action="{{ route('agent.register') }}">
        @csrf
        <div class="form-group"><label>Central URL</label>
            <input class="form-control" name="central_url" value="{{ $central_url }}" placeholder="https://central.example.com" required></div>
        <div class="form-group"><label>Business name</label>
            <input class="form-control" name="business_name" required></div>
        <div class="form-group"><label>Contact email</label>
            <input class="form-control" type="email" name="contact_email" required></div>
        <div class="form-group"><label>Your store domain</label>
            <input class="form-control" name="domain" value="{{ parse_url(config('app.url'), PHP_URL_HOST) }}" required></div>
        <button class="btn btn-primary" type="submit">Register</button>
    </form>

    @if($status !== 'unregistered')
    <form method="POST" action="{{ route('agent.sync') }}" class="mt-2">
        @csrf <button class="btn btn-secondary" type="submit">Sync now</button>
    </form>
    @endif
</div></div>
@endsection
```

- [ ] **Step 6: Run test to verify it passes**

Run: `shop-test --filter=AgentRegistrationUiTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add codecanyon-34858541-the-shop/install/app/Http/Controllers/Admin/AgentController.php \
        codecanyon-34858541-the-shop/install/routes/admin.php \
        codecanyon-34858541-the-shop/install/resources/views/backend/agent \
        codecanyon-34858541-the-shop/install/tests/Agent
git commit -m "feat(shop/agent): admin registration + sync UI"
```

---

## Phase C — End-to-End Smoke Test

### Task C1: Manual end-to-end verification

**Files:**
- Create: `docs/superpowers/plans/control-plane-e2e-checklist.md` (record results)

- [ ] **Step 1: Run the central app and migrate**

```bash
cd /home/tanmoy/Projects/Shop/theshop/control-plane
# point .env DB at the MariaDB container or local sqlite file; create the super-admin user:
cp-art migrate
cp-art tinker --execute="\App\Models\User::factory()->create(['email'=>'super@admin.test','password'=>bcrypt('password')]);"
# serve on :9000
docker compose -f ../codecanyon-34858541-the-shop/install/docker-compose.yml run --rm -p 9000:9000 \
  -v "$PWD/..":/work -w /work/control-plane app php artisan serve --host=0.0.0.0 --port=9000
```

- [ ] **Step 2: Migrate the agent table in The Shop**

```bash
cd /home/tanmoy/Projects/Shop/theshop/codecanyon-34858541-the-shop/install
docker compose exec -T app php artisan migrate
```

- [ ] **Step 3: Register from The Shop admin**

In the store admin, open **Platform Connection**, enter Central URL `http://host.docker.internal:9000` (or the reachable central URL), business details, and click **Register**. Confirm status shows **pending**.

- [ ] **Step 4: Approve + set commission in central**

Log into the central app at `:9000`, open **Clients**, find the pending client, **Approve**, set commission `percent 10`, status `active`.

- [ ] **Step 5: Report + verify**

```bash
cd /home/tanmoy/Projects/Shop/theshop/codecanyon-34858541-the-shop/install
docker compose exec -T app php artisan agent:sync-status   # pulls active + commission
docker compose exec -T app php artisan agent:report        # sends yesterday's paid sales
```

Confirm in the central dashboard: the client shows the reported gross + computed commission (10%). Then set status to `maintenance` in central, run `agent:sync-status` again, and confirm the storefront returns the maintenance response while admin behaves per `locked_admin`/`maintenance` rules.

- [ ] **Step 6: Record results + commit**

Write pass/fail notes into `docs/superpowers/plans/control-plane-e2e-checklist.md` and:

```bash
cd /home/tanmoy/Projects/Shop/theshop
git add docs/superpowers/plans/control-plane-e2e-checklist.md
git commit -m "test: control-plane end-to-end smoke results"
```

---

## Notes for the Implementer

- **Fail-open is a hard requirement:** any network/HTTP error in `AgentClient` must be swallowed and logged; a client's store must never break because central is unreachable.
- **No PII leaves the client:** only `gross_sales`, `order_count`, `currency`, `app_version`, and `period` are sent. Do not add order/customer fields.
- **Idempotency:** reports are keyed by `(client_id, period_start, period_end)`; re-sending a period overwrites, never duplicates.
- **The Shop is Laravel 9** (`$routeMiddleware` in Kernel) while **central is Laravel 10** (`$middlewareAliases`). Use the right property name in each app.
- **Deferred to billing sub-project:** automatic `warning → locked_admin → maintenance` progression tied to unpaid commission + grace period; the `grace_until` field already exists in the status contract for that.
```
