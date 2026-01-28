# POSaaS - Multi-Tenant Point of Sale SaaS Platform

## Overview
POSaaS is a multi-tenant Software-as-a-Service (SaaS) Point of Sale platform designed to serve multiple retail and restaurant businesses. Built with Laravel + Backpack for the backend/backoffice and Flutter for mobile applications, it provides a scalable, white-label solution with subscription-based pricing.

### Business Model
- **SaaS Platform**: Multi-tenant architecture serving multiple stores
- **Subscription Tiers**: Free, Basic, Pro, Enterprise
- **White-Label Capabilities**: Custom branding per tenant
- **Revenue Model**: Monthly/annual subscriptions with tiered pricing
- **Target Market**: Small to medium retail stores, restaurants, cafes

### System Requirements
- **Backend**: PHP 8.1+, Laravel 10
- **Backoffice**: Laravel Backpack 4.1
- **Database**: PostgreSQL 14+
- **Mobile**: Flutter 3.x (Android/iOS)
- **Web Server**: Nginx or Apache
- **Cache**: Redis
- **Queue**: Redis or Database
- **Extensions**: GD, intl, mbstring, json, pdo, curl, zip

### Architecture
- **Framework**: Laravel 10 (Multi-tenant SaaS)
- **Backoffice**: Laravel Backpack 4.1 (Admin Panel)
- **Mobile**: Flutter 3.x (Cross-platform)
- **Database**: Multi-tenant isolation (PostgreSQL database per tenant)
- **API**: RESTful API with JWT authentication
- **Real-time**: WebSockets (Laravel Echo/Redis)
- **Queue**: Redis for background jobs
- **Cache**: Redis for performance
- **Session**: Database-backed sessions with tenant context

### Multi-Tenant Strategy
- **Tenant Isolation**: Separate database per tenant (store)
- **Central Database**: Stores tenant metadata, subscriptions, billing
- **Tenant Routing**: Subdomain-based (store1.yourdomain.com)
- **Data Isolation**: Complete separation of tenant data
- **Shared Services**: Shared codebase with tenant-specific data

---

## Multi-Tenant Architecture

### Central Database (Platform Level)

#### tenants (Store/Tenant Management)
**Fields:**
- `id` BIGINT AUTO_INCREMENT - Primary key
- `subdomain` VARCHAR(100) UNIQUE - Store subdomain
- `name` VARCHAR(255) - Store name
- `status` ENUM('active', 'suspended', 'trial') - Store status
- `database_name` VARCHAR(255) - Tenant database name
- `plan_id` BIGINT - Subscription plan reference
- `subscription_status` ENUM('active', 'past_due', 'cancelled', 'trial')
- `trial_ends_at` DATETIME - Trial expiration
- `subscription_ends_at` DATETIME - Subscription expiration
- `max_users` INT - Maximum users allowed
- `max_items` INT - Maximum items allowed
- `max_locations` INT - Maximum stock locations
- `custom_domain` VARCHAR(255) - Custom domain
- `logo_path` VARCHAR(255) - Store logo
- `primary_color` VARCHAR(7) - Brand color
- `created_at` DATETIME
- `updated_at` DATETIME

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE INDEX (`subdomain`)
- UNIQUE INDEX (`custom_domain`)
- INDEX (`status`)

#### subscription_plans (Pricing Tiers)
**Fields:**
- `id` BIGINT AUTO_INCREMENT - Primary key
- `name` VARCHAR(100) - Plan name (Free, Basic, Pro, Enterprise)
- `slug` VARCHAR(100) UNIQUE - URL-friendly name
- `price_monthly` DECIMAL(10,2) - Monthly price
- `price_yearly` DECIMAL(10,2) - Yearly price
- `max_users` INT - Maximum users
- `max_items` INT - Maximum items
- `max_locations` INT - Maximum locations
- `max_cash_registers` INT - Maximum registers
- `features` JSON - Feature flags
- `trial_days` INT - Trial period in days
- `is_active` BOOLEAN - Active flag
- `created_at` DATETIME
- `updated_at` DATETIME

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE INDEX (`slug`)

#### subscriptions (Billing Management)
**Fields:**
- `id` BIGINT AUTO_INCREMENT - Primary key
- `tenant_id` BIGINT - Tenant reference
- `plan_id` BIGINT - Plan reference
- `status` ENUM('active', 'past_due', 'cancelled', 'trialing', 'incomplete')
- `billing_cycle` ENUM('monthly', 'yearly')
- `current_period_start` DATETIME
- `current_period_end` DATETIME
- `cancel_at_period_end` BOOLEAN
- `created_at` DATETIME
- `updated_at` DATETIME

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`tenant_id`)
- INDEX (`status`)

**Foreign Keys:**
- `tenant_id` REFERENCES `tenants`(`id`)
- `plan_id` REFERENCES `subscription_plans`(`id`)

#### invoices (Billing Invoices)
**Fields:**
- `id` BIGINT AUTO_INCREMENT - Primary key
- `tenant_id` BIGINT - Tenant reference
- `subscription_id` BIGINT - Subscription reference
- `invoice_number` VARCHAR(255) - Invoice number
- `amount` DECIMAL(10,2) - Invoice amount
- `status` ENUM('draft', 'unpaid', 'paid', 'overdue', 'cancelled')
- `due_date` DATETIME
- `paid_at` DATETIME
- `payment_method` VARCHAR(50) - Payment method (cash, bank_transfer, etc.)
- `notes` TEXT - Invoice notes
- `created_at` DATETIME

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE INDEX (`invoice_number`)
- INDEX (`tenant_id`)
- INDEX (`status`)

**Foreign Keys:**
- `tenant_id` REFERENCES `tenants`(`id`)

#### users (Platform Users)
**Fields:**
- `id` BIGINT AUTO_INCREMENT - Primary key
- `tenant_id` BIGINT - Tenant reference (null for super admin)
- `name` VARCHAR(255) - User name
- `email` VARCHAR(255) UNIQUE - Email
- `password` VARCHAR(255) - Hashed password
- `role` ENUM('super_admin', 'admin', 'manager', 'cashier')
- `email_verified_at` DATETIME
- `remember_token` VARCHAR(100)
- `created_at` DATETIME
- `updated_at` DATETIME

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE INDEX (`email`)
- INDEX (`tenant_id`)
- INDEX (`role`)

**Foreign Keys:**
- `tenant_id` REFERENCES `tenants`(`id`)

### Tenant Database (Per Store)

Each tenant has its own isolated database with the following tables (from original OSPOS):
- `items` - Product inventory
- `item_kits` - Product bundles
- `sales` - Sales transactions
- `sales_items` - Sale line items
- `customers` - Customer database
- `suppliers` - Supplier database
- `employees` - Employee accounts
- `receivings` - Purchase orders
- `expenses` - Expense tracking
- `giftcards` - Gift card management
- `dinner_tables` - Restaurant tables
- `tax_categories` - Tax categories
- `tax_codes` - Tax codes
- `tax_jurisdictions` - Tax jurisdictions
- `attribute_definitions` - Custom field definitions
- `attribute_values` - Custom field values
- `attribute_links` - Attribute associations
- `cashups` - Cash register tracking
- `app_config` - Store-specific configuration
- `stock_locations` - Stock locations
- `item_quantities` - Stock quantities
- `customers_packages` - Reward packages
- `customers_points` - Customer points
- `sales_reward_points` - Sale rewards

### Tenant Routing Strategy

**Subdomain-Based Routing:**
- `store1.yourdomain.com` → Routes to tenant 1
- `store2.yourdomain.com` → Routes to tenant 2
- `www.yourdomain.com` → Platform landing page

**Custom Domain Support:**
- `mystore.com` → Routes to tenant's database
- DNS CNAME configuration
- SSL certificate management

**Middleware:**
- Tenant identification from subdomain
- Database connection switching
- Tenant context injection
- Subscription validation

### Subscription Enforcement

**Feature Limits Enforcement:**
- Maximum users check
- Maximum items check
- Maximum locations check
- Maximum cash registers check
- Feature flag checks

**Subscription Status Checks:**
- Active subscription required for access
- Trial period handling
- Suspension handling
- Grace periods

### White-Label Capabilities

**Per-Tenant Customization:**
- Custom logo upload
- Brand color selection
- Custom domain support
- Email template customization
- Receipt template customization
- Theme selection

**Admin Panel Features:**
- Store management dashboard
- Subscription management
- Billing overview
- Revenue analytics
- Tenant analytics
- Support ticket system

---

## Development Plan - Phased Approach

### Phase 1: Foundation & Platform Setup (4-6 weeks)

**Objective:** Set up the multi-tenant infrastructure and core platform

**Tasks:**
1. **Project Setup**
   - Initialize Laravel 11 project
   - Configure Backpack 6 for admin panel
   - Set up Redis for caching and queues
   - Configure multi-tenant database connections
   - Set up environment configuration

2. **Central Database Schema**
   - Create central database migrations
   - Implement tenants table
   - Implement subscription_plans table
   - Implement subscriptions table
   - Implement invoices table
   - Implement users table with role system

3. **Multi-Tenant Middleware**
   - Create tenant identification middleware
   - Implement database connection switching
   - Create tenant context service
   - Implement subdomain routing
   - Add custom domain support

4. **Authentication System**
   - Implement JWT authentication
   - Create user registration/login
   - Implement role-based access control
   - Add email verification
   - Implement password reset

5. **Manual Billing System**
   - Create subscription management
   - Implement manual invoice generation (PDF)
   - Create payment tracking (cash, bank transfer)
   - Implement payment reminders via email
   - Create billing reports
   - Add access blocking mechanism for non-paying tenants
   - Implement manual payment verification by admin

**Deliverables:**
- Working multi-tenant platform
- User authentication system
- Manual billing system with access control
- Admin panel for platform management

---

### Phase 2: Core POS Features (6-8 weeks)

**Objective:** Implement essential POS functionality for tenants

**Tasks:**
1. **Tenant Database Setup**
   - Create tenant database creation system
   - Implement database migrations for tenant schema
   - Create tenant seeding system
   - Implement database backup/restore

