# API Documentation — LAP Backend (Laravel)

Base URL: `http://localhost:8000/api`

> **Response Format**
> ```json
> { "success": true/false, "message": "...", "data": {..}, "status": 200/401/404 }
> ```

> **Authentication**: `Authorization: Bearer <token>` (JWT, guard `api` for admins, `customer` for customers)

> **Shared Pagination Pattern** (used in most index methods)
> Query params: `page=1`, `raw=1` (returns raw resource)
> ```json
> {
>   "data": [...],
>   "meta": { "current_page": 1, "last_page": 5, "per_page": 10, "total": 50, "from": 1, "to": 10 },
>   "links": { "first": "...", "last": "...", "prev": "...", "next": "..." }
> }
> ```

---

## Table of Contents

- [1. Shared Resources](#1-shared-resources)
- [2. Admin Authentication](#2-admin-authentication)
- [3. Admin CRUDs](#3-admin-cruds)
- [4. Admin Products](#4-admin-products)
- [5. Admin Categories](#5-admin-categories)
- [6. Admin Brands](#6-admin-brands)
- [7. Admin Promotions](#7-admin-promotions)
- [8. Admin Banners](#8-admin-banners)
- [9. Admin Projects](#9-admin-projects)
- [10. Admin Catalogs](#10-admin-catalogs)
- [11. Admin Press Releases](#11-admin-press-releases)
- [12. Admin Logos](#12-admin-logos)
- [13. Admin Materials (Made)](#13-admin-materials-made)
- [14. Admin Orders](#14-admin-orders)
- [15. Admin Transactions](#15-admin-transactions)
- [16. Admin Customers](#16-admin-customers)
- [17. Admin Contacts](#17-admin-contacts)
- [18. Admin Dashboard](#18-admin-dashboard)
- [19. Admin Roles](#19-admin-roles)
- [20. Customer Authentication](#20-customer-authentication)
- [21. Customer Products](#21-customer-products)
- [22. Customer Projects](#22-customer-projects)
- [23. Customer Press Releases](#23-customer-press-releases)
- [24. Customer Parts & Filters](#24-customer-parts--filters)
- [25. Customer Home Banner](#25-customer-home-banner)
- [26. Customer Promotion](#26-customer-promotion)
- [27. Customer Contact Us](#27-customer-contact-us)
- [28. Customer Email Subscriber](#28-customer-email-subscriber)
- [29. Customer Orders](#29-customer-orders)
- [30. Customer Transactions](#30-customer-transactions)
- [31. Customer Addresses & Regions](#31-customer-addresses--regions)
- [32. Customer Data](#32-customer-data)

---

## 1. Shared Resources

| Type | Description |
|------|-------------|
| **Error Response** | `{"success": false, "error": "msg", "message": "..", "status": 401}` |
| **Image Fields** | All file/image fields: raw relative paths stored in `storage/app/public/{dir}/`. Use `asset('storage/{path}')` to render. |
| **MIME Validation** | `FileUploadService` enforces: JPEG/PNG/WebP/GIF/SVG, max 5 MB, blocked `.exe/.sh/.php/...` extensions, magic-byte verification. Controllers also have validator rules. |
| **Status Values** | `"Active"/"Non Active"` (Banners), `"active"/"inactive"` (Products/Promotions) |

---

## 2. Admin Authentication

Prefix: `/admins`

| Method | Endpoint | Auth | Body |
|--------|----------|------|------|
| `POST` | `/admins/login` | ❌ | `{ email, password }` |
| `GET` | `/admins/check-route` | ❌ | — |
| `POST` | `/admins/forgot_password` | ❌ | `{ email }` |
| `POST` | `/admins/reset_password` | ❌ | `{ token, email, password, password_confirmation }` |
| `GET` | `/admins/active-logo` | ❌ | — |

### POST `/admins/login`

**Request Body**
```json
{ "email": "admin@example.com", "password": "secret" }
```

**Success Response** `200`
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "id": 1,
    "name": "John",
    "email": "john@example.com",
    "role": [{ "id": 1, "name": "Superadmin" }],
    "menus": [{ "id": 1, "name": "Dashboard", "route": "/dashboard" }],
    "token": "eyJ0eXAi..."
  },
  "status": 200
}
```

**Error Response** `401`
```json
{ "success": false, "error": "Unauthorized", "message": "Email atau password salah", "status": 401 }
```

---

## 3. Admin CRUDs (Protected: jwt.auth)

Prefix: `/admins/*`

### GET `/admins/me`

Get current admin profile.

### POST `/admins/change-password`

Change admin password.

**Body**
```json
{ "old_password": "...", "new_password": "...", "confirm_password": "..." }
```

### GET `/admins/adminEdit/{id}`

Get admin edit form with role info.

### DELETE `/admins/logout`

Invalidate JWT token.

---

### Admin Resource: `/admins/admin`

Full CRUD via `AdminController`.

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/admin` |
| `GET` | `/admins/admin/{id}` |
| `POST` | `/admins/admin` |
| `PUT/PATCH` | `/admins/admin/{id}` |
| `DELETE` | `/admins/admin/{id}` |

#### GET `/admins/admin` — List all admins

**Query Params** (all optional): `page`, `name`, `email`, `raw`

```json
{
  "success": true,
  "data": {
    "data": [...],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 10, "total": 3, "from": 1, "to": 3 },
    "links": { "first": "...", "last": "...", "prev": null, "next": null }
  }
}
```

#### POST `/admins/admin` — Create admin

**Body**
```json
{
  "name": "New Admin",
  "email": "newadmin@example.com",
  "password": "secret123",
  "status": "active",
  "role_id": 1
}
```

#### PUT `/admins/admin/{id}` — Update admin

**Body**
```json
{
  "name": "Updated",
  "email": "updated@example.com",
  "status": "active",
  "password": "newpass123",
  "role_id": 1
}
```

> Note: `role_id` used via `roles()->sync($request->role_id)`

#### GET `/admins/get-role` — Get roles

Returns all roles for dropdowns.

```json
{ "success": true, "data": [{ "id": 1, "name": "Superadmin" }] }
```

---

## 4. Admin Products (Protected: jwt.auth)

Prefix: `/admins/product`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/product` |
| `GET` | `/admins/product/{id}` |
| `POST` | `/admins/product` |
| `PUT/PATCH` | `/admins/product/{id}` |
| `DELETE` | `/admins/product/{id}` |
| `GET` | `/admins/edit-product/{id}` |
| `GET` | `/admins/get-category` |
| `GET` | `/admins/get-brand` |
| `GET` | `/admins/get-material` |

### GET `/admins/product` — List products

**Query Params**: `page`, `name`, `sku_product`, `color`, `brand_name`, `category_name`, `status`, `raw`

### POST `/admins/product` — Create product

**Body**
```json
{
  "name": "Product Name",
  "brand_id": 1,
  "category_id": 1,
  "description": "Description",
  "stock_type": "Ready",
  "color": "Red",
  "made_ids": [1, 2],
  "status": "active",
  "image1": <file>,
  "image2": <file>,
  "image3": <file>,
  "image4": <file>,
  "image5": <file>,
  "image6": <file>,
  "color_product": ["Red", "Blue"]
}
```

> File validation: `mimes:jpeg,png,jpg`, max 5 MB per image. Stored via `FileUploadService`.

### GET `/admins/edit-product/{id}` — Pre-fill for editing

Returns product with asset URLs for all images (`asset('storage/{path}')`). Also returns `made_ids` array.

### GET `/admins/get-category`

```json
{ "success": true, "data": [{ "id": 1, "name": "Chair" }] }
```

### GET `/admins/get-brand`

```json
{ "success": true, "data": [{ "id": 1, "name": "IKEA" }] }
```

### GET `/admins/get-material`

Returns all materials from `Made` model (`id, name, file`).

---

## 5. Admin Categories (Protected: jwt.auth)

Prefix: `/admins/category`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/category` |
| `POST` | `/admins/category` |
| `PUT/PATCH` | `/admins/category/{id}` |
| `DELETE` | `/admins/category/{id}` |

> Note: `show` method is a bare `GET /admins/category/{id}` returning raw model. No `edit` route separately.

### POST `/admins/category`

**Body**
```json
{ "name": "Category", "description": "...", "note": "...", "image": <file> }
```
Max 17 MB for image.

### PUT `/admins/category/{id}`

Same body as store + `name` is required.

---

## 6. Admin Brands (Protected: jwt.auth)

Prefix: `/admins/brand`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/brand` |
| `GET` | `/admins/brand/{id}` |
| `POST` | `/admins/brand` |
| `PUT/PATCH` | `/admins/brand/{id}` |
| `DELETE` | `/admins/brand/{id}` |
| `GET` | `/admins/brand-edit/{id}` |

### POST `/admins/brand`

**Body**
```json
{ "name": "Brand", "description": "...", "note": "...", "image": <file>, "link": "https://..." }
```
`image` is **required**. Max 15 MB.

---

## 7. Admin Promotions (Protected: jwt.auth)

Prefix: `/admins/promotion`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/promotion` |
| `POST` | `/admins/promotion` |
| `PUT/PATCH` | `/admins/promotion/{id}` |
| `DELETE` | `/admins/promotion/{id}` |
| `GET` | `/admins/edit-promotion/{id}` |
| `GET` | `/admins/get-brand` |

### POST `/admins/promotion`

**Body**
```json
{
  "name": "Promo",
  "description": "...",
  "note": "...",
  "type": "...",
  "brand_id": 1,
  "status": "active",
  "file": <img>, "file2": <img>, "file3": <img>, "file4": <img>, "file5": <img>
}
```
Each image max 15 MB.

> Setting `status = "active"` automatically deactivates all other promotions.

---

## 8. Admin Banners (Protected: jwt.auth)

Prefix: `/admins/banner`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/banner` |
| `POST` | `/admins/banner` |
| `PUT/PATCH` | `/admins/banner/{id}` |
| `DELETE` | `/admins/banner/{id}` |
| `GET` | `/admins/banner-edit/{id}` |

### POST `/admins/banner`

**Body**
```json
{
  "name": "Banner",
  "description": "...",
  "note": "...",
  "type": "...",
  "status": "Active",
  "note2": "...", "note3": "...", "note4": "...", "note5": "...",
  "title": "...", "title2": "...", "title3": "...", "title4": "...", "title5": "...",
  "file": <img|video>,
  "file2": <img|video>,
  "file3": <img|video>,
  "file4": <img|video>
}
```
**Allowed MIME types**: `jpeg, png, jpg, mp4, mov, avi, webm` — max **20 MB** each.
Setting `status = "Active"` deactivates others.

---

## 9. Admin Projects (Protected: jwt.auth)

Prefix: `/admins/project`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/project` |
| `POST` | `/admins/project` |
| `PUT/PATCH` | `/admins/project/{id}` |
| `DELETE` | `/admins/project/{id}` |
| `GET` | `/admins/edit-project/{id}` |
| `GET` | `/admins/get-brand` |
| `GET` | `/admins/get-category` |

### POST `/admins/project`

**Body**
```json
{
  "name": "Project Name",
  "brand_id": 1,
  "description": "...",
  "note": "...",
  "type": "...",
  "category": "...",
  "architect": "...",
  "location": "...",
  "photo_created": "...",
  "designer": "...",
  "project_time": "2025-01-01",
  "file": <img>, ... "file10": <img>
}
```
10 image fields, each max 15 MB. Auto-generated `id` via `generateUniqueProjectId()`.

---

## 10. Admin Catalogs (Protected: jwt.auth)

Prefix: `/admins/catalog`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/catalog` |
| `POST` | `/admins/catalog` |
| `PUT/PATCH` | `/admins/catalog/{id}` |
| `DELETE` | `/admins/catalog/{id}` |
| `GET` | `/admins/edit-catalog/{id}` |
| `GET` | `/admins/get-brand` |

### POST `/admins/catalog`

**Body**
```json
{
  "name": "Catalog",
  "brand_id": 1,
  "description": "...",
  "type": "...",
  "cover": "...",
  "file": <img>
}
```
Max 15 MB.

---

## 11. Admin Press Releases (Protected: jwt.auth)

Prefix: `/admins/press-release`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/press-release` |
| `POST` | `/admins/press-release` |
| `PUT/PATCH` | `/admins/press-release/{id}` |
| `DELETE` | `/admins/press-release/{id}` |
| `GET` | `/admins/edit-pressrelease/{id}` |
| `GET` | `/admins/get-brand` |

### POST `/admins/press-release`

**Body**
```json
{
  "name": "Press Title",
  "description": "...",
  "type": "...",
  "brand_id": 1,
  "status": "...",
  "press_release_date": "2025-01-01",
  "file": <img>, "file2"..., "file3"..., "file4"..., "file5"...
}
```

---

## 12. Admin Logos (Protected: jwt.auth)

Prefix: `/admins/logo`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/logo` |
| `POST` | `/admins/logo` |
| `PUT/PATCH` | `/admins/logo/{id}` |
| `DELETE` | `/admins/logo/{id}` |
| `GET` | `/admins/logo-edit/{id}` |
| `PATCH` | `/admins/logo/{id}/set-active` |

### POST `/admins/logo`

**Body**
```json
{ "description": "...", "image": <img>, "is_active": 1 }
```
Only 1 active logo allowed system-wide. Max 15 MB.

---

## 13. Admin Materials (Made) (Protected: jwt.auth)

Prefix: `/admins/made`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/made` |
| `POST` | `/admins/made` |
| `PUT/PATCH` | `/admins/made/{id}` |
| `DELETE` | `/admins/made/{id}` |
| `GET` | `/admins/made-edit/{id}` |

### POST `/admins/made`

**Body**
```json
{ "name": "Steel", "description": "...", "file": <img> }
```
Max 15 MB image.

### PUT `/admins/made/{id}`

**Body**
```json
{ "name": "Updated", "description": "...", "file": <img|video> }
```
Accepts `jpeg,png,jpg,mp4,mov,avi,webm`, max 20 MB.

---

## 14. Admin Orders (Protected: jwt.auth)

Prefix: `/admins/order`

Standard CRUD via `OrderController`.

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/order` |
| `GET` | `/admins/order/{id}` |
| `POST` | `/admins/order` |
| `PUT/PATCH` | `/admins/order/{id}` |
| `DELETE` | `/admins/order/{id}` |

---

## 15. Admin Transactions (Protected: jwt.auth)

Prefix: `/admins/transaction`

Standard CRUD via `TransactionController`.

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/transaction` |
| `POST` | `/admins/transaction` |
| `PUT/PATCH` | `/admins/transaction/{id}` |
| `DELETE` | `/admins/transaction/{id}` |

---

## 16. Admin Customers (Protected: jwt.auth)

Prefix: `/admins/customer`

Standard CRUD via `CustomerController`.

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/customer` |
| `POST` | `/admins/customer` |
| `PUT/PATCH` | `/admins/customer/{id}` |
| `DELETE` | `/admins/customer/{id}` |

---

## 17. Admin Contacts (Protected: jwt.auth)

Prefix: `/admins/contact-us` (via `ContactUsController`)

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/contact-us` |
| `GET` | `/admins/contact-us/{id}` |
| `DELETE` | `/admins/contact-us/{id}` |

No `POST`/`PUT` — contacts are only **viewed and deleted** by admin. Submitted by customers.

### GET `/admins/contact-us`

Paginated list of all contact form submissions. Filterable by `name`, `email`, `page`.

---

## 18. Admin Dashboard (Protected: jwt.auth)

Prefix: `/admins`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/dashboard` |

### GET `/admins/dashboard`

**Response**
```json
{
  "success": true,
  "data": {
    "total_products": 120,
    "total_categories": 10,
    "total_brands": 5,
    "total_customers": 350,
    "total_admins": 4,
    "total_role": 2
  }
}
```

---

## 19. Admin Roles (Protected: jwt.auth)

Prefix: `/admins/role`

| Method | Endpoint |
|--------|----------|
| `GET` | `/admins/role` |
| `POST` | `/admins/role` |
| `PUT/PATCH` | `/admins/role/{id}` |
| `DELETE` | `/admins/role/{id}` |
| `GET` | `/admins/get-role` (also on AdminController) |
| `GET` | `/admins/get-menu` |
| `GET` | `/admins/get-edit-role/{id}` |

### POST `/admins/role`

**Body**
```json
{ "name": "Moderator", "menus": [1, 3, 5] }
```
Syncs role-to-menu relations.

### GET `/admins/get-edit-role/{id}`

Returns role + all menus with `selected` boolean for UI checkbox binding.

---

## 20. Customer Authentication

Prefix: `/customers`

| Method | Endpoint |
|--------|----------|
| `POST` | `/customers/register` |
| `POST` | `/customers/social-register-auth` |
| `POST` | `/customers/social-login` |
| `POST` | `/customers/login-customer` |
| `GET` | `/customers/me` |
| `POST` | `/customers/logout` |
| `POST` | `/customers/change-password` |

### POST `/customers/register`

**Body**
```json
{
  "name": "John Doe",
  "full_name": "John",
  "email": "john@example.com",
  "phone_number": "+6281234567890",
  "password": "secret123",
  "confirm_password": "secret123"
}
```

### POST `/customers/login-customer`

**Body**
```json
{ "email": "john@example.com", "password": "secret123" }
```

**Success**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "John",
    "email": "john@example.com",
    "token": "eyJ0eXAi..."
  },
  "message": "Login Success",
  "status": 200
}
```

### POST `/customers/social-register-auth` (Google OAuth)

**Body**
```json
{
  "provider": "google",
  "id_token": "<google-id-token>",
  "phone_number": "+62..."
}
```
Also accepts `code` (authorization code flow) — exchanges server-side with Google.

### POST `/customers/social-login`

**Body**
```json
{ "provider": "google", "id_token": "..." }
```

---

## 21. Customer Products (Public)

Prefix: `/product`

| Method | Endpoint |
|--------|----------|
| `GET` | `/product` |
| `GET` | `/product/{id}` |

### GET `/product`

Paginated, `status = active` only. Per-page: **12**.

**Query Params**: `page`, `name`, `sku_product`, `product_type`, `color`, `brand`, `category`, `status`, `raw`

### GET `/product/{id}`

Single product via `ProductCustomerDetailMapper`.

---

## 22. Customer Projects (Public)

Prefix: `/project`

| Method | Endpoint |
|--------|----------|
| `GET` | `/project` |
| `GET` | `/project/{id}` |

### GET `/project`

Paginated, no status filter. Per-page: **9**.

**Query Params**: `page`, `category` (supports `"All"` = no filter)

### GET `/project/{id}`

Uses `ProjectDetailMapper`.

---

## 23. Customer Press Releases (Public)

Prefix: `/press`

| Method | Endpoint |
|--------|----------|
| `GET` | `/press` |
| `GET` | `/press-release` |

### GET `/press`

Paginated list of all press releases.

### GET `/press-release`

Latest **3** press releases ordered by position ascending.

```json
{
  "success": true,
  "data": [...],
  "message": "Success Load Data"
}
```

---

## 24. Customer Parts & Filters (Public)

| Method | Endpoint | Response |
|--------|----------|----------|
| `GET` | `/part-color` | Array of colors: `[{ "color": "Red" }, ...]` |
| `GET` | `/part-category` | Categories from DB (`id, name`) |
| `GET` | `/part-type-product` | Static types: `["Custom Made", "Pre Order", "Limited Edition", "Sales Stock", "Ready Stock"]` |
| `GET` | `/part-brand` | Brands from DB (`id, name`) |

---

## 25. Customer Home Banner (Public)

| Method | Endpoint |
|--------|----------|
| `GET` | `/home-banner` |

### GET `/home-banner`

Returns active banners transformed into slider slides.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "src": "http://localhost:8000/storage/banners/abc.jpg",
      "alt": "Banner Name",
      "type": "main",
      "title": "Title",
      "subtitle": " ",
      "description": "Note text",
      "tag": ""
    }
  ],
  "message": "Success Load Slides"
}
```

---

## 26. Customer Promotion (Public)

| Method | Endpoint |
|--------|----------|
| `GET` | `/customer-promotion` |

### GET `/customer-promotion`

Returns the single most-recently-added **active** promotion (`status = 'active'`).

```json
{
  "success": true,
  "data": { "id": 1, "name": "Promo", "description": "...", "file": "promotions/abc.jpg" },
  "message": "Success Load Data"
}
```

> Note: File path is **raw** (relative to `storage/app/public`). Frontend should prefix with `asset('storage/')`.

---

## 27. Customer Contact Us (Public)

| Method | Endpoint | Auth |
|--------|----------|------|
| `POST` | `/contact-us` (prefix: `/customers/contact-us` via apiResource) | ❌ (store only) |
| `GET` | `/customers/contact-us` (within customers group) | JWT (`customer` guard) |

### POST `/contact-us` (public route at root level)

**Body**
```json
{ "name": "John", "email": "john@example.com", "phone_number": "+62...", "description": "Message" }
```

---

## 28. Customer Email Subscriber (Public)

| Method | Endpoint |
|--------|----------|
| `POST` | `/email-subscriber` |

### POST `/email-subscriber`

**Body**
```json
{ "email": "subscriber@example.com" }
```
Checks uniqueness. Returns `201` with subscriber data or `400` with validation errors.

> **Duplicate**: Same route is also defined under `/customers/email-subscriber` (within the auth group). Only the public one handles store.

---

## 29. Customer Orders (Authenticated: customer JWT)

Prefix: `/customers/orders`

Resource: `CustomerOrderController` (currently empty — no methods implemented).

| Method | Endpoint |
|--------|----------|
| `GET` | `/customers/orders` |
| `POST` | `/customers/orders` |
| `PUT/PATCH` | `/customers/orders/{id}` |
| `DELETE` | `/customers/orders/{id}` |

> These routes are declared via `apiResource` but the controller has no CRUD methods. Empty scaffolds awaiting implementation.

---

## 30. Customer Transactions (Authenticated: customer JWT)

Prefix: `/customers/transactions`

Resource: `CustomerTransactionController` (similarly empty).

| Method | Endpoint |
|--------|----------|
| `GET` | `/customers/transactions` |
| `POST` | `/customers/transactions` |
| `PUT/PATCH` | `/customers/transactions/{id}` |
| `DELETE` | `/customers/transactions/{id}` |

---

## 31. Customer Addresses & Regions (Authenticated: customer JWT)

Prefix: `/customers`

### Address Routes (from `AddressController` — empty scaffold)

| Method | Endpoint |
|--------|----------|
| `GET` | `/customers/address` |
| `POST` | `/customers/address` |
| `PUT/PATCH` | `/customers/address/{id}` |
| `DELETE` | `/customers/address/{id}` |

### Region Lookup (from `CustomerDataController`)

| Method | Endpoint |
|--------|----------|
| `GET` | `/customers/get-province` |
| `POST` | `/customers/get-city` |
| `POST` | `/customers/get-districts` |
| `POST` | `/customers/get-subdistrict` |
| `POST` | `/customers/get-postal-code` |
| `POST` | `/customers/create-address` |
| `GET` | `/customers/get-addresses` |
| `POST` | `/customers/delete-address` |

### GET `/customers/get-province`

```json
{ "success": true, "data": [{ "prov_id": 1, "province_name": "DKI Jakarta" }] }
```

### POST `/customers/get-city`

**Body**: `{ "prov_id": 1 }`

**Response**
```json
{ "success": true, "data": [{ "city_id": 1, "city_name": "Jakarta Selatan" }] }
```

### POST `/customers/get-districts`

**Body**: `{ "city_id": 1 }`

### POST `/customers/get-subdistrict`

**Body**: `{ "dis_id": 1 }`

### POST `/customers/get-postal-code`

**Body**: `{ "prov_id": 1, "city_id": 1, "dis_id": 1, "subdis_id": 1 }`

### POST `/customers/create-address`

**Body**
```json
{
  "recipient_name": "John",
  "phone": "+62...",
  "address_line": "Jl. Example No. 123",
  "province_id": 1,
  "province_name": "DKI Jakarta",
  "city_id": 1,
  "city_name": "Jakarta Selatan",
  "district_id": 1,
  "district_name": "Kebayoran Baru",
  "subdistrict_id": 1,
  "subdistrict_name": "Senayan",
  "postal_code": "12190",
  "is_default": true
}
```

### GET `/customers/get-addresses`

Returns addresses for current logged-in customer.

### POST `/customers/delete-address`

**Body**: `{ "id": 1 }`

---

## 32. Customer Data (Authenticated: customer JWT)

| Method | Endpoint |
|--------|----------|
| `GET` | `/customers/customer-data/{id}` |

Shows customer profile detail via `CustomerFEDetailMapper`.

---

## Models Reference

| Model | Table | Key Notes |
|-------|-------|-----------|
| `Admin` | `admins` | Has `roles()`, `menus()` (via role), `last_login` |
| `Banner` | `banners` | Fields: `name, description, note, note2-5, type, title, title2-5, file, file2-4, status` |
| `Brand` | `brands` | `name, description, note, image, link` |
| `Catalog` | `catalogs` | `name, brand_id, description, type, cover, file` |
| `Category` | `categories` | `name, description, note, image` |
| `ColorProduct` | `color_products` | `product_id, color_name` |
| `ContactUs` | `contact_us` | `name, email, phone_number, description` |
| `Customer` | `customers` | `name, full_name, email, phone_number, password, provider, provider_id, avatar, status, time` |
| `EmailSubscriber` | `email_subscribers` | `email` (unique) |
| `Logo` | `logos` | `description, image, is_active` (singleton active) |
| `Made` | `made` | `name, description, file` |
| `Order` | `orders` | Fields see below |
| `OrderItem` | `order_items` | Fields see below |
| `PressRelease` | `press_releases` | `name, description, type, brand_id, status, file-5, position, press_release_date` |
| `Product` | `products` | `name, sku_product, brand_id, category_id, description, stock_type, color, status, image1-6` |
| `Project` | `projects` | `id, name, brand_id, description, note, type, category, architect, location, photo_created, designer, project_time, file-10, select` |
| `Promotion` | `promotions` | `name, description, note, type, brand_id, status, file-5` |
| `Role` | `roles` | `name` |
| `RoleMenu` | `role_menus` | Pivot `role_id, menu_id` |
| `Transaction` | `transactions` | Fields see below |

### Order & OrderItem Schema

**`orders` table:**
| Column | Type |
|--------|------|
| `id` | PK |
| `customer_id` | FK → customers |
| `order_number` | string (unique) |
| `status` | enum/string |
| `total_price` | decimal |
| `shipping_cost` | decimal |
| `payment_method` | string |
| `payment_status` | string |
| `shipping_address` | text |
| `recipient_name` | string |
| `recipient_phone` | string |
| `tracking_number` | string |
| `notes` | text |
| `created_at`, `updated_at` | timestamps |

**`order_items` table:**
| Column | Type |
|--------|------|
| `id` | PK |
| `order_id` | FK → orders |
| `product_id` | FK → products |
| `product_name` | string |
| `quantity` | integer |
| `price` | decimal |
| `subtotal` | decimal |
| `created_at`, `updated_at` | timestamps |

### Transaction Schema

**`transactions` table:**
| Column | Type |
|--------|------|
| `id` | PK |
| `order_id` | FK → orders |
| `customer_id` | FK → customers |
| `amount` | decimal |
| `payment_method` | string |
| `status` | string |
| `reference_number` | string |
| `paid_at` | datetime |
| `created_at`, `updated_at` | timestamps |

---

## Route Summary (Quick Reference)

### Public Routes (no auth)

| Prefix | Controllers |
|--------|-------------|
| `/product` | `ProductCustomerController` (CRUD-like: index, show) |
| `/project` | `ProjectCustomerController` (CRUD-like) |
| `/press` | `PressController` (index + getPress) |
| `/press-release` | `PressController` → `getPress` (top 3) |
| `/part-color` | `PartController` → `getColor` |
| `/part-category` | `PartController` → `getCategory` |
| `/part-type-product` | `PartController` → `getType` |
| `/part-brand` | `PartController` → `getBrand` |
| `/home-banner` | `HomeController` → `getHomeBanner` |
| `/customer-promotion` | `PromotionCustomersController` → `index` |
| `/contact-us` | `ContactUsCustomerController` → `store` |
| `/email-subscriber` | `EmailSubscriberController` → `store` |
| `/admins/login` | `AuthController` → `login` |
| `/admins/forgot_password` | `AuthController` → `sendResetLinkEmail` |
| `/admins/reset_password` | `AuthController` → `reset` |
| `/admins/active-logo` | `LogoController` → `activeLogo` |
| `/customers/register` | `AuthCustomerController` → `register` |
| `/customers/social-register-auth` | `AuthCustomerController` → `socialRegister` |
| `/customers/social-login` | `AuthCustomerController` → `socialLogin` |

### Protected — Admin (`jwt.auth` guard: `api`)

| Prefix | Controller |
|--------|------------|
| `/admins/admin` | `AdminController` |
| `/admins/product` | `ProductController` |
| `/admins/promotion` | `PromotionController` |
| `/admins/press-release` | `PressReleaseController` |
| `/admins/project` | `ProjectController` |
| `/admins/catalog` | `CatalogController` |
| `/admins/banner` | `BannerController` |
| `/admins/brand` | `BrandController` |
| `/admins/category` | `CategoryController` |
| `/admins/logo` | `LogoController` |
| `/admins/made` | `MadeController` |
| `/admins/contact-us` | `ContactUsController` |
| `/admins/list-contact` | `ListContactController` |
| `/admins/customer` | `CustomerController` |
| `/admins/order` | `OrderController` |
| `/admins/transaction` | `TransactionController` |
| `/admins/role` | `RolesController` |
| `/admins/dashboard` | `DashboardController` |
| `/admins/me`, `/change-password`, `/logout` | `AuthController` |

### Protected — Customer (`jwt.auth` guard: `customer`)

| Prefix | Controller |
|--------|------------|
| `/customers/me` | `AuthCustomerController` |
| `/customers/change-password` | `AuthCustomerController` |
| `/customers/logout` | `AuthCustomerController` |
| `/customers/contact-us` | `ContactUsCustomerController` (CRUD-like) |
| `/customers/email-subscriber` | `EmailSubscriberController` (store) |
| `/customers/project` | `OurProjectController` |
| `/customers/project/list` | `OurProjectController` → `list` |
| `/customers/customer-data` | `CustomerDataController` |
| `/customers/orders` | `CustomerOrderController` |
| `/customers/transactions` | `CustomerTransactionController` |
