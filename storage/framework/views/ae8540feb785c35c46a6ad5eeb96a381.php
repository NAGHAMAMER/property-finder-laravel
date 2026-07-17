<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>رمز استعادة كلمة المرور</title>
</head>
<body style="margin:0;background:#f1f5f9;font-family:Tahoma,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:30px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.10);">
                <tr>
                    <td style="background:#2563eb;color:#fff;padding:24px;text-align:center;">
                        <h1 style="margin:0;font-size:25px;">🏠 عقاري</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:30px;">
                        <h2 style="margin-top:0;">مرحبًا <?php echo e($userName); ?></h2>
                        <p style="line-height:1.9;color:#475569;">استخدم الرمز التالي لإعادة تعيين كلمة المرور الخاصة بحسابك:</p>
                        <div style="margin:24px auto;text-align:center;font-size:36px;font-weight:900;letter-spacing:8px;color:#1d4ed8;background:#eff6ff;border:1px dashed #93c5fd;border-radius:14px;padding:18px;">
                            <?php echo e($code); ?>

                        </div>
                        <p style="line-height:1.9;color:#475569;">الرمز صالح لمدة <strong>10 دقائق</strong> فقط. إذا لم تطلب تغيير كلمة المرور، تجاهل هذه الرسالة.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
<?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/emails/password-reset-code.blade.php ENDPATH**/ ?>