2. **Items Management**
   - CRUD operations for items
   - Barcode generation
   - Image upload
   - Category management
   - Search and filtering
   - Item kits (bundles)

3. **Customers Management**
   - Customer CRUD operations
   - Customer statistics
   - Account number generation
   - Search and filtering

4. **Suppliers Management**
   - Supplier CRUD operations
   - Supplier categories
   - Purchase history

5. **Sales Management**
   - POS register interface
   - Add/remove items to cart
   - Apply discounts
   - Multiple payment methods
   - Complete sales
   - Receipt generation
   - Suspend/resume sales

6. **Inventory Tracking**
   - Real-time inventory updates
   - Stock locations
   - Low inventory alerts
   - Quantity management

**Deliverables:**
- Full POS functionality
- Inventory management
- Customer and supplier management
- Sales processing

---

### Phase 3: Advanced Features (4-6 weeks)

**Objective:** Add advanced POS features and reporting

**Tasks:**
1. **Receivings (Purchasing)**
   - Purchase order processing
   - Supplier selection
   - Stock receiving
   - Invoice generation

2. **Expenses Management**
   - Expense tracking
   - Expense categories
   - Expense reports

3. **Gift Cards**
   - Gift card creation
   - Value management
   - Redemption

4. **Cash Management**
   - Cash register opening/closing
   - Cash up tracking
   - Cash reports

5. **Restaurant Features**
   - Table management
   - Table status tracking
   - Table assignment to sales

6. **Reporting System**
   - Sales reports (daily, weekly, monthly)
   - Inventory reports
   - Customer reports
   - Employee reports
   - Tax reports
   - Expense reports
   - Export to CSV/PDF

**Deliverables:**
- Purchasing system
- Expense tracking
- Gift card system
- Cash management
- Restaurant features
- Comprehensive reporting

---

### Phase 4: Mobile Application Development (8-10 weeks)

**Objective:** Build Flutter mobile app for Android/iOS

**Tasks:**
1. **Flutter Project Setup**
   - Initialize Flutter project
   - Configure state management (Provider/Riverpod)
   - Set up navigation
   - Configure API integration

2. **Authentication**
   - Login screen
   - Registration
   - Password reset
   - JWT token management
   - Biometric authentication

3. **POS Features**
   - Barcode scanning
   - Add items to cart
   - Apply discounts
   - Payment processing
   - Receipt generation
   - Offline mode with sync

4. **Inventory Management**
   - View items
   - Add/edit items
   - Stock management
   - Low inventory alerts

5. **Customer Management**
   - View customers
   - Add/edit customers
   - Customer history

6. **Sales Management**
   - Process sales
   - View sales history
   - Generate reports

7. **Offline Support**
   - Local database (SQLite/Room)
   - Sync mechanism
   - Conflict resolution

8. **Push Notifications**
   - Sales notifications
   - Low inventory alerts
   - Payment confirmations

**Deliverables:**
- Fully functional Flutter mobile app
- Android and iOS support
- Offline capability
- Push notifications

---

### Phase 5: Subscription & Billing (4-6 weeks)

**Objective:** Complete subscription management and billing system

**Tasks:**
1. **Subscription Plans**
   - Create plan management
   - Feature flag system
   - Trial period handling
   - Plan upgrades/downgrades

2. **Billing System**
   - Manual invoice generation
   - Payment tracking (cash, bank transfer)
   - Payment history
   - Payment reminders via email
   - Billing reports

3. **Usage Tracking**
   - User count tracking
   - Item count tracking
   - Location count tracking
   - Usage reports

4. **Subscription Enforcement**
   - Feature limit checks
   - Subscription validation
   - Suspension handling
   - Grace periods

5. **Admin Panel Features**
   - Store management
   - Subscription overview
   - Revenue analytics
   - Tenant analytics
   - Support tickets

**Deliverables:**
- Complete subscription system
- Billing automation
- Usage tracking
- Admin panel enhancements

---

### Phase 6: White-Label & Customization (3-4 weeks)

**Objective:** Implement white-label capabilities

**Tasks:**
1. **Branding**
   - Custom logo upload
   - Brand color selection
   - Theme customization
   - Email template customization

2. **Custom Domains**
   - Custom domain setup
   - SSL certificate management
   - DNS configuration

3. **Receipt Customization**
   - Receipt templates
   - Custom fields
   - Logo on receipts

4. **Email Customization**
   - Email templates
   - Custom sender
   - Email branding

**Deliverables:**
- White-label capabilities
- Custom domain support
- Branding customization

---

### Phase 7: Testing & Quality Assurance (3-4 weeks)

**Objective:** Comprehensive testing and bug fixes

**Tasks:**
1. **Unit Testing**
   - Backend unit tests
   - Model tests
   - Service tests
   - Controller tests

2. **Integration Testing**
   - API integration tests
   - Database integration tests
   - Payment integration tests
   - Multi-tenant tests

3. **Mobile Testing**
   - Unit tests
   - Widget tests
   - Integration tests
   - E2E tests

4. **Performance Testing**
   - Load testing
   - Stress testing
   - Database optimization
   - API response time optimization

5. **Security Testing**
   - Penetration testing
   - Vulnerability scanning
   - Security audits

**Deliverables:**
- Comprehensive test suite
- Bug fixes
- Performance optimization
- Security hardening

---

### Phase 8: Deployment & Launch (2-3 weeks)

**Objective:** Deploy to production and launch

**Tasks:**
1. **Infrastructure Setup**
   - Production server setup
   - Database configuration
   - Redis configuration
   - SSL certificates
   - CDN setup

2. **CI/CD Pipeline**
   - Automated testing
   - Automated deployment
   - Rollback mechanisms
   - Monitoring setup

3. **Monitoring & Logging**
   - Application monitoring
   - Error tracking (Sentry)
   - Performance monitoring
   - Log aggregation

4. **Backup & Recovery**
   - Database backups
   - File backups
   - Disaster recovery plan
   - Backup automation

5. **Documentation**
   - API documentation
   - User guides
   - Admin documentation
   - Developer documentation

**Deliverables:**
- Production deployment
- Monitoring system
- Backup system
- Complete documentation

---

### Technology Stack Summary

### Backend & Backoffice
- **Framework**: Laravel 10
- **Admin Panel**: Laravel Backpack 4.1
- **Database**: PostgreSQL 14+
- **Cache**: Redis
- **Queue**: Redis
- **Payment**: Manual (Cash, Bank Transfer)
- **Authentication**: JWT
- **Real-time**: WebSockets (Laravel Echo/Redis)

### Mobile Application
- **Framework**: Flutter 3.x
- **State Management**: Provider or Riverpod
- **Local Storage**: SQLite/Room
- **API**: Retrofit
- **Barcode**: Mobile Vision SDK
- **Notifications**: Firebase Cloud Messaging (Free Tier)

### DevOps & Infrastructure
- **Web Server**: Nginx (Free)
- **Container**: Docker (Free)
- **CI/CD**: GitHub Actions (Free Tier)
- **Monitoring**: Laravel Telescope (Free)
- **SSL**: Let's Encrypt (Free)
- **Email**: SMTP (Gmail/Free SMTP services)

### Free Services Used
- **PostgreSQL**: Open source database
- **Redis**: Open source cache/queue
- **Laravel**: Open source framework
- **Backpack 4.1**: Free admin panel
- **Flutter**: Open source mobile framework
- **Docker**: Free containerization
- **GitHub Actions**: Free CI/CD
- **Let's Encrypt**: Free SSL certificates
- **Firebase**: Free tier for notifications
- **Laravel Telescope**: Free debugging tool

### No Paid Services
- No Stripe (payment gateway)
- No Pusher (real-time)
- No AWS/Azure/GCP (use VPS)
- No paid monitoring tools
- No paid email services (use free SMTP)

### Billing System
- Manual invoice generation (PDF)
- Payment tracking (hand-to-hand cash/bank transfer)
- Payment reminders via email
- Billing reports
- Subscription management
- Access blocking for non-paying tenants
- Manual payment verification by admin

---

## Manual Payment Workflow

### Payment Collection Process

**1. Invoice Generation**
- System automatically generates monthly invoices
- PDF invoices sent via email to tenant admin
- Invoice includes:
  - Invoice number and date
  - Subscription plan and pricing
  - Billing period
  - Payment instructions (bank account, etc.)
  - Due date (30 days from invoice date)

**2. Payment Collection (Outside System)**
- Tenant pays via cash or bank transfer
- Payment is collected manually by platform owner
- No payment gateway involved

**3. Payment Verification**
- Admin receives payment notification (email/WhatsApp)
- Admin logs into admin panel
- Admin marks invoice as "paid"
- System records payment date and method

**4. Access Control**
- System checks payment status on every login
- Non-paying tenants are blocked from access
- Blocked tenants see payment reminder screen
- Payment reminder emails sent automatically

### Access Blocking Mechanism

**Blocking Logic:**
```php
// Middleware checks payment status
if ($tenant->subscription_status !== 'active' || $tenant->subscription_ends_at < now()) {
    // Block access
    return redirect('/payment-required');
}
```

**Payment Required Screen:**
- Shows outstanding invoices
- Displays payment instructions
- Shows subscription status
- Contact information for support

**Grace Period:**
- 7-day grace period after subscription ends
- Warning emails sent during grace period
- After grace period, full access blocked

**Admin Controls:**
- Manually mark payments as received
- Manually block/unblock tenants
- Extend grace periods
- Send payment reminders
- View payment history

### Future Payment Integration

**Prepared for:**
- Stripe (international payments)
- Tunisian ClickToPay (local payments)

**Integration Points:**
- Payment gateway interface (abstracted)
- Webhook handlers
- Payment method selection
- Automatic payment verification

**Migration Path:**
1. Keep manual payment as fallback
2. Add Stripe integration (optional)
3. Add ClickToPay integration (optional)
4. Tenants can choose payment method

---

## Key Benefits for Multi-Tenant SaaS

