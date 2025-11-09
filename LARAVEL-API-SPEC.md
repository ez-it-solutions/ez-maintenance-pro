# Ez IT Solutions - Laravel Licensing API Specification

Complete API specification for building the licensing server in Laravel.

**Base URL:** `https://licensing.ez-it-solutions.com/api/v1`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Database Schema](#database-schema)
3. [API Endpoints](#api-endpoints)
4. [Laravel Implementation](#laravel-implementation)
5. [Security Considerations](#security-considerations)
6. [Testing](#testing)

---

## Authentication

### API Key Authentication

All requests from WordPress plugins include an internal API key for server-to-server communication.

**Header:**
```
X-API-Secret: your_internal_api_secret
```

**Laravel Middleware:**
```php
// app/Http/Middleware/ValidateApiSecret.php
public function handle($request, Closure $next)
{
    $secret = $request->header('X-API-Secret');
    
    if ($secret !== config('licensing.api_secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return $next($request);
}
```

---

## Database Schema

### Tables Required

#### 1. `licenses` Table

```php
Schema::create('licenses', function (Blueprint $table) {
    $table->id();
    $table->string('license_key', 50)->unique();
    $table->string('email')->index();
    $table->string('product_id', 50)->index(); // 'ez-maintenance-pro', 'ez-client-manager', etc.
    $table->enum('plan', ['free', 'pro', 'business'])->default('free');
    $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
    $table->integer('max_activations')->default(1);
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('last_verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['product_id', 'status']);
});
```

#### 2. `license_activations` Table

```php
Schema::create('license_activations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->onDelete('cascade');
    $table->string('site_url');
    $table->string('site_ip')->nullable();
    $table->string('wp_version', 20)->nullable();
    $table->string('plugin_version', 20)->nullable();
    $table->string('php_version', 20)->nullable();
    $table->timestamp('activated_at');
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamps();
    
    $table->unique(['license_id', 'site_url']);
    $table->index('site_url');
});
```

#### 3. `license_logs` Table

```php
Schema::create('license_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->onDelete('cascade');
    $table->string('action', 50); // 'activated', 'deactivated', 'verified', 'expired'
    $table->string('site_url')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('details')->nullable(); // JSON data
    $table->timestamp('created_at');
    
    $table->index(['license_id', 'action']);
    $table->index('created_at');
});
```

#### 4. `products` Table

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('product_id', 50)->unique();
    $table->string('name');
    $table->text('description')->nullable();
    $table->boolean('active')->default(true);
    $table->json('features')->nullable(); // Feature list per plan
    $table->timestamps();
});
```

---

## API Endpoints

### 1. Activate License

**Endpoint:** `POST /api/v1/activate`

**Purpose:** Activate a license key for a specific site.

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "email": "customer@example.com",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro",
  "wp_version": "6.4",
  "plugin_version": "1.0.0",
  "php_version": "8.1"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "status": "active",
  "plan": "pro",
  "expires_at": "2025-12-31 23:59:59",
  "message": "License activated successfully",
  "features": ["premium_templates", "api_access", "countdown_timer"]
}
```

**Error Responses:**

**Invalid License (400):**
```json
{
  "success": false,
  "message": "Invalid license key"
}
```

**Already Activated (400):**
```json
{
  "success": false,
  "message": "License already activated on maximum number of sites",
  "current_activations": 1,
  "max_activations": 1
}
```

**Expired License (400):**
```json
{
  "success": false,
  "message": "License has expired",
  "expired_at": "2024-12-31 23:59:59"
}
```

---

### 2. Verify License

**Endpoint:** `POST /api/v1/verify`

**Purpose:** Verify license status and update last checked timestamp.

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "status": "active",
  "plan": "pro",
  "expires_at": "2025-12-31 23:59:59",
  "features": ["premium_templates", "api_access", "countdown_timer"]
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "License not found or inactive",
  "status": "expired"
}
```

---

### 3. Deactivate License

**Endpoint:** `POST /api/v1/deactivate`

**Purpose:** Deactivate license from a specific site.

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://example.com",
  "product_id": "ez-maintenance-pro"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "License deactivated successfully"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "License not found for this site"
}
```

---

### 4. Get License Info (Admin)

**Endpoint:** `GET /api/v1/licenses/{license_key}`

**Purpose:** Get detailed license information (admin only).

**Response:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "email": "customer@example.com",
  "product_id": "ez-maintenance-pro",
  "plan": "pro",
  "status": "active",
  "max_activations": 1,
  "current_activations": 1,
  "expires_at": "2025-12-31 23:59:59",
  "created_at": "2024-01-01 00:00:00",
  "activations": [
    {
      "site_url": "https://example.com",
      "activated_at": "2024-01-15 10:30:00",
      "last_checked_at": "2024-01-20 14:22:00",
      "wp_version": "6.4",
      "plugin_version": "1.0.0"
    }
  ]
}
```

---

### 5. Create License (Admin)

**Endpoint:** `POST /api/v1/licenses`

**Purpose:** Create a new license (admin dashboard).

**Request:**
```json
{
  "email": "customer@example.com",
  "product_id": "ez-maintenance-pro",
  "plan": "pro",
  "max_activations": 1,
  "expires_at": "2025-12-31"
}
```

**Response:**
```json
{
  "success": true,
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "message": "License created successfully"
}
```

---

### 6. Update License (Admin)

**Endpoint:** `PUT /api/v1/licenses/{license_key}`

**Purpose:** Update license details.

**Request:**
```json
{
  "plan": "business",
  "max_activations": 5,
  "expires_at": "2026-12-31",
  "status": "active"
}
```

---

### 7. Get Statistics (Admin)

**Endpoint:** `GET /api/v1/stats`

**Response:**
```json
{
  "total_licenses": 1250,
  "active_licenses": 980,
  "expired_licenses": 200,
  "total_activations": 1150,
  "by_product": {
    "ez-maintenance-pro": 450,
    "ez-client-manager": 530,
    "ez-dh-sso": 270
  },
  "by_plan": {
    "free": 300,
    "pro": 650,
    "business": 300
  }
}
```

---

## Laravel Implementation

### Models

#### License Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class License extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'license_key',
        'email',
        'product_id',
        'plan',
        'status',
        'max_activations',
        'expires_at',
        'last_verified_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($license) {
            if (empty($license->license_key)) {
                $license->license_key = self::generateLicenseKey();
            }
        });
    }

    public static function generateLicenseKey()
    {
        do {
            $key = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (self::where('license_key', $key)->exists());
        
        return $key;
    }

    public function activations()
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function logs()
    {
        return $this->hasMany(LicenseLog::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function isActive()
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function canActivate()
    {
        return $this->isActive() && 
               $this->activations()->count() < $this->max_activations;
    }

    public function isActivatedOn($siteUrl)
    {
        return $this->activations()
            ->where('site_url', $siteUrl)
            ->exists();
    }
}
```

#### LicenseActivation Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseActivation extends Model
{
    protected $fillable = [
        'license_id',
        'site_url',
        'site_ip',
        'wp_version',
        'plugin_version',
        'php_version',
        'activated_at',
        'last_checked_at'
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
```

---

### Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\LicenseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    /**
     * Activate License
     */
    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'email' => 'required|email',
            'site_url' => 'required|url',
            'product_id' => 'required|string',
            'wp_version' => 'nullable|string',
            'plugin_version' => 'nullable|string',
            'php_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $license = License::where('license_key', $request->license_key)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid license key'
            ], 400);
        }

        if (!$license->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'License has expired or is inactive',
                'status' => $license->status,
                'expired_at' => $license->expires_at
            ], 400);
        }

        // Check if already activated on this site
        if ($license->isActivatedOn($request->site_url)) {
            // Update existing activation
            $activation = $license->activations()
                ->where('site_url', $request->site_url)
                ->first();
                
            $activation->update([
                'wp_version' => $request->wp_version,
                'plugin_version' => $request->plugin_version,
                'php_version' => $request->php_version,
                'last_checked_at' => now()
            ]);
        } else {
            // Check activation limit
            if (!$license->canActivate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'License already activated on maximum number of sites',
                    'current_activations' => $license->activations()->count(),
                    'max_activations' => $license->max_activations
                ], 400);
            }

            // Create new activation
            LicenseActivation::create([
                'license_id' => $license->id,
                'site_url' => $request->site_url,
                'site_ip' => $request->ip(),
                'wp_version' => $request->wp_version,
                'plugin_version' => $request->plugin_version,
                'php_version' => $request->php_version,
                'activated_at' => now(),
                'last_checked_at' => now()
            ]);
        }

        // Log activation
        LicenseLog::create([
            'license_id' => $license->id,
            'action' => 'activated',
            'site_url' => $request->site_url,
            'ip_address' => $request->ip(),
            'details' => json_encode($request->only(['wp_version', 'plugin_version', 'php_version'])),
            'created_at' => now()
        ]);

        $license->update(['last_verified_at' => now()]);

        return response()->json([
            'success' => true,
            'status' => $license->status,
            'plan' => $license->plan,
            'expires_at' => $license->expires_at,
            'message' => 'License activated successfully',
            'features' => $this->getFeatures($license->plan)
        ]);
    }

    /**
     * Verify License
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'site_url' => 'required|url',
            'product_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed'
            ], 400);
        }

        $license = License::where('license_key', $request->license_key)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$license || !$license->isActivatedOn($request->site_url)) {
            return response()->json([
                'success' => false,
                'message' => 'License not found or not activated on this site'
            ], 400);
        }

        // Update last checked timestamp
        $activation = $license->activations()
            ->where('site_url', $request->site_url)
            ->first();
            
        $activation->update(['last_checked_at' => now()]);
        $license->update(['last_verified_at' => now()]);

        // Log verification
        LicenseLog::create([
            'license_id' => $license->id,
            'action' => 'verified',
            'site_url' => $request->site_url,
            'ip_address' => $request->ip(),
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'status' => $license->isActive() ? 'active' : $license->status,
            'plan' => $license->plan,
            'expires_at' => $license->expires_at,
            'features' => $this->getFeatures($license->plan)
        ]);
    }

    /**
     * Deactivate License
     */
    public function deactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'site_url' => 'required|url',
            'product_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed'
            ], 400);
        }

        $license = License::where('license_key', $request->license_key)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'message' => 'License not found'
            ], 400);
        }

        $activation = $license->activations()
            ->where('site_url', $request->site_url)
            ->first();

        if (!$activation) {
            return response()->json([
                'success' => false,
                'message' => 'License not activated on this site'
            ], 400);
        }

        $activation->delete();

        // Log deactivation
        LicenseLog::create([
            'license_id' => $license->id,
            'action' => 'deactivated',
            'site_url' => $request->site_url,
            'ip_address' => $request->ip(),
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'License deactivated successfully'
        ]);
    }

    /**
     * Get features for plan
     */
    private function getFeatures($plan)
    {
        $features = [
            'free' => ['basic_templates', 'color_customization', 'basic_access_control'],
            'pro' => ['basic_templates', 'color_customization', 'basic_access_control', 'premium_templates', 'countdown_timer', 'social_links', 'custom_css', 'api_access'],
            'business' => ['basic_templates', 'color_customization', 'basic_access_control', 'premium_templates', 'countdown_timer', 'social_links', 'custom_css', 'api_access', 'white_label', 'priority_support', 'multisite']
        ];

        return $features[$plan] ?? $features['free'];
    }
}
```

---

### Routes

```php
// routes/api.php

use App\Http\Controllers\Api\LicenseController;

Route::prefix('v1')->middleware('api.secret')->group(function () {
    Route::post('/activate', [LicenseController::class, 'activate']);
    Route::post('/verify', [LicenseController::class, 'verify']);
    Route::post('/deactivate', [LicenseController::class, 'deactivate']);
    
    // Admin routes (add auth middleware)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/licenses/{license_key}', [LicenseController::class, 'show']);
        Route::post('/licenses', [LicenseController::class, 'store']);
        Route::put('/licenses/{license_key}', [LicenseController::class, 'update']);
        Route::get('/stats', [LicenseController::class, 'stats']);
    });
});
```

---

### Configuration

```php
// config/licensing.php

return [
    'api_secret' => env('LICENSING_API_SECRET', 'your-secret-key-here'),
    
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'max_activations' => 1,
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 49,
            'max_activations' => 1,
        ],
        'business' => [
            'name' => 'Business',
            'price' => 149,
            'max_activations' => 5,
        ],
    ],
];
```

---

## Security Considerations

1. **Rate Limiting:** Apply rate limits to prevent abuse
2. **IP Whitelisting:** Optional IP restrictions for admin endpoints
3. **HTTPS Only:** Enforce SSL/TLS
4. **Input Validation:** Validate all inputs
5. **SQL Injection:** Use Eloquent ORM (parameterized queries)
6. **Logging:** Log all license operations
7. **Monitoring:** Alert on suspicious activity

---

## Testing

### PHPUnit Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LicenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_activate_license()
    {
        $license = License::factory()->create([
            'plan' => 'pro',
            'status' => 'active',
            'max_activations' => 1
        ]);

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->license_key,
            'email' => $license->email,
            'site_url' => 'https://example.com',
            'product_id' => $license->product_id,
        ], [
            'X-API-Secret' => config('licensing.api_secret')
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'plan' => 'pro'
            ]);
    }

    public function test_cannot_activate_expired_license()
    {
        $license = License::factory()->create([
            'status' => 'expired'
        ]);

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->license_key,
            'email' => $license->email,
            'site_url' => 'https://example.com',
            'product_id' => $license->product_id,
        ], [
            'X-API-Secret' => config('licensing.api_secret')
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }
}
```

---

## Deployment Checklist

- [ ] Set up Laravel application on server
- [ ] Configure database (MySQL/PostgreSQL)
- [ ] Run migrations
- [ ] Set `LICENSING_API_SECRET` in `.env`
- [ ] Configure SSL certificate
- [ ] Set up queue workers for async tasks
- [ ] Configure logging and monitoring
- [ ] Set up automated backups
- [ ] Configure rate limiting
- [ ] Test all endpoints
- [ ] Set up admin dashboard (Laravel Nova/Filament)

---

**Built for Ez IT Solutions | Chris Hultberg**
