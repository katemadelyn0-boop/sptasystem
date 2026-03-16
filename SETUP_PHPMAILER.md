# PHPMailer Setup Instructions

## Step 1: Install PHPMailer

I-download ang PHPMailer gamit ang Composer sa XAMPP:

1. I-download ang Composer: https://getcomposer.org/download/
2. Buksan ang CMD sa folder ng project:
   ```
   cd C:\xampp\htdocs\spta-system
   composer require phpmailer/phpmailer
   ```

### Wala kang Composer? Manual install:
1. Pumunta sa: https://github.com/PHPMailer/PHPMailer/releases
2. I-download ang latest ZIP
3. I-extract at i-copy ang `src` folder sa:
   `C:\xampp\htdocs\spta-system\vendor\phpmailer\phpmailer\src\`
4. Gumawa ng `vendor\autoload.php` (see below)

### Simple autoload.php (kung walang Composer):
```php
<?php
spl_autoload_register(function($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base   = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $file = $base . substr($class, strlen($prefix)) . '.php';
        if (file_exists($file)) require $file;
    }
});
```
I-save as: `C:\xampp\htdocs\spta-system\vendor\autoload.php`

---

## Step 2: I-setup ang Gmail App Password

1. Mag-login sa Gmail → Google Account → Security
2. I-enable ang **2-Step Verification**
3. Pumunta sa **App Passwords**
4. Gumawa ng App Password → piliin "Mail" → "Windows Computer"
5. Kopyahin ang 16-character na password na lalabas

---

## Step 3: I-edit ang config/mailer.php

Buksan ang `C:\xampp\htdocs\spta-system\config\mailer.php` at palitan:

```php
define('MAIL_USERNAME', 'yourgmail@gmail.com');   // Gmail mo
define('MAIL_PASSWORD', 'xxxx xxxx xxxx xxxx');    // App Password
define('MAIL_FROM',     'yourgmail@gmail.com');    // Gmail mo
```

---

## Step 4: I-run ang ADD_THESE_TO_DB.sql sa phpMyAdmin

1. Buksan ang phpMyAdmin → piliin ang `spta_system` database
2. I-click ang **SQL** tab
3. I-paste ang laman ng `ADD_THESE_TO_DB.sql`
4. I-click **Go**

---

## Files na kailangan i-replace sa spta-system folder:

| File | Location |
|------|----------|
| register.php | C:\xampp\htdocs\spta-system\ |
| staff/reports.php | C:\xampp\htdocs\spta-system\staff\ |
| staff/payments.php | C:\xampp\htdocs\spta-system\staff\ |
| officer/students.php | C:\xampp\htdocs\spta-system\officer\ |
| officer/payments.php | C:\xampp\htdocs\spta-system\officer\ |
| config/mailer.php | C:\xampp\htdocs\spta-system\config\ |
| includes/sidebar.php | C:\xampp\htdocs\spta-system\includes\ |