### For Business Owners:
- **Scalable Revenue**: Subscription-based model
- **Multi-Store Support**: Serve unlimited stores
- **White-Label**: Custom branding per tenant
- **Manual Billing**: Hand-to-hand cash/bank transfer payments
- **Access Control**: Block non-paying tenants
- **Low Maintenance**: Centralized management
- **Zero Cost**: All services are free/open source
- **Future-Ready**: Ready for Stripe/ClickToPay integration

### For Developers:
- **Laravel + Backpack**: Rapid development
- **Flutter**: Cross-platform mobile
- **Multi-Tenant**: Proven architecture
- **RESTful API**: Easy integration
- **Comprehensive Features**: All POS functionality

### For End Users (Stores):
- **No Installation**: Web-based access
- **Mobile App**: On-the-go management
- **Offline Support**: Work without internet
- **Automatic Updates**: Always latest features
- **Affordable**: Tiered pricing

---

## Core Features

### 1. Sales Management

#### Point of Sale (POS) Register
**Core Functionality:**
- Real-time barcode scanning integration
- Multiple payment methods: Cash, Credit Card, Check, Debit, Due
- Automatic inventory updates on sale completion
- Customer selection and tracking
- Line item and total discount application
- Multi-tax calculation with support for tax-inclusive and tax-exclusive pricing
- Item search with autocomplete (by name, barcode, item number)
- Suspend and resume sales functionality
- Receipt printing and emailing

**Key Fields:**
- `sale_time`: Timestamp of sale
- `customer_id`: Customer reference
- `employee_id`: Employee who processed sale
- `comment`: Sale notes
- `invoice_number`: Custom invoice number
- `quote_number`: Quotation reference
- `sale_status`: Sale state (0=completed, 1=suspended)
- `dinner_table_id`: Restaurant table reference
- `sale_type`: Sale classification

**Payment Methods:**
- Cash (with cash rounding support)
- Credit Card
- Check
- Debit
- Due (customer account)
- Gift Card

**Discount Types:**
- Percentage-based discounts
- Fixed amount discounts
- Line item discounts
- Total sale discounts

**Supported Operations:**
- Add items by barcode scanning
- Add items by search
- Modify quantities
- Apply discounts
- Remove items
- Suspend sale
- Resume suspended sale
- Complete sale
- Print receipt
- Email receipt

#### Sales Transactions
**Transaction Logging:**
- Complete audit trail of all sales
- Payment tracking with multiple payment types per sale
- Cash adjustment and refund tracking
- Transaction status management
- Sale history with search and filtering

**Search Filters:**
- Date range filtering
- Customer-specific sales
- Payment type filtering (cash, due, check, credit card)
- Invoice-only sales
- Suspended sales

**Suspended Sales:**
- Save incomplete sales for later
- Resume from suspended state
- Auto-generated quote numbers
- Convert to completed sales

#### Invoicing
**Invoice Features:**
- Custom invoice numbering format (configurable)
- Invoice generation from sales
- Invoice printing with company branding
- Email invoices to customers
- Invoice status tracking
- Invoice history and management

**Configuration Options:**
- `invoice_enable`: Enable/disable invoicing
- `sales_invoice_format`: Invoice number format
- `recv_invoice_format`: Receiving invoice format
- `last_used_invoice_number`: Auto-increment tracking

#### Quotations
**Quotation Management:**
- Create quotations from sales
- Custom quotation numbering
- Convert quotations to sales
- Print quotations
- Email quotations to customers
- Quotation history

**Configuration:**
- `sales_quote_format`: Quotation number format
- `last_used_quote_number`: Auto-increment tracking

#### Receipt Management
**Receipt Features:**
- Receipt printing with customizable templates
- Email receipts to customers
- Receipt content configuration
- Multiple receipt formats
- Company name and logo support
- Font size configuration

**Configuration Options:**
- `receipt_template`: Template selection
- `receipt_show_company_name`: Show company name
- `receipt_show_total_discount`: Show discount total
- `receipt_show_description`: Show item descriptions
- `receipt_show_serialnumber`: Show serial numbers
- `receipt_font_size`: Font size setting

**Receipt Data:**
- Company information
- Sale details
- Line items with quantities and prices
- Tax breakdown
- Payment information
- Change due
- Barcode/QR code support

### 2. Inventory Management

#### Items Management
**Item Database Fields:**
- `item_id`: Primary key
- `name`: Item name
- `category`: Item category
- `supplier_id`: Supplier reference
- `item_number`: Barcode/SKU
- `description`: Item description
- `cost_price`: Wholesale cost
- `unit_price`: Selling price
- `reorder_level`: Minimum stock alert level
- `receiving_quantity`: Default receiving quantity
- `allow_alt_description`: Allow custom descriptions
- `is_serialized`: Track serial numbers
- `stock_type`: Stock type (0=has stock, 1=no stock)
- `item_type`: Item type (0=standard, 1=amount entry, 2=kit)
- `tax_category_id`: Tax category reference
- `pic_filename`: Item image filename
- `qty_per_pack`: Quantity per pack
- `pack_name`: Pack name
- `low_sell_item_id`: Low sell item reference
- `hsn_code`: Harmonized System Code
- `deleted`: Soft delete flag

**Item Features:**
- Barcode generation and printing
- Image upload support
- Serialized item tracking
- Custom field support via attributes system
- Category management
- Subcategory support
- Reorder level alerts
- Low inventory notifications
- Duplicate barcode prevention (configurable)
- Search by name, barcode, item number, category

**Item Types:**
- **Standard Items**: Regular inventory items
- **Amount Entry Items**: Services or non-stock items
- **Item Kits**: Bundled products

**Search Filters:**
- Empty UPC/barcode items
- Low inventory items
- Serialized items
- Items without descriptions
- Custom attribute search
- Deleted items
- Temporary items

#### Item Kits (Bundled Products)
**Kit Configuration:**
- `item_id`: Kit identifier
- `kit_discount_percent`: Discount percentage
- `price_option`: Price calculation method
- `print_option`: Print behavior
- `item_kit_number`: Kit barcode

**Kit Items:**
- `kit_sequence`: Display order
- `quantity`: Quantity per kit
- Individual item tracking

**Price Options:**
- Sum of component prices
- Fixed kit price
- Discounted kit price

#### Stock Locations
**Multi-Location Support:**
- Unlimited stock locations
- Location-based inventory tracking
- Stock transfers between locations
- Location-specific permissions
- Location-based reporting

**Location Fields:**
- `location_id`: Primary key
- `location_name`: Location name
- `deleted`: Soft delete flag

**Permissions:**
- Location-specific item permissions
- Location-specific sales permissions
- Location-specific receiving permissions

#### Inventory Tracking
**Transaction Types:**
- Sales (decrease inventory)
- Receivings (increase inventory)
- Stock transfers
- Inventory adjustments

**Tracking Features:**
- Real-time quantity updates
- Transaction logging
- Decimal quantity support (configurable precision)
- Cost price tracking
- Stock in/out reports
- Inventory value calculations

**Quantity Precision:**
- Configurable decimal places
- `quantity_decimals`: Setting for precision

#### Item Attributes (Custom Fields)
**Attribute System:**
- Extensible custom field framework
- Support for multiple attribute types:
  - TEXT: Text fields
  - DATETIME: Date/time fields
  - DECIMAL: Numeric fields

**Database Tables:**
- `ospos_attribute_definitions`: Field definitions
- `ospos_attribute_values`: Field values
- `ospos_attribute_links`: Associations

**Attribute Features:**
- Link to items, sales, and receivings
- Cascading delete support
- Foreign key constraints
- Unique constraint enforcement

**Attribute Definition Fields:**
- `definition_id`: Primary key
- `definition_name`: Field name
- `definition_type`: Field type
- `definition_flags`: Configuration flags
- `definition_fk`: Parent definition (for hierarchical attributes)
- `deleted`: Soft delete flag

**Migration Notes:**
- Replaces old custom1-10 fields
- Maintains data integrity during migration
- Supports unlimited custom fields

### 3. Purchasing & Receivings

#### Receivings (Stock Receiving)
**Core Functionality:**
- Purchase order processing
- Supplier selection and management
- Stock receiving with automatic inventory updates
- Reference number tracking
- Invoice generation for receivings
- Multiple payment method support
- Receiving reports and history

**Receiving Fields:**
- `receiving_time`: Timestamp of receiving
- `supplier_id`: Supplier reference
- `employee_id`: Employee who processed receiving
- `comment`: Receiving notes
- `invoice_number`: Custom invoice number
- `reference`: Reference number
- `payment_type`: Payment method used

**Payment Methods:**
- Cash
- Check
- Debit
- Credit
- Due

**Receiving Process:**
1. Select supplier
2. Scan or search items
3. Enter quantities and costs
4. Apply discounts if needed
5. Select payment method
6. Complete receiving
7. Update inventory automatically

**Features:**
- Barcode scanning for items
- Cost price updates
- Quantity updates
- Receiving quantity tracking
- Invoice printing
- Email invoices

#### Supplier Management
**Supplier Database Fields:**
- `person_id`: Primary key (references people table)
- `company_name`: Company name
- `account_number`: Supplier account number
- `category`: Supplier category

**Supplier Features:**
- Contact information management
- Supplier categories
- Account number tracking
- Purchase history
- Supplier statistics
- Search and filtering

**Supplier Categories:**
- Custom category support
- Category-based reporting
- Category-based filtering

### 4. Customer Management

#### Customer Database
**Customer Fields:**
- `person_id`: Primary key (references people table)
- `account_number`: Customer account number
- `taxable`: Tax exemption flag
- `tax_id`: Tax identification number
- `sales_tax_code_id`: Tax code reference
- `discount`: Default discount percentage
- `discount_type`: Discount type (percent/fixed)
- `company_name`: Company name
- `package_id`: Reward package reference
- `points`: Reward points balance
- `consent`: GDPR consent flag
- `deleted`: Soft delete flag

**Customer Features:**
- Comprehensive customer profiles
- Contact information (from people table)
- Customer statistics tracking:
  - Total amount spent
  - Minimum purchase amount
  - Maximum purchase amount
  - Average purchase amount
  - Total quantity purchased
  - Average discount
