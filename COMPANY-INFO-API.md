# Ez IT Solutions - Company Info API Specification

API specification for the Laravel endpoint that provides company information to WordPress plugins.

**Base URL:** `https://www.ez-it-solutions.com/api/v1`

---

## Endpoint: Get Company Info

**URL:** `GET /api/v1/company-info`

**Purpose:** Provide company information and plugin updates to all Ez IT WordPress plugins.

**Authentication:** None required (public endpoint)

**Response Format:** JSON

---

## Response Structure

### Success Response (200 OK)

```json
{
  "success": true,
  "data": {
    "name": "Ez IT Solutions",
    "tagline": "Professional WordPress Solutions",
    "description": "We build premium WordPress plugins and provide comprehensive IT solutions for businesses of all sizes.",
    "website": "https://www.ez-it-solutions.com",
    "email": "chrishultberg@ez-it-solutions.com",
    "phone": "+1 (555) 123-4567",
    "logo": "https://www.ez-it-solutions.com/images/logo.png",
    "social": {
      "facebook": "https://facebook.com/ezitsolutions",
      "twitter": "https://twitter.com/ezitsolutions",
      "linkedin": "https://linkedin.com/company/ez-it-solutions",
      "github": "https://github.com/ez-it-solutions"
    },
    "support_url": "https://www.ez-it-solutions.com/support",
    "docs_url": "https://www.ez-it-solutions.com/docs",
    "products": [
      {
        "id": "ez-maintenance-pro",
        "name": "Ez Maintenance Pro",
        "description": "Professional maintenance mode plugin with beautiful templates",
        "version": "1.0.0",
        "download_url": "https://www.ez-it-solutions.com/downloads/ez-maintenance-pro",
        "changelog_url": "https://www.ez-it-solutions.com/changelog/ez-maintenance-pro",
        "requires_wp": "5.8",
        "requires_php": "7.4",
        "tested_up_to": "6.4"
      },
      {
        "id": "ez-client-manager",
        "name": "Ez IT Client Manager",
        "description": "Complete client management and monitoring solution",
        "version": "1.0.0",
        "download_url": "https://www.ez-it-solutions.com/downloads/ez-client-manager",
        "changelog_url": "https://www.ez-it-solutions.com/changelog/ez-client-manager",
        "requires_wp": "5.8",
        "requires_php": "7.4",
        "tested_up_to": "6.4"
      }
    ],
    "announcements": [
      {
        "id": 1,
        "title": "New Feature: Advanced Templates",
        "message": "Check out our new premium templates in Ez Maintenance Pro!",
        "type": "info",
        "link": "https://www.ez-it-solutions.com/blog/new-templates",
        "expires_at": "2025-12-31"
      }
    ]
  }
}
```

### Error Response (500)

```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Laravel Implementation

### Route

**`routes/api.php`:**
```php
Route::get('/v1/company-info', [CompanyInfoController::class, 'index']);
```

### Controller

**`app/Http/Controllers/Api/CompanyInfoController.php`:**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    /**
     * Get company information
     */
    public function index()
    {
        $info = [
            'name' => config('company.name'),
            'tagline' => config('company.tagline'),
            'description' => config('company.description'),
            'website' => config('app.url'),
            'email' => config('company.email'),
            'phone' => config('company.phone'),
            'logo' => asset('images/logo.png'),
            'social' => [
                'facebook' => config('company.social.facebook'),
                'twitter' => config('company.social.twitter'),
                'linkedin' => config('company.social.linkedin'),
                'github' => config('company.social.github'),
            ],
            'support_url' => config('company.support_url'),
            'docs_url' => config('company.docs_url'),
            'products' => $this->getProducts(),
            'announcements' => $this->getActiveAnnouncements(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $info
        ]);
    }
    
    /**
     * Get products list
     */
    private function getProducts()
    {
        // This could come from a database
        return [
            [
                'id' => 'ez-maintenance-pro',
                'name' => 'Ez Maintenance Pro',
                'description' => 'Professional maintenance mode plugin with beautiful templates',
                'version' => '1.0.0',
                'download_url' => url('/downloads/ez-maintenance-pro'),
                'changelog_url' => url('/changelog/ez-maintenance-pro'),
                'requires_wp' => '5.8',
                'requires_php' => '7.4',
                'tested_up_to' => '6.4'
            ],
            [
                'id' => 'ez-client-manager',
                'name' => 'Ez IT Client Manager',
                'description' => 'Complete client management and monitoring solution',
                'version' => '1.0.0',
                'download_url' => url('/downloads/ez-client-manager'),
                'changelog_url' => url('/changelog/ez-client-manager'),
                'requires_wp' => '5.8',
                'requires_php' => '7.4',
                'tested_up_to' => '6.4'
            ]
        ];
    }
    
    /**
     * Get active announcements
     */
    private function getActiveAnnouncements()
    {
        // This could come from a database
        // Filter by expiration date
        return [];
    }
}
```

