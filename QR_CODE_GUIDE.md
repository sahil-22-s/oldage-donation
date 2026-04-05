# QR Code Feature Guide

## How to Use QR Code Payment Option

### For Donors (Website Visitors)

1. **View the QR Code**
   - Click "View QR Code" button in the donation form
   - A modal will appear showing the payment QR code
   - Scan with any UPI app (Google Pay, PhonePe, BHIM, etc.)

2. **Supported Payment Methods**
   - UPI Payment
   - Card Payment
   - **QR Code Payment** (NEW!)
   - Donate in Person

---

## For Admin (Managing QR Code)

### How to Update Your QR Code

1. **Login to Admin Dashboard**
   - Username: `admin`
   - Password: `1234`

2. **Go to QR Code Management Section**
   - You'll see a purple section titled "Payment QR Code Management"
   - Left side: Current QR code display
   - Right side: Update options

### Method 1: Update from Image URL

1. Get your QR code image URL
2. Paste it in "QR Code Image URL" field
3. Click "Update from URL" button
4. Your custom QR code will be displayed to all donors

**Supported formats:**
- Direct image URLs (PNG, JPG, GIF)
- Any image hosting service (Imgur, Cloudinary, etc.)

### Method 2: Generate from QR Data

1. Paste your UPI link or QR code data
2. Click "Generate from Data" button
3. System automatically creates a QR code image

**Example UPI links:**
```
upi://pay?pa=yourname@upi&pn=ElderCare&am=&tn=Donation
upi://pay?pa=eldercare@okhdfcbank&pn=ElderCareHome
```

**Other data formats:**
- Website URLs
- Contact information
- WiFi credentials
- Bank account details
- Any text that can be encoded in QR

---

## Getting Your Own QR Code

### Option 1: Free QR Code Generators

**Online Generators:**
- https://www.qrcode-monkey.com
- https://qr-code-generator.com
- https://www.qrcode.am
- https://www.the-qrcode-generator.com

**Steps:**
1. Visit any QR code generator
2. Paste your UPI ID or payment link
3. Generate QR code
4. Download the image
5. Upload to image hosting (Imgur, Cloudinary, etc.)
6. Copy the image URL
7. Paste in Admin Dashboard → Update from URL

### Option 2: Mobile App QRs

**If you have a UPI/Payment App:**
1. Open your payment app (Google Pay, PhonePe, etc.)
2. Go to "Request Money" or "QR Code" section
3. Display your payment QR code
4. Take a screenshot
5. Upload to cloud storage or image host
6. Use the link in Admin Dashboard

### Option 3: Generate via Admin Dashboard

**Easiest Method:**
1. Login to Admin Dashboard
2. In "QR Code Management" section
3. Paste your UPI address in "Or Paste QR Data"
4. Click "Generate from Data"
5. Done! System will create the QR code automatically

---

## Default QR Code

The default QR code is currently set to:
```
UPI Link: upi://pay?pa=eldercare@upi&pn=ElderCareHome&am=&tn=Donation
```

You can change this to your own:
- Bank UPI ID
- Payment gateway link
- Personalized account details

---

## Where the QR Code Appears

1. **Donor View**: "View QR Code" button in donation form
2. **Mobile Friendly**: Automatically scales for all devices
3. **Persistent**: Changes saved in browser storage
4. **Always Available**: Works with or without backend

---

## Tips

✅ **Use High Resolution QR Codes** - Ensures easy scanning
✅ **Test Your QR Code** - Scan with different apps before using
✅ **Simple Data** - Shorter data = easier to scan
✅ **Update Regularly** - Keep payment details current
✅ **Backup QR Code** - Save in multiple places

---

## Troubleshooting

### QR Code not updating?
- Clear browser cache
- Try incognito/private browsing
- Use "Generate from Data" instead of URL

### QR Code not scanning?
- Check if image URL is correct
- Try a different QR code generator
- Ensure code is high contrast (dark on light)
- Test with multiple scanning apps

### Lost custom QR code?
- Check browser localStorage
- Try regenerating with your UPI ID
- Re-upload from backup

---

## Examples

### Example 1: UPI Payment
```
Data: upi://pay?pa=yourname@okhdfcbank&pn=ElderCare&tn=Donation
Goal: Donors scan → direct payment to your bank
```

### Example 2: Payment Gateway
```
URL: https://payment-gateway.com/eldercare/donation
Goal: Donors scan → redirected to online form
```

### Example 3: Crypto Payment
```
URL: https://qr-code-for-wallet-address.png
Goal: Donors scan → send crypto directly
```

---

## Security Notes

- QR codes are client-side only (stored in browser)
- No sensitive data sent to server
- Each browser stores its own QR code
- Clear browser data = QR code reset to default

---

**Updated: February 25, 2026**