- Customer search and filtering
- Account number generation
- Tax exemption support
- Company name support

**Search Capabilities:**
- Search by name
- Search by account number
- Search by email
- Search by phone number
- Filter by various criteria

#### Customer Rewards & Loyalty
**Reward System Fields:**
- `customers_packages`:
  - `package_id`: Primary key
  - `package_name`: Package name
  - `points_percent`: Points earning percentage
  - `deleted`: Soft delete flag

- `customers_points`:
  - `id`: Primary key
  - `person_id`: Customer reference
  - `package_id`: Package reference
  - `sale_id`: Sale reference
  - `points_earned`: Points earned

- `sales_reward_points`:
  - `id`: Primary key
  - `sale_id`: Sale reference
  - `earned`: Points earned
  - `used`: Points used

**Reward Packages (Default):**
- Default: 0% points
- Bronze: 10% points
- Silver: 20% points
- Gold: 30% points
- Premium: 50% points

**Reward Features:**
- Points earning on purchases
- Points redemption for discounts
- Package-based point multipliers
- Point history tracking
- Configurable point percentages
- Customer package assignment

**Configuration:**
- `customer_reward_enable`: Enable/disable rewards

#### Customer Tax Support
**Tax Features:**
- Customer-specific tax codes
- Tax jurisdiction assignment
- Location-based tax calculation
- Tax exemption support
- Sales tax code tracking

**Tax Code Fields:**
- `tax_code`: Tax code identifier
- `tax_code_name`: Tax code name
- `tax_code_type`: Tax code type
- `city`: City field
- `state`: State field

**Tax Rate Fields:**
- `rate_tax_code`: Tax code reference
- `rate_tax_category_id`: Tax category reference
- `tax_rate`: Tax rate percentage
- `rounding_code`: Rounding method

#### MailChimp Integration
**Integration Features:**
- Customer list synchronization
- Email campaign support
- Double opt-in support
- List management
- API key configuration

**Configuration:**
- `mailchimp_api_key`: Encrypted API key
- `mailchimp_list_id`: Encrypted list ID

### 5. Employee Management

#### Employee Database
**Employee Fields:**
- `person_id`: Primary key (references people table)
- `username`: Login username
- `password`: Hashed password
- `hash_version`: Password hash version (1 or 2)
- `deleted`: Soft delete flag

**Employee Features:**
- Employee profiles
- Contact information (from people table)
- Login credentials
- Password hashing (bcrypt)
- Session management
- Remember me functionality

#### Permission System
**Permission Structure:**
- **Modules**: High-level feature groups
- **Permissions**: Granular access rights
- **Grants**: Permission assignments to employees
- **Locations**: Location-specific permissions

**Core Modules:**
- `sales`: Sales operations
- `items`: Item management
- `customers`: Customer management
- `employees`: Employee management
- `receivings`: Stock receiving
- `reports`: Report generation
- `config`: System configuration
- `suppliers`: Supplier management
- `giftcards`: Gift card management
- `expenses`: Expense tracking
- `taxes`: Tax management
- `messages`: SMS messaging
- `attributes`: Custom attributes
- `item_kits`: Item kit management
- `cashups`: Cash management

**Report Permissions:**
- `reports_sales`: Sales reports
- `reports_items`: Inventory reports
- `reports_customers`: Customer reports
- `reports_employees`: Employee reports
- `reports_suppliers`: Supplier reports
- `reports_receivings`: Receiving reports
- `reports_discounts`: Discount reports
- `reports_taxes`: Tax reports
- `reports_inventory`: Inventory reports
- `reports_categories`: Category reports
- `reports_payments`: Payment reports

**Location-Specific Permissions:**
- Format: `{module}_{location_name}`
- Example: `items_warehouse`, `sales_store1`
- Allows granular access control per location

**Grant Management:**
- Assign permissions to employees
- Menu group assignments
- Role-based access control
- Permission inheritance

### 6. Tax Management

#### Multi-Tier Taxation
**Tax Categories:**
- `tax_category_id`: Primary key
- `tax_category`: Category name
- `tax_group_sequence`: Display order
- Auto-incrementing IDs

**Tax Codes:**
- Tax code definitions
- Tax code names
- Tax code types
- City/state information
- Tax rate assignments

**Tax Jurisdictions:**
- Jurisdiction definitions
- Location-based tax rules
- Tax rate assignments

**Tax Features:**
- Multi-tier tax calculation
- Tax-inclusive and tax-exclusive pricing
- VAT/GST support
- Indian GST support
- Cascade tax support
- Customer-specific tax rates
- Rounding code support
- Tax reporting

**Tax Calculation:**
- Line item tax calculation
- Tax group support
- Tax type classification
- Rounding methods:
  - No rounding
  - Round to nearest
  - Round up
  - Round down
- Cash rounding support

**Configuration:**
- `tax_included`: Tax-inclusive pricing
- `customer_sales_tax_support`: Customer tax support
- `default_tax_category`: Default tax category
- `default_tax_1_name/rate`: Primary tax
- `default_tax_2_name/rate`: Secondary tax
- `cash_rounding_code`: Cash rounding method
- `cash_decimals`: Cash decimal places

**Tax Reporting:**
- Sales tax reports
- Tax collection reports
- Tax jurisdiction reports
- Tax category reports
- Detailed tax breakdowns

### 7. Expenses Management

#### Expense Tracking
**Expense Fields:**
- `expense_id`: Primary key
- `date`: Expense date
- `amount`: Expense amount
- `payment_type`: Payment method
- `expense_category_id`: Category reference
- `description`: Expense description
- `employee_id`: Employee who created expense
- `deleted`: Soft delete flag

**Expense Categories:**
- `expense_category_id`: Primary key
- `category_name`: Category name
- `deleted`: Soft delete flag

**Expense Features:**
- Expense logging and tracking
- Expense categorization
- Multiple payment methods:
  - Cash
  - Due
  - Check
  - Credit
  - Debit
- Date range tracking
- Search and filtering
- Expense reports
- Payment summaries

**Search Filters:**
- Date range filtering
- Payment type filtering
- Category filtering
- Text search
- Deleted items filter

**Reports:**
- Expense summaries
- Expense category reports
- Payment method breakdowns

### 8. Gift Cards

#### Gift Card Management
**Gift Card Fields:**
- `giftcard_id`: Primary key
- `person_id`: Customer reference
- `giftcard_number`: Card number (configurable format)
- `value`: Card value
- `deleted`: Soft delete flag
- `record_time`: Creation timestamp

**Gift Card Features:**
- Gift card creation and tracking
- Value management (add/deduct)
- Gift card redemption in sales
- Gift card search by number
- Series-based or random number generation
- Gift card history

**Configuration:**
- `giftcard_number`: Number format (series/random)

**Gift Card Number Generation:**
- Series: Sequential numbering
- Random: Random alphanumeric generation

### 9. Cash Management

#### Cash Up Functionality
**Cash Up Fields:**
- `cashup_id`: Primary key
- `open_date`: Opening timestamp
- `close_date`: Closing timestamp
- `open_amount`: Opening amount
- `close_amount`: Closing amount
- `transfer_amount`: Transfer amount
- `open_employee_id`: Employee who opened
- `close_employee_id`: Employee who closed
- `comment`: Cash up notes
- `deleted`: Soft delete flag

**Cash Up Features:**
- Cash register opening and closing
- Cash amount tracking
- Employee assignment
- Open/close date and time
- Cash up reports
- Transfer amount tracking
- Comment support

**Cash Up Process:**
1. Open cash register with starting amount
2. Process sales throughout the day
3. Close cash register with ending amount
4. Generate cash up report
5. Track discrepancies

**Reports:**
- Cash up summary
- Payment method breakdown
- Cash discrepancies
- Employee performance

### 10. Restaurant Features

#### Table Management
**Dinner Table Fields:**
- `dinner_table_id`: Primary key
- `name`: Table name
- `status`: Table status (0=available, 1=occupied)
- `deleted`: Soft delete flag

**Default Tables:**
- Delivery
- Take Away

**Table Features:**
- Table creation and management
- Table status tracking
- Table assignment to sales
- Delivery and take-away support
- Restaurant mode enable/disable

**Configuration:**
- `dinner_table_enable`: Enable/disable restaurant features

**Sale Integration:**
- `dinner_table_id` in sales table
- Table-specific sales tracking
- Table status updates on sale completion

### 11. Messaging System

#### SMS Messaging
**SMS Configuration:**
- `msg_uid`: SMS gateway username
- `msg_pwd`: SMS gateway password (encrypted)
- `msg_src`: SMS originator/sender ID

**SMS Features:**
- SMS gateway integration
- Send messages to customers
- Configurable SMS provider
- Message templates
- Bulk messaging support

**SMS Providers:**
- Textmarketer
- Custom SMS gateways (via proxy implementation)

**SMS Workflow:**
1. Select customer
2. Compose message
3. Send via SMS gateway
4. Track delivery status

**Configuration:**
- SMS provider credentials
- Sender ID configuration
- Message templates

### 12. Reporting System

#### Sales Reports
**Summary Sales Reports:**
- Daily, weekly, monthly, custom date ranges
- Sales totals
- Quantity totals
- Subtotal, tax, and total amounts
- Payment method breakdown
- Employee performance
- Customer performance

**Detailed Sales Reports:**
- Line-by-line sale details
- Item-level information
- Tax breakdown per item
- Payment information
- Customer information

**Report Filters:**
- Date range
- Sale type (all, sales, returns)
- Location
- Customer
- Employee
- Payment type

**Report Formats:**
- Tabular data
- Graphical charts
- Export options (CSV, PDF)

#### Inventory Reports
**Inventory Summary:**
- Total inventory value
- Item counts
- Category breakdown
- Location breakdown

**Low Inventory Reports:**
- Items below reorder level
- Stock-out alerts
- Reorder recommendations

**Inventory Value Reports:**
- Cost value
- Retail value
- Margin calculations

#### Customer Reports
**Customer Purchase Reports:**
- Purchase history
- Spending patterns
- Frequency analysis