### Configuration

**`config/company.php`:**
```php
<?php

return [
    'name' => env('COMPANY_NAME', 'Ez IT Solutions'),
    'tagline' => env('COMPANY_TAGLINE', 'Professional WordPress Solutions'),
    'description' => env('COMPANY_DESCRIPTION', 'We build premium WordPress plugins and provide comprehensive IT solutions for businesses of all sizes.'),
    'email' => env('COMPANY_EMAIL', 'chrishultberg@ez-it-solutions.com'),
    'phone' => env('COMPANY_PHONE', ''),
    
    'social' => [
        'facebook' => env('COMPANY_FACEBOOK', ''),
        'twitter' => env('COMPANY_TWITTER', ''),
        'linkedin' => env('COMPANY_LINKEDIN', ''),
        'github' => env('COMPANY_GITHUB', 'https://github.com/ez-it-solutions'),
    ],
    
    'support_url' => env('COMPANY_SUPPORT_URL', 'https://www.ez-it-solutions.com/support'),
    'docs_url' => env('COMPANY_DOCS_URL', 'https://www.ez-it-solutions.com/docs'),
];
```

**`.env` additions:**
```env
COMPANY_NAME="Ez IT Solutions"
COMPANY_TAGLINE="Professional WordPress Solutions"
COMPANY_DESCRIPTION="We build premium WordPress plugins and provide comprehensive IT solutions for businesses."
COMPANY_EMAIL="chrishultberg@ez-it-solutions.com"
COMPANY_PHONE=""
COMPANY_FACEBOOK=""
COMPANY_TWITTER=""
COMPANY_LINKEDIN=""
COMPANY_GITHUB="https://github.com/ez-it-solutions"
COMPANY_SUPPORT_URL="https://www.ez-it-solutions.com/support"
COMPANY_DOCS_URL="https://www.ez-it-solutions.com/docs"
```

---

## Database Schema (Optional)

If you want to manage products and announcements in a database:

### Products Table

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('product_id')->unique();
    $table->string('name');
    $table->text('description');
    $table->string('version');
    $table->string('download_url');
    $table->string('changelog_url');
    $table->string('requires_wp');
    $table->string('requires_php');
    $table->string('tested_up_to');
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

### Announcements Table

```php
Schema::create('announcements', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('message');
    $table->enum('type', ['info', 'warning', 'success', 'error'])->default('info');
    $table->string('link')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

---

## WordPress Plugin Integration

### How Plugins Use This API

1. **Initial Load:** Plugin checks cache (24-hour transient)
2. **Cache Miss:** Makes HTTP request to API endpoint
3. **Parse Response:** Extracts company info and product data
4. **Store Cache:** Saves to WordPress transient
5. **Display:** Renders company info page

### Caching Strategy

- **Cache Duration:** 24 hours
- **Cache Key:** `ezit_company_info`
- **Manual Refresh:** Admin can click "Refresh Info" button
- **Fallback:** If API fails, use hardcoded defaults

### Benefits

✅ **Centralized Updates:** Update company info once, reflects everywhere  
✅ **Plugin Updates:** Notify users of new versions  
✅ **Announcements:** Push important messages to all installations  
✅ **Branding:** Consistent company information across all plugins  
✅ **Offline Support:** Graceful fallback to defaults if API unavailable

---

## Testing

### Test Endpoint

```bash
curl https://www.ez-it-solutions.com/api/v1/company-info
```

### Expected Response

Should return JSON with company information and product list.

### Error Handling

- **Timeout:** WordPress plugins will use cached data or defaults
- **Invalid JSON:** Plugins will use defaults
- **500 Error:** Plugins will use defaults

---

## Security Considerations

1. **Rate Limiting:** Apply rate limits to prevent abuse
2. **CORS:** Configure if needed for browser requests
3. **Caching:** Use Laravel cache to reduce database queries
4. **Monitoring:** Track API usage and errors

---

## Future Enhancements

- **Plugin Update Notifications:** Check for new versions
- **License Validation:** Integrate with licensing system
- **Analytics:** Track plugin installations and usage
- **A/B Testing:** Test different announcements
- **Targeted Messages:** Show different content based on plugin version

---

**Built for Ez IT Solutions | Chris Hultberg**
