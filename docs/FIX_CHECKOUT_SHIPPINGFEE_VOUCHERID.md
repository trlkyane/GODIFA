# FIX CHECKOUT - ShippingFee & VoucherID Issues
**Date:** 10/11/2024  
**Issue:** Payment error with shippingFee and voucherID columns

---

## 🐛 PROBLEMS IDENTIFIED

### 1. NULL VoucherID Handling
**Issue:**  
When customer doesn't use a voucher, `$voucherID = null`. Attempting to bind NULL with type 'i' (integer) in `bind_param()` causes errors.

**Error:**
```php
// BEFORE - Always included voucherID:
$stmt->bind_param("sdsiiis", ..., $voucherID, ...);
// When voucherID is NULL → bind_param error
```

**Fix:**  
Conditional SQL based on whether voucherID exists:

```php
// AFTER:
if ($voucherID) {
    // Include voucherID column
    INSERT INTO `order` (..., voucherID, ...) VALUES (...)
    $stmt->bind_param("sdsiids", ..., $voucherID, ...);
} else {
    // Exclude voucherID column (let it default to NULL)
    INSERT INTO `order` (..., shippingFee, ...) VALUES (...)
    $stmt->bind_param("sdsids", ..., $shippingFee, ...);
}
```

---

### 2. Wrong bind_param Type Hints
**Issue:**  
Type hints in `bind_param()` didn't match database column types.

**Errors:**
```php
// BEFORE:
$stmt->bind_param("sdsiiids", 
    $paymentStatus,  // s ✅ varchar
    $finalAmount,    // d ✅ decimal
    $paymentMethod,  // s ✅ varchar
    $customerID,     // i ✅ int
    $voucherID,      // i ✅ int
    $shippingFee,    // i ❌ WRONG! Should be 'd' (decimal)
    $notes           // d ❌ WRONG! Should be 's' (text)
);
```

**Fix:**
```php
// AFTER:
$stmt->bind_param("sdsiids", 
    $paymentStatus,  // s = string (varchar)
    $finalAmount,    // d = double (decimal)
    $paymentMethod,  // s = string (varchar)
    $customerID,     // i = integer
    $voucherID,      // i = integer
    $shippingFee,    // d = double (decimal) ✅ FIXED
    $notes           // s = string (text) ✅ FIXED
);
```

---

### 3. Missing Database Columns
**Issue:**  
Code attempted to UPDATE non-existent columns `qrExpiredAt` and `qrUrl`:

```sql
UPDATE `order` 
SET transactionCode = ?, qrExpiredAt = ?, qrUrl = ? 
WHERE orderID = ?
```

**Database Reality:**
```
mysql> DESCRIBE `order`;
No columns named: qrExpiredAt, qrUrl
```

**Fix:**
```php
// BEFORE:
UPDATE `order` 
SET transactionCode = ?, qrExpiredAt = ?, qrUrl = ? 
WHERE orderID = ?

// AFTER:
UPDATE `order` 
SET transactionCode = ?
WHERE orderID = ?

// Store QR info in session instead
$_SESSION['qr_url'] = $qrUrl;
$_SESSION['qr_expired_at'] = $qrExpiredAt;
```

---

## ✅ FIXES APPLIED

### File: `controller/cCheckout.php`

**1. Conditional VoucherID Handling:**
```php
// Lines ~133-165
if ($voucherID) {
    // WITH voucher
    INSERT INTO `order` (..., voucherID, ...) VALUES (...)
} else {
    // WITHOUT voucher
    INSERT INTO `order` (...) VALUES (...)  // voucherID excluded
}
```

**2. Correct Type Hints:**
```php
// WITH voucher:
bind_param("sdsiids", $paymentStatus, $finalAmount, $paymentMethod, 
           $customerID, $voucherID, $shippingFee, $notes)

// WITHOUT voucher:
bind_param("sdsids", $paymentStatus, $finalAmount, $paymentMethod, 
           $customerID, $shippingFee, $notes)
```

**3. Removed Non-Existent Columns:**
```php
// Lines ~221-232
// Only update transactionCode (exists)
UPDATE `order` SET transactionCode = ? WHERE orderID = ?

// Store QR info in session for display
$_SESSION['qr_url'] = $qrUrl;
$_SESSION['qr_expired_at'] = $qrExpiredAt;
```

---

## 🗂️ DATABASE STRUCTURE

### `order` table relevant columns:
```sql
voucherID        INT NULL           -- Can be NULL (no voucher used)
shippingFee      DECIMAL(10,2) NULL DEFAULT 0.00
totalAmount      DECIMAL(10,0) NOT NULL
transactionCode  VARCHAR(50) NULL
```

**Missing columns** (not in schema):
- ❌ `qrExpiredAt` - Not in table
- ❌ `qrUrl` - Not in table

---

## 🧪 TESTING

### Test Case 1: Order WITHOUT Voucher
```
✅ voucherID = NULL
✅ shippingFee = 20000
✅ SQL: INSERT without voucherID column
✅ bind_param("sdsids", ...)
```

### Test Case 2: Order WITH Voucher
```
✅ voucherID = 5
✅ shippingFee = 20000
✅ SQL: INSERT with voucherID column
✅ bind_param("sdsiids", ...)
```

### Test Case 3: COD Payment
```
✅ transactionCode updated
✅ No qrExpiredAt/qrUrl update
✅ Redirect to thankyou.php
```

### Test Case 4: QR Payment
```
✅ transactionCode updated
✅ QR info stored in session
✅ Redirect to checkout_qr.php
```

---

## 📊 BIND_PARAM TYPE REFERENCE

| PHP Type | MySQL Type | Example |
|----------|------------|---------|
| `s` | VARCHAR, TEXT, CHAR, ENUM | 'Hello', 'COD' |
| `i` | INT, TINYINT, BIGINT | 123, -456 |
| `d` | DECIMAL, FLOAT, DOUBLE | 12.34, 10000.00 |
| `b` | BLOB | Binary data |

**Common Mistakes:**
- ❌ Using `i` for DECIMAL → Use `d`
- ❌ Using `d` for TEXT → Use `s`
- ❌ Binding NULL integer → Exclude column or handle separately

---

## 🚀 DEPLOYMENT CHECKLIST

- [x] Fix voucherID NULL handling
- [x] Fix bind_param type hints
- [x] Remove non-existent column updates
- [x] Store QR info in session
- [x] Test order without voucher
- [x] Test order with voucher
- [x] Test COD payment
- [x] Test QR payment
- [ ] Clear PHP opcache (if enabled)
- [ ] Monitor checkout.log for errors
- [ ] Test on production (if applicable)

---

## 📝 NOTES

**Why conditional SQL?**
- Cannot bind NULL to INTEGER type in mysqli
- Better to exclude column from INSERT and let DB default to NULL
- Cleaner than using workarounds like 0 or empty string

**Why session for QR info?**
- Original code tried to save to non-existent columns
- Session is temporary storage until payment confirmed
- Alternative: Create new columns (qrExpiredAt, qrUrl) in order table

**Future Enhancement:**
If QR info needs persistence, add columns:
```sql
ALTER TABLE `order` 
ADD COLUMN qrExpiredAt DATETIME NULL AFTER transactionCode,
ADD COLUMN qrUrl TEXT NULL AFTER qrExpiredAt;
```

---

## ✅ STATUS

**VoucherID Issue:** ✅ FIXED  
**ShippingFee Type:** ✅ FIXED  
**Missing Columns:** ✅ FIXED (removed from code)  
**Checkout Error:** ✅ RESOLVED  

**Ready for Testing:** ✅ YES