**Customer Statistics:**
- Total customers
- Active customers
- New customers
- Customer retention

#### Employee Reports
**Employee Performance:**
- Sales by employee
- Transaction counts
- Average sale value
- Productivity metrics

#### Supplier Reports
**Supplier Purchase Reports:**
- Purchase history
- Supplier statistics
- Payment tracking

#### Expense Reports
**Expense Summaries:**
- Total expenses
- Category breakdown
- Date range analysis

**Expense Category Reports:**
- Category-wise expenses
- Trend analysis

#### Tax Reports
**Tax Collection Reports:**
- Tax collected by category
- Tax collected by jurisdiction
- Tax payment summaries

**Tax Jurisdiction Reports:**
- Location-based tax collection
- Jurisdiction-specific reports

**Tax Category Reports:**
- Category-wise tax collection
- Tax rate analysis

**Report Generation:**
- Date range selection
- Filter application
- Sort options
- Export capabilities
- Print support
- Email reports

### 13. Configuration & Settings

#### System Configuration
**General Settings:**
- `address`: Company address
- `phone`: Company phone
- `fax`: Company fax
- `email`: Company email
- `website`: Company website
- `return_policy`: Return policy text
- `store_accounting_income`: Accounting income account
- `store_accounting_expense`: Accounting expense account

**Receipt Configuration:**
- `receipt_template`: Template selection (receipt_default, etc.)
- `receipt_show_company_name`: Show company name (0/1)
- `receipt_show_total_discount`: Show discount total (0/1)
- `receipt_show_description`: Show item descriptions (0/1)
- `receipt_show_serialnumber`: Show serial numbers (0/1)
- `receipt_font_size`: Font size (default: 12)
- `receipt_show_taxes`: Show tax breakdown (0/1)
- `receipt_show_total_discount`: Show total discount (0/1)
- `receipt_show_sale_num`: Show sale number (0/1)
- `receipt_show_customer`: Show customer info (0/1)
- `receipt_show_employee`: Show employee info (0/1)
- `receipt_show_time`: Show sale time (0/1)
- `receipt_show_comments`: Show comments (0/1)

**Barcode Configuration:**
- `barcode_content`: Barcode content (id, number, name)
- `barcode_type`: Barcode type (Code128, EAN13, etc.)
- `barcode_width`: Barcode width
- `barcode_height`: Barcode height
- `barcode_font`: Barcode font
- `barcode_font_size`: Barcode font size
- `barcode_num_in_row`: Number per row
- `barcode_page_width`: Page width
- `barcode_page_cellspacing`: Cell spacing
- `barcode_page_orientation`: Page orientation
- `barcode_generate_if_empty`: Generate if empty
- `barcode_label`: Label format
- `barcode_price`: Show price
- `barcode_cost`: Show cost

**Currency Configuration:**
- `currency_symbol`: Currency symbol ($, €, £, etc.)
- `currency_symbol_location`: Symbol position (left, right)
- `currency_decimals`: Decimal places (default: 2)
- `currency_thousands_separator`: Thousands separator
- `currency_decimal_point`: Decimal point
- `number_locale`: Locale setting (en_US, etc.)
- `thousands_separator`: Enable thousands separator (0/1)

**Number Formatting:**
- `quantity_decimals`: Quantity decimal places (default: 0)
- `tax_decimals`: Tax decimal places (default: 2)
- `cash_decimals`: Cash decimal places (default: 2)

**Date/Time Formatting:**
- `date_or_time_format`: Format type (date, time, datetime)
- `datetime_format`: Date/time format string

**Language Settings:**
- `language`: Language code (english, spanish, etc.)
- `language_code`: ISO language code (en, es, etc.)

**Payment Options:**
- `payment_options_order`: Payment method order
- `default_register_mode`: Default mode (sale, return)
- `allow_duplicate_barcodes`: Allow duplicate barcodes (0/1)

**Sales Configuration:**
- `sales_invoice_format`: Invoice number format
- `sales_quote_format`: Quote number format
- `invoice_enable`: Enable invoicing (0/1)
- `recv_invoice_format`: Receiving invoice format
- `last_used_invoice_number`: Auto-increment tracking
- `last_used_quote_number`: Auto-increment tracking
- `tax_included`: Tax-inclusive pricing (0/1)
- `customer_sales_tax_support`: Customer tax support (0/1)
- `line_sequence`: Line sequence order

**Inventory Configuration:**
- `receiving_calculate_average_price`: Calculate average price (0/1)
- `multi_pack_enabled`: Enable multi-pack (0/1)

**Customer Configuration:**
- `customer_reward_enable`: Enable rewards (0/1)
- `customer_sales_tax_support`: Customer tax support (0/1)

**Tax Configuration:**
- `default_tax_category`: Default tax category
- `default_tax_1_name`: Primary tax name
- `default_tax_1_rate`: Primary tax rate
- `default_tax_2_name`: Secondary tax name
- `default_tax_2_rate`: Secondary tax rate
- `cash_rounding_code`: Cash rounding method (0-3)
- `default_origin_tax_code`: Default tax code

**Gift Card Configuration:**
- `giftcard_number`: Number format (series, random)

**Restaurant Configuration:**
- `dinner_table_enable`: Enable restaurant features (0/1)

**Cash Up Configuration:**
- `cash_up_enable`: Enable cash up (0/1)

**Statistics Configuration:**
- `statistics`: Enable statistics (0/1)

**Financial Configuration:**
- `financial_year`: Financial year start month (1-12)

**Email Configuration:**
- `protocol`: Email protocol (mail, sendmail, smtp)
- `mailpath`: Sendmail path
- `smtp_host`: SMTP server
- `smtp_user`: SMTP username
- `smtp_pass`: SMTP password (encrypted)
- `smtp_port`: SMTP port (default: 465)
- `smtp_timeout`: SMTP timeout (default: 5)
- `smtp_crypto`: SMTP encryption (ssl, tls)

**MailChimp Configuration:**
- `mailchimp_api_key`: Encrypted API key
- `mailchimp_list_id`: Encrypted list ID

**SMS Configuration:**
- `msg_uid`: SMS gateway username
- `msg_pwd`: SMS gateway password (encrypted)
- `msg_src`: SMS originator/sender ID

**Security Configuration:**
- `gcaptcha_enable`: Enable reCAPTCHA (0/1)
- `gcaptcha_secret_key`: reCAPTCHA secret key
- `gcaptcha_site_key`: reCAPTCHA site key
- `gcaptcha_version`: reCAPTCHA version (v2, v3)

**GDPR Configuration:**
- `privacy_policy`: Privacy policy text
- `cookie_consent`: Cookie consent text

**Notification Configuration:**
- `notify_horizontal_position`: Horizontal position (left, center, right)
- `notify_vertical_position`: Vertical position (top, center, bottom)
- `notify_timeout`: Notification timeout

**Theme Configuration:**
- `theme`: Bootswatch theme (flatly, cosmo, united, etc.)

**Barcode Generation:**
- `barcode_generate_if_empty`: Generate if empty
- `barcode_label`: Label format
- `barcode_price`: Show price
- `barcode_cost`: Show cost

#### Theme Support
**Bootswatch Themes:**
- Flatly
- Cosmo
- United
- Yeti
- Lumen
- Journal
- Simplex
- Readable
- Slate
- Spacelab
- Cyborg
- Darkly
- Superhero
- Cerulean
- Sandstone

**Theme Features:**
- Bootstrap 3 based
- Responsive design
- Customizable via configuration
- Theme switching

#### Multi-Language Support
**Supported Languages (40+):**
- English (en, en-GB)
- Spanish (es-ES, es-MX)
- French (fr)
- German (de-DE, de-CH)
- Italian (it)
- Portuguese (pt-BR)
- Dutch (nl-NL, nl-BE)
- Russian (ru)
- Chinese (zh-Hans, zh-Hant)
- Japanese
- Korean
- Arabic (ar-EG, ar-LB)
- Hebrew (he)
- Turkish (tr)
- Polish (pl)
- Czech (cs)
- Hungarian (hu)
- Romanian (ro)
- Bulgarian (bg)
- Serbian
- Croatian (hr-HR)
- Swedish (sv)
- Norwegian (nb)
- Danish (da)
- Finnish
- Greek (el)
- Ukrainian (uk)
- Vietnamese (vi)
- Thai (th)
- Indonesian (id)
- Malay (ml)
- Tagalog (tl)
- Hindi
- Urdu (ur)
- Persian (fa)
- Azerbaijani (az)
- Georgian
- Armenian (hy)
- Kurdish (ckb)
- Bosnian (bs)
- Lao (lo)
- And more...

**Language Features:**
- Complete translations
- Locale-specific formatting
- Date/time localization
- Number formatting
- Currency localization
- Translation management via Weblate

### 14. Security Features

#### Authentication
**Login System:**
- Username/password authentication
- Password hashing (bcrypt)
- Hash version tracking (version 1 and 2)
- Session-based authentication
- Database-backed sessions
- Remember me functionality
- Session timeout configuration
- Login attempt tracking

**Password Security:**
- Bcrypt password hashing
- Password hash version tracking
- Secure password storage
- Password change functionality
- Password reset (via admin)

#### Access Control
**Permission System:**
- Module-based permissions
- Granular access control
- Location-specific permissions
- Role-based access control
- Grant management
- Menu group assignments
- Permission inheritance

**Permission Structure:**
- Modules: High-level feature groups
- Permissions: Granular access rights
- Grants: Permission assignments to employees
- Locations: Location-specific permissions

**Location-Based Access:**
- Location-specific item permissions
- Location-specific sales permissions
- Location-specific receiving permissions
- Format: `{module}_{location_name}`

**IP-Based Access:**
- Proxy IP whitelisting
- IP-based session validation
- Proxy support configuration

#### Security Enhancements
**CSRF Protection:**
- CSRF token generation
- Token validation on form submissions
- Automatic token refresh

**SQL Injection Protection:**
- Parameterized queries
- Query builder with escaping
- Input validation

**XSS Protection:**
- Output escaping
- Input sanitization
- Content Security Policy support

