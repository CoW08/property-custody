# 📝 Signature Setup Instructions

## ✅ What I've Done:

I've updated the PDF generators to automatically include signature images when they exist:

1. **Property Issuance Receipts** (`generate_issuance_pdf.php`)
   - Shows staff signature above recipient signature line
   
2. **Accountability Transfer Documents** (`generate_accountability_pdf.php`)
   - Shows staff signature above custodian signature line

## 📋 What You Need to Do:

### Step 1: Save Your Signature Images

Save the signature images as:

**Staff Signature (first image):**
```
c:\Users\johnr\Desktop\property-custodian\signatures\staff_signature.png
```

**Custodian Signature (second image):**
```
c:\Users\johnr\Desktop\property-custodian\signatures\custodian_signature.png
```

### Step 2: Image Requirements

- **Format:** PNG (transparent background recommended)
- **Recommended size:** 150-200 pixels wide, 60-80 pixels tall
- **File size:** Keep under 100KB

### Step 3: Test It

1. Generate a new Property Issuance receipt
2. Generate a new Accountability Transfer document
3. The signature should appear automatically above the recipient's name

## 🎨 How It Looks in PDFs:

```
┌──────────────────────┐  ┌──────────────────────┐
│  [Staff Signature]   │  │ [Custodian Signature]│
│  ─────────────────   │  │  ─────────────────   │
│  Staff Member Name   │  │  Custodian Name      │
│  Recipient Signature │  │  Property Custodian  │
│  Date: ____________  │  │  Date: Oct 5, 2025   │
└──────────────────────┘  └──────────────────────┘
```

## 📁 Folder Structure:

```
property-custodian/
└── signatures/
    ├── README.md
    ├── staff_signature.png      ← Save staff signature here (REQUIRED)
    ├── custodian_signature.png  ← Save custodian signature here (REQUIRED)
    └── admin_signature.png      (optional - for future use)
```

## ⚙️ Technical Details:

The PDF generators check if the signature file exists:
```php
$staffSignaturePath = __DIR__ . '/signatures/staff_signature.png';
if (file_exists($staffSignaturePath)) {
    // Display signature image
}
```

If the file doesn't exist, the PDF will just show the signature line as before.

## 🔄 Future Enhancements:

You can also add:
- `custodian_signature.png` - For property custodian
- `admin_signature.png` - For admin approvals
- User-specific signatures linked to their accounts

## ✨ Benefits:

- ✅ Automatic signature inclusion
- ✅ Professional appearance
- ✅ No manual signing needed for digital copies
- ✅ Consistent branding
- ✅ Easy to update (just replace the PNG file)

---

**Last Updated:** October 5, 2025, 2:53 AM