**Google reCAPTCHA:**
- reCAPTCHA v2 and v3 support
- Configurable on login page
- Site key and secret key configuration
- Enable/disable option

**Input Validation:**
- Type validation
- Length validation
- Format validation
- Custom validation rules

#### GDPR Compliance
**Data Protection:**
- Customer consent tracking
- Privacy policy support
- Cookie consent banners
- Data export functionality
- Data deletion support

**Consent Management:**
- `consent` field in customers table
- Privacy policy configuration
- Cookie consent configuration

### 15. Integration Capabilities

#### MailChimp Integration
**Features:**
- Customer list synchronization
- Email campaign support
- Double opt-in support
- List management
- API key configuration

**Configuration:**
- `mailchimp_api_key`: Encrypted API key
- `mailchimp_list_id`: Encrypted list ID

**Integration Points:**
- Customer creation/update sync
- Customer deletion sync
- Email campaign integration

#### SMS Gateway Integration
**Features:**
- SMS gateway integration
- Send messages to customers
- Configurable SMS provider
- Message templates
- Bulk messaging support

**Supported Providers:**
- Textmarketer
- Custom SMS gateways (via proxy implementation)

**Configuration:**
- `msg_uid`: SMS gateway username
- `msg_pwd`: SMS gateway password (encrypted)
- `msg_src`: SMS originator/sender ID

**SMS Workflow:**
1. Select customer
2. Compose message
3. Send via SMS gateway
4. Track delivery status

#### Email Integration
**Features:**
- SMTP support
- Email notifications
- Receipt emailing
- Invoice emailing
- Quotation emailing

**Configuration:**
- `protocol`: Email protocol (mail, sendmail, smtp)
- `mailpath`: Sendmail path
- `smtp_host`: SMTP server
- `smtp_user`: SMTP username
- `smtp_pass`: SMTP password (encrypted)
- `smtp_port`: SMTP port (default: 465)
- `smtp_timeout`: SMTP timeout (default: 5)
- `smtp_crypto`: SMTP encryption (ssl, tls)

**Email Features:**
- Receipt emailing
- Invoice emailing
- Quotation emailing
- Custom email templates
- Email notifications

### 16. Technical Features

#### Framework & Architecture
**CodeIgniter 4 Framework:**
- MVC architecture
- RESTful API structure
- Modular design
- HMVC support
- Event system
- Filter system

**Architecture Pattern:**
- Model-View-Controller (MVC)
- Separation of concerns
- Reusable components
- Library-based functionality
- Helper functions

**Design Patterns:**
- Active Record pattern
- Repository pattern
- Service layer
- Factory pattern

#### Database
**Database Support:**
- MySQL 5.7+
- MariaDB 10.2+
- Database migrations
- Foreign key constraints
- Transaction support
- Query builder
- Parameterized queries

**Database Features:**
- Soft delete support
- Timestamp tracking
- Auto-incrementing IDs
- Index optimization
- Query caching

**Migration System:**
- Version-controlled schema updates
- Rollback support
- Database upgrade scripts
- Migration history tracking

#### Frontend
**UI Framework:**
- Bootstrap 3
- Bootswatch themes
- jQuery
- AJAX-powered interactions
- Dynamic content loading

**Frontend Features:**
- Responsive design
- Mobile-friendly interface
- Real-time updates
- Form validation
- Autocomplete search
- Modal dialogs

**JavaScript Libraries:**
- jQuery
- jQuery UI
- DataTables
- Chart.js (for reports)
- Bootstrap plugins

#### Performance
**Caching System:**
- File-based caching
- Configuration caching
- Session caching
- Query result caching

**Optimization:**
- Optimized database queries
- Indexed searches
- Lazy loading
- Pagination support
- Asset minification

**Performance Features:**
- Database query optimization
- Caching strategies
- Session management
- Efficient data retrieval

#### Internationalization
**Multi-Language Support:**
- 40+ language translations
- Language code configuration
- Locale-specific formatting
- Translation management via Weblate

**Localization Features:**
- Currency localization
- Date/time localization
- Number formatting
- Text direction (LTR/RTL)

#### Deployment
**Docker Support:**
- Dockerfile configuration
- Docker Compose configurations
- Development environment
- Production environment
- Test environment

**Deployment Options:**
- Traditional hosting (Apache/Nginx)
- Docker containers
- Cloud deployment
- Development and production environments

**Database Upgrade Scripts:**
- Version-controlled upgrades
- Migration scripts
- Data migration support
- Rollback capability

### API Endpoints & Workflows

#### Sales API Endpoints
**Sales Operations:**
- `GET /sales` - Sales register interface
- `POST /sales/add_item` - Add item to sale
- `POST /sales/edit_item` - Edit sale item
- `POST /sales/delete_item` - Delete sale item
- `POST /sales/complete` - Complete sale
- `POST /sales/suspend` - Suspend sale
- `POST /sales/unsuspend` - Resume suspended sale
- `POST /suspended/complete` - Complete suspended sale
- `GET /sales/search` - Search sales
- `GET /sales/getRow` - Get sale row
- `GET /sales/getSuggestion` - Get sale suggestions
- `POST /suspended/delete` - Delete suspended sale

**Sale Management:**
- `GET /sales/manage` - Sales management interface
- `GET /sales/getSearch` - Search sales data

#### Items API Endpoints
**Items Operations:**
- `GET /items` - Items management interface
- `POST /items/save` - Save item
- `POST /items/delete` - Delete item
- `GET /items/getSearch` - Search items
- `GET /items/getRow` - Get item row
- `GET /items/suggest_search` - Item search suggestions
- `POST /items/bulk` - Bulk operations

#### Customers API Endpoints
**Customers Operations:**
- `GET /customers` - Customers management interface
- `POST /customers/save` - Save customer
- `POST /customers/delete` - Delete customer
- `GET /customers/getSearch` - Search customers
- `GET /customers/getRow` - Get customer row
- `GET /customers/suggest_search` - Customer search suggestions

#### Receivings API Endpoints
**Receivings Operations:**
- `GET /receivings` - Receivings interface
- `POST /receivings/add_item` - Add item to receiving
- `POST /receivings/edit_item` - Edit receiving item
- `POST /receivings/delete_item` - Delete receiving item
- `POST /receivings/complete` - Complete receiving
- `POST /receivings/undo` - Undo receiving
- `GET /receivings/getSearch` - Search receivings
- `POST /receivings/selectSupplier` - Select supplier

#### Reports API Endpoints
**Reports Operations:**
- `GET /reports` - Reports listing
- `GET /reports/summary_sales` - Summary sales report
- `GET /reports/summary_categories` - Category summary report
- `GET /reports/summary_customers` - Customer summary report
- `GET /reports/summary_items` - Item summary report
- `GET /reports/summary_employees` - Employee summary report
- `GET /reports/summary_suppliers` - Supplier summary report
- `GET /reports/summary_taxes` - Tax summary report
- `GET /reports/summary_payments` - Payment summary report
- `GET /reports/summary_discounts` - Discount summary report
- `GET /reports/detailed_sales` - Detailed sales report
- `GET /reports/detailed_receivings` - Detailed receivings report
- `GET /reports/inventory_low` - Low inventory report
- `GET /reports/inventory_summary` - Inventory summary report
- `GET /reports/specific_customer` - Specific customer report
- `GET /reports/specific_employee` - Specific employee report
- `GET /reports/specific_supplier` - Specific supplier report
- `GET /reports/specific_discount` - Specific discount report

#### Config API Endpoints
**Configuration Operations:**
- `GET /config` - Configuration interface
- `POST /config/save` - Save configuration
- `POST /config/bulk_save` - Bulk save configuration
- `GET /config/check_duplicate` - Check duplicate values
- `GET /config/get_file_info` - Get file information
- `GET /config/get_logo` - Get company logo

#### Authentication Endpoints
**Login Operations:**
- `GET /login` - Login page
- `POST /login` - Login authentication
- `GET /logout` - Logout

#### Common API Patterns
**Search Pattern:**
- `GET /{module}/getSearch` - Search with pagination
- `GET /{module}/getRow` - Get single row
- `GET /{module}/suggest_search` - Autocomplete suggestions

**CRUD Pattern:**
- `POST /{module}/save` - Create/Update
- `POST /{module}/delete` - Delete
- `GET /{module}` - List view

**AJAX Responses:**
- JSON responses
- Success/error status
- Data payload
- Error messages

#### Workflows

**Sale Workflow:**
1. Open POS register
2. Select customer (optional)
3. Add items via barcode search
4. Apply discounts (optional)
5. Select payment method(s)
6. Complete sale
7. Print/email receipt
8. Update inventory automatically

**Receiving Workflow:**
1. Open receiving interface
2. Select supplier
3. Add items via barcode search
4. Enter quantities and costs
5. Apply discounts (optional)
6. Select payment method
7. Complete receiving
8. Print/email invoice
9. Update inventory automatically

**Customer Creation Workflow:**
1. Open customer management
2. Enter customer information
3. Select reward package (optional)
4. Assign tax code (optional)
5. Save customer
6. Generate account number

**Employee Creation Workflow:**
1. Open employee management
2. Enter employee information
3. Set username and password
4. Assign permissions
5. Grant module access
6. Save employee

**Report Generation Workflow:**
1. Select report type
2. Choose date range
3. Apply filters (optional)
4. Select location (optional)
5. Generate report
6. View/print/email/export

---

## Database Schema Details

### Core Tables Structure

#### ospos_items (Product Inventory)
**Primary Key:** `item_id`

**Fields:**
- `item_id` INT(10) AUTO_INCREMENT - Primary key
- `name` VARCHAR(255) - Item name
- `category` VARCHAR(255) - Item category
- `supplier_id` INT(10) - Supplier reference
- `item_number` VARCHAR(255) - Barcode/SKU
- `description` TEXT - Item description
- `cost_price` DECIMAL(15,2) - Wholesale cost
- `unit_price` DECIMAL(15,2) - Selling price
- `reorder_level` DECIMAL(15,3) - Minimum stock alert level
- `receiving_quantity` DECIMAL(15,3) - Default receiving quantity
- `allow_alt_description` TINYINT(1) - Allow custom descriptions
- `is_serialized` TINYINT(1) - Track serial numbers
- `stock_type` TINYINT(2) - Stock type (0=has stock, 1=no stock)
- `item_type` TINYINT(2) - Item type (0=standard, 1=amount entry, 2=kit)
- `tax_category_id` INT(10) - Tax category reference
- `pic_filename` VARCHAR(255) - Item image filename
- `qty_per_pack` DECIMAL(15,3) - Quantity per pack
- `pack_name` VARCHAR(255) - Pack name
- `low_sell_item_id` INT(10) - Low sell item reference
- `hsn_code` VARCHAR(255) - Harmonized System Code
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`item_id`)
- INDEX (`item_number`)
- INDEX (`deleted`)

#### ospos_sales (Sales Transactions)
**Primary Key:** `sale_id`

**Fields:**
- `sale_id` INT(10) AUTO_INCREMENT - Primary key
- `sale_time` DATETIME - Timestamp of sale
- `customer_id` INT(10) - Customer reference
- `employee_id` INT(10) - Employee reference
- `comment` TEXT - Sale notes
- `invoice_number` VARCHAR(32) - Custom invoice number
- `quote_number` VARCHAR(32) - Quotation reference
- `sale_status` TINYINT(2) - Sale state (0=completed, 1=suspended)
- `dinner_table_id` INT(11) - Restaurant table reference
- `work_order_number` VARCHAR(32) - Work order number
- `sale_type` TINYINT(2) - Sale classification

**Indexes:**
- PRIMARY KEY (`sale_id`)
- UNIQUE INDEX (`invoice_number`)
- INDEX (`sale_time`)
- INDEX (`customer_id`)
- INDEX (`employee_id`)
- INDEX (`dinner_table_id`)

**Foreign Keys:**
- `customer_id` REFERENCES `ospos_customers`(`person_id`)
- `employee_id` REFERENCES `ospos_employees`(`person_id`)
- `dinner_table_id` REFERENCES `ospos_dinner_tables`(`dinner_table_id`)

#### ospos_sales_items (Sale Line Items)
**Fields:**
- `sale_id` INT(10) - Sale reference
- `item_id` INT(10) - Item reference
- `line` INT(3) - Line number
- `description` VARCHAR(255) - Item description
- `serialnumber` VARCHAR(255) - Serial number
- `quantity_purchased` DECIMAL(15,3) - Quantity
- `discount` DECIMAL(15,2) - Discount amount
- `discount_type` TINYINT(2) - Discount type (0=fixed, 1=percent)
- `item_unit_price` DECIMAL(15,2) - Unit price
- `item_cost_price` DECIMAL(15,2) - Cost price
- `print_option` TINYINT(2) - Print option

**Indexes:**
- PRIMARY KEY (`sale_id`, `item_id`, `line`)
- INDEX (`item_id`)

**Foreign Keys:**
- `sale_id` REFERENCES `ospos_sales`(`sale_id`)
- `item_id` REFERENCES `ospos_items`(`item_id`)

#### ospos_customers (Customer Database)
**Primary Key:** `person_id` (references ospos_people)

**Fields:**
- `person_id` INT(10) - Primary key
- `account_number` VARCHAR(255) - Customer account number
- `taxable` TINYINT(1) - Tax exemption flag
- `tax_id` VARCHAR(32) - Tax identification number
- `sales_tax_code_id` VARCHAR(32) - Tax code reference
- `discount` DECIMAL(15,2) - Default discount percentage
- `discount_type` TINYINT(2) - Discount type (0=percent, 1=fixed)
- `company_name` VARCHAR(255) - Company name
- `package_id` INT(11) - Reward package reference
- `points` INT(11) - Reward points balance
- `date` DATETIME - Creation date
- `employee_id` INT(10) - Creating employee
- `consent` TINYINT(1) - GDPR consent flag
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`person_id`)
- UNIQUE INDEX (`account_number`)
- INDEX (`deleted`)

**Foreign Keys:**
- `person_id` REFERENCES `ospos_people`(`person_id`)
- `package_id` REFERENCES `ospos_customers_packages`(`package_id`)
- `employee_id` REFERENCES `ospos_employees`(`person_id`)

#### ospos_employees (Employee Accounts)
**Primary Key:** `person_id` (references ospos_people)

**Fields:**
- `person_id` INT(10) - Primary key
- `username` VARCHAR(255) - Login username
- `password` VARCHAR(255) - Hashed password
- `hash_version` TINYINT(1) - Password hash version (1 or 2)
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`person_id`)
- UNIQUE INDEX (`username`)

#### ospos_receivings (Purchase Orders)
**Primary Key:** `receiving_id`

**Fields:**
- `receiving_id` INT(10) AUTO_INCREMENT - Primary key
- `receiving_time` DATETIME - Timestamp
- `supplier_id` INT(10) - Supplier reference
- `employee_id` INT(10) - Employee reference
- `comment` TEXT - Receiving notes
- `invoice_number` VARCHAR(32) - Custom invoice number
- `reference` VARCHAR(32) - Reference number
- `payment_type` VARCHAR(32) - Payment method

**Indexes:**
- PRIMARY KEY (`receiving_id`)
- UNIQUE INDEX (`invoice_number`)
- INDEX (`receiving_time`)
- INDEX (`supplier_id`)

**Foreign Keys:**
- `supplier_id` REFERENCES `ospos_suppliers`(`person_id`)
- `employee_id` REFERENCES `ospos_employees`(`person_id`)

#### ospos_expenses (Expense Tracking)
**Primary Key:** `expense_id`

**Fields:**
- `expense_id` INT(10) AUTO_INCREMENT - Primary key
- `date` DATETIME - Expense date
- `amount` DECIMAL(15,2) - Expense amount
- `payment_type` VARCHAR(32) - Payment method
- `expense_category_id` INT(10) - Category reference
- `description` TEXT - Expense description
- `employee_id` INT(10) - Creating employee
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`expense_id`)
- INDEX (`date`)
- INDEX (`expense_category_id`)

**Foreign Keys:**
- `expense_category_id` REFERENCES `ospos_expense_categories`(`expense_category_id`)
- `employee_id` REFERENCES `ospos_employees`(`person_id`)

#### ospos_giftcards (Gift Card Management)
**Primary Key:** `giftcard_id`

**Fields:**
- `giftcard_id` INT(10) AUTO_INCREMENT - Primary key
- `person_id` INT(10) - Customer reference
- `giftcard_number` VARCHAR(255) - Card number
- `value` DECIMAL(15,2) - Card value
- `deleted` TINYINT(1) - Soft delete flag
- `record_time` TIMESTAMP - Creation timestamp

**Indexes:**
- PRIMARY KEY (`giftcard_id`)
- UNIQUE INDEX (`giftcard_number`)

**Foreign Keys:**
- `person_id` REFERENCES `ospos_people`(`person_id`)

#### ospos_dinner_tables (Restaurant Tables)
**Primary Key:** `dinner_table_id`

**Fields:**
- `dinner_table_id` INT(11) AUTO_INCREMENT - Primary key
- `name` VARCHAR(30) - Table name
- `status` TINYINT(1) - Table status (0=available, 1=occupied)
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`dinner_table_id`)

#### ospos_tax_categories (Tax Categories)
**Primary Key:** `tax_category_id` (auto-increment)

**Fields:**
- `tax_category_id` INT(10) AUTO_INCREMENT - Primary key
- `tax_category` VARCHAR(32) - Category name
- `tax_group_sequence` TINYINT(2) - Display order

**Indexes:**
- PRIMARY KEY (`tax_category_id`)

#### ospos_tax_codes (Tax Codes)
**Primary Key:** `tax_code`

**Fields:**
- `tax_code` VARCHAR(32) - Tax code identifier
- `tax_code_name` VARCHAR(255) - Tax code name
- `tax_code_type` TINYINT(2) - Tax code type
- `city` VARCHAR(255) - City field
- `state` VARCHAR(255) - State field

**Indexes:**
- PRIMARY KEY (`tax_code`)

#### ospos_tax_jurisdictions (Tax Jurisdictions)
**Primary Key:** `tax_jurisdiction_id`

**Fields:**
- `tax_jurisdiction_id` INT(10) AUTO_INCREMENT - Primary key
- `jurisdiction_name` VARCHAR(255) - Jurisdiction name
- `tax_group` VARCHAR(32) - Tax group

**Indexes:**
- PRIMARY KEY (`tax_jurisdiction_id`)

#### ospos_attribute_definitions (Custom Field Definitions)
**Primary Key:** `definition_id`

**Fields:**
- `definition_id` INT(10) AUTO_INCREMENT - Primary key
- `definition_name` VARCHAR(255) - Field name
- `definition_type` VARCHAR(45) - Field type (TEXT, DATETIME, DECIMAL)
- `definition_flags` TINYINT(4) - Configuration flags
- `definition_fk` INT(10) - Parent definition (for hierarchical)
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`definition_id`)
- INDEX (`definition_fk`)

**Foreign Keys:**
- `definition_fk` REFERENCES `ospos_attribute_definitions`(`definition_id`)

#### ospos_attribute_values (Custom Field Values)
**Primary Key:** `attribute_id`

**Fields:**
- `attribute_id` INT AUTO_INCREMENT - Primary key
- `attribute_value` VARCHAR(255) UNIQUE - Field value
- `attribute_datetime` DATETIME - DateTime value

**Indexes:**
- PRIMARY KEY (`attribute_id`)
- UNIQUE INDEX (`attribute_value`)

#### ospos_attribute_links (Attribute Associations)
**Fields:**
- `attribute_id` INT - Attribute reference
- `definition_id` INT - Definition reference
- `item_id` INT - Item reference
- `sale_id` INT - Sale reference
- `receiving_id` INT - Receiving reference

**Indexes:**
- UNIQUE INDEX (`attribute_id`, `definition_id`, `item_id`, `sale_id`, `receiving_id`)
- INDEX (`attribute_id`)
- INDEX (`definition_id`)
- INDEX (`item_id`)
- INDEX (`sale_id`)
- INDEX (`receiving_id`)

**Foreign Keys:**
- `definition_id` REFERENCES `ospos_attribute_definitions`(`definition_id`) ON DELETE CASCADE
- `attribute_id` REFERENCES `ospos_attribute_values`(`attribute_id`) ON DELETE CASCADE
- `item_id` REFERENCES `ospos_items`(`item_id`)
- `receiving_id` REFERENCES `ospos_receivings`(`receiving_id`)
- `sale_id` REFERENCES `ospos_sales`(`sale_id`)

#### ospos_cashups (Cash Register Tracking)
**Primary Key:** `cashup_id`

**Fields:**
- `cashup_id` INT(10) AUTO_INCREMENT - Primary key
- `open_date` DATETIME - Opening timestamp
- `close_date` DATETIME - Closing timestamp
- `open_amount` DECIMAL(15,2) - Opening amount
- `close_amount` DECIMAL(15,2) - Closing amount
- `transfer_amount` DECIMAL(15,2) - Transfer amount
- `open_employee_id` INT(10) - Employee who opened
- `close_employee_id` INT(10) - Employee who closed
- `comment` TEXT - Cash up notes
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`cashup_id`)
- INDEX (`open_date`)
- INDEX (`close_date`)

**Foreign Keys:**
- `open_employee_id` REFERENCES `ospos_employees`(`person_id`)
- `close_employee_id` REFERENCES `ospos_employees`(`person_id`)

#### ospos_app_config (System Configuration)
**Primary Key:** `key`

**Fields:**
- `key` VARCHAR(50) - Configuration key
- `value` VARCHAR(500) - Configuration value

**Indexes:**
- PRIMARY KEY (`key`)

#### ospos_people (People Database)
**Primary Key:** `person_id`

**Fields:**
- `person_id` INT(10) AUTO_INCREMENT - Primary key
- `first_name` VARCHAR(255) - First name
- `last_name` VARCHAR(255) - Last name
- `phone_number` VARCHAR(255) - Phone number
- `email` VARCHAR(255) - Email address
- `address_1` VARCHAR(255) - Address line 1
- `address_2` VARCHAR(255) - Address line 2
- `city` VARCHAR(255) - City
- `state` VARCHAR(255) - State
- `zip` VARCHAR(255) - ZIP code
- `country` VARCHAR(255) - Country
- `comments` TEXT - Comments

**Indexes:**
- PRIMARY KEY (`person_id`)
- INDEX (`email`)

#### ospos_suppliers (Supplier Database)
**Primary Key:** `person_id` (references ospos_people)

**Fields:**
- `person_id` INT(10) - Primary key
- `company_name` VARCHAR(255) - Company name
- `account_number` VARCHAR(255) - Account number
- `category` VARCHAR(255) - Category

**Indexes:**
- PRIMARY KEY (`person_id`)

**Foreign Keys:**
- `person_id` REFERENCES `ospos_people`(`person_id`)

#### ospos_item_kits (Product Bundles)
**Primary Key:** `item_id`

**Fields:**
- `item_id` INT(10) AUTO_INCREMENT - Primary key
- `name` VARCHAR(255) - Kit name
- `description` TEXT - Kit description
- `kit_discount_percent` DECIMAL(15,2) - Discount percentage
- `price_option` TINYINT(2) - Price calculation method
- `print_option` TINYINT(2) - Print behavior
- `item_kit_number` VARCHAR(255) - Kit barcode
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`item_id`)

#### ospos_item_kit_items (Kit Components)
**Fields:**
- `item_kit_id` INT(10) - Kit reference
- `item_id` INT(10) - Item reference
- `quantity` DECIMAL(15,3) - Quantity per kit
- `kit_sequence` INT(3) - Display order

**Indexes:**
- PRIMARY KEY (`item_kit_id`, `item_id`)

**Foreign Keys:**
- `item_kit_id` REFERENCES `ospos_item_kits`(`item_id`)
- `item_id` REFERENCES `ospos_items`(`item_id`)

#### ospos_item_quantities (Stock Quantities)
**Fields:**
- `item_id` INT(10) - Item reference
- `location_id` INT(10) - Location reference
- `quantity` DECIMAL(15,3) - Stock quantity

**Indexes:**
- PRIMARY KEY (`item_id`, `location_id`)

**Foreign Keys:**
- `item_id` REFERENCES `ospos_items`(`item_id`)
- `location_id` REFERENCES `ospos_stock_locations`(`location_id`)

#### ospos_stock_locations (Stock Locations)
**Primary Key:** `location_id`

**Fields:**
- `location_id` INT(10) AUTO_INCREMENT - Primary key
- `location_name` VARCHAR(255) - Location name
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`location_id`)

#### ospos_customers_packages (Reward Packages)
**Primary Key:** `package_id`

**Fields:**
- `package_id` INT(11) AUTO_INCREMENT - Primary key
- `package_name` VARCHAR(255) - Package name
- `points_percent` FLOAT - Points earning percentage
- `deleted` TINYINT(1) - Soft delete flag

**Indexes:**
- PRIMARY KEY (`package_id`)

#### ospos_customers_points (Customer Points)
**Primary Key:** `id`

**Fields:**
- `id` INT(11) AUTO_INCREMENT - Primary key
- `person_id` INT(11) - Customer reference
- `package_id` INT(11) - Package reference
- `sale_id` INT(11) - Sale reference
- `points_earned` INT(11) - Points earned

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`person_id`)
- INDEX (`package_id`)
- INDEX (`sale_id`)

**Foreign Keys:**
- `person_id` REFERENCES `ospos_customers`(`person_id`)
- `package_id` REFERENCES `ospos_customers_packages`(`package_id`)
- `sale_id` REFERENCES `ospos_sales`(`sale_id`)

#### ospos_sales_reward_points (Sale Rewards)
**Primary Key:** `id`

**Fields:**
- `id` INT(11) AUTO_INCREMENT - Primary key
- `sale_id` INT(11) - Sale reference
- `earned` FLOAT - Points earned
- `used` FLOAT - Points used

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`sale_id`)

**Foreign Keys:**
- `sale_id` REFERENCES `ospos_sales`(`sale_id`)

#### ospos_modules (Modules)
**Primary Key:** `module_id`

**Fields:**
- `module_id` VARCHAR(255) - Module identifier
- `name_lang_key` VARCHAR(255) - Name language key
- `desc_lang_key` VARCHAR(255) - Description language key
- `sort` INT(10) - Sort order

**Indexes:**
- PRIMARY KEY (`module_id`)

#### ospos_permissions (Permissions)
**Primary Key:** `permission_id`

**Fields:**
- `permission_id` VARCHAR(255) - Permission identifier
- `module_id` VARCHAR(255) - Module reference
- `location_id` INT(10) - Location reference (optional)

**Indexes:**
- PRIMARY KEY (`permission_id`)
- INDEX (`module_id`)
- INDEX (`location_id`)

**Foreign Keys:**
- `module_id` REFERENCES `ospos_modules`(`module_id`) ON DELETE CASCADE
- `location_id` REFERENCES `ospos_stock_locations`(`location_id`) ON DELETE CASCADE

#### ospos_grants (Permission Grants)
**Fields:**
- `permission_id` VARCHAR(255) - Permission reference
- `person_id` INT(10) - Employee reference

**Indexes:**
- PRIMARY KEY (`permission_id`, `person_id`)

**Foreign Keys:**
- `permission_id` REFERENCES `ospos_permissions`(`permission_id`) ON DELETE CASCADE
- `person_id` REFERENCES `ospos_employees`(`person_id`) ON DELETE CASCADE

#### ospos_sessions (Sessions)
**Fields:**
- `id` VARCHAR(40) - Session ID
- `ip_address` VARCHAR(45) - IP address
- `timestamp` INT(10) - Timestamp
- `data` BLOB - Session data

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`timestamp`)

---

## Supported Languages
Arabic (Egypt, Lebanon), Azerbaijani, Bulgarian, Bosnian, Kurdish (Sorani), Czech, Danish, German (Switzerland, Germany), Greek, English (US, UK), Spanish (Spain, Mexico), Persian, French, Hebrew, Croatian, Hungarian, Armenian, Indonesian, Italian, Georgian, Lao, Malay, Norwegian Bokmål, Dutch (Belgium, Netherlands), Polish, Portuguese (Brazil), Romanian, Russian, Swedish, Tamil, Thai, Tagalog, Turkish, Ukrainian, Urdu, Vietnamese, Chinese (Simplified, Traditional)

---

## Technology Stack
- **Backend**: PHP 8.1+, CodeIgniter 4
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 3, Bootswatch, jQuery
- **Deployment**: Docker, Docker Compose
- **Security**: Encryption, CSRF protection, reCAPTCHA

---

## Key Benefits for Mobile App Development

### For Android APK Development:
1. **RESTful API Structure**: CodeIgniter 4 provides RESTful endpoints
2. **JSON Responses**: All data operations return JSON
3. **Authentication System**: Built-in user authentication
4. **Permission System**: Granular access control for mobile users
5. **Barcode Integration**: Native barcode scanning support
6. **Offline Capability**: Session-based operation support

### For Backend Development:
1. **Modular Architecture**: Clean separation of concerns
2. **Database Migrations**: Version-controlled schema updates
3. **Caching System**: Performance optimization
4. **Multi-tenant Ready**: Stock location support
5. **Extensible**: Custom attributes system
6. **Well-documented**: Clear code structure

### For Backoffice Development:
1. **Comprehensive Admin Panel**: Full management interface
2. **Reporting System**: Extensive report generation
3. **Configuration Management**: Centralized settings
4. **User Management**: Employee and permission system
5. **Audit Trail**: Transaction logging
6. **Integration Ready**: MailChimp, SMS, Email APIs

---

## Use Cases
- Retail stores
- Restaurants and cafes
- Wholesale businesses
- Service businesses
- Multi-location retail chains
- E-commerce backoffice
- Inventory management systems
- Point of sale terminals

